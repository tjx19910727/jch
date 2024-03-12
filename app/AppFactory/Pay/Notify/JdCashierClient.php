<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/11
 * Time: 10:29
 */

namespace app\AppFactory\Pay\Notify;


use app\AppFactory\Kernel\Traits\Machine\MachineMqRecordTrait;
use app\AppFactory\Kernel\Traits\Payment\AfterOrderPaymentTrait;
use app\AppFactory\Kernel\Traits\Payment\AfterOrderRefundTrait;
use app\AppFactory\Kernel\Traits\Payment\JdCashierTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersRefundTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersRevenueTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyMachineTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyPayeeTrait;
use app\AppFactory\Kernel\Traits\User\UserTrait;
use app\AppFactory\Pay\PayBaseClient;

class JdCashierClient extends PayBaseClient
{
    use SaleOrdersTrait,SaleOrdersRefundTrait,SaleOrdersRevenueTrait;
    use StrategyPayeeTrait;
    use StrategyMachineTrait;
    use UserTrait;
    use AfterOrderPaymentTrait,AfterOrderRefundTrait;
    use JdCashierTrait;
    use MachineMqRecordTrait;

    protected $jdConfig;
    protected $storeCharge;

    /**
     * 回调通知，必须为https://
     * @param $message
     * @return array|int
     */
    public function handle()
    {
        $trade_no = $this->data['requestNum'];
        $this->order = $this->getSaleOrdersFind(['trade_no' => $trade_no]);
        if (!$this->order) {
            actionLog($this->getLS(), '查无订单');
            return 200;
        }
        $this->order = obj2arr($this->order);
        $this->jdConfig = $this->getStrategyPayeeContent(['sp_id' => $this->order['sp_id'],'sm.s_type' => 1 , 'payee_type' => 4]);
        if (!is_array($this->jdConfig)) return $this->jdConfig;

        $result = $this->data;
//        $header['token'] = request()->header('token');
//        $header['timestamp'] = request()->header('timestamp');
//        $message = [
//            'header' => $header,
//            'body' => $this->data,
//        ];
//        $app = Jd::callback($this->jdConfig);
//        $result = $app->notify->callbackUrl($message);
//        if (is_string($result)) {
//            actionLog($this->data, '验签失败');
//            return 200;
//        }

        $this->order['mch_no'] = $result['orderNum'];
        if (isset($result['openId']) && $result['openId'] && $result['openId'] != "000000") {
            $user = $this->getUserFind(['openid' => $result['openId']]);
            if ($user) {
                $this->order['user_id'] = $user['user_id'];
                $this->order['user_name'] = $user['name'];
            } else {
                $insert['openid'] = $result['openId'];
                $insert['type'] = 2;
                if (substr($result['openId'],0,4) == 2088) $insert['type'] = 3;
                $this->order['user_id'] = $this->addUser($insert);
            }
        }
        $ledger = 0;
        if ($result['subOrderType'] == 'LEDGER' && $result['ledgerStatus']) {
            $ledger = 2;
            if ($result['ledgerStatus'] != "FAIL") {
                $ledger = $result['ledgerStatus'] == "SUCCESS" ? 1 : 0;
            }
        }

//        if ($ledger) {
//            if (JdBillModel::be(['bill_order_id' => $order['order_id'],'bill_result_status' => 3])) {
////                $updateBill['bill_status'] = 1;
////                if ($result['ledgerStatus'] == "SUCCESS") $updateBill['bill_result_status'] = 1;
////                if ($result['ledgerStatus'] == "FAIL") $updateBill['bill_result_status'] = 2;
////                JdBillModel::updateWhere(['bill_order_id' => $order['order_id']], $updateBill);
////                actionLog(JdBillModel::getLS(), '修改分账状态');
////                HandlePay::updateProfit($order['order_id'], 1);
//                $this->updateProfit($order,$ledger);
//            }
//        }
//        actionLog($ledger,'ledger的值');

        $flag = [];
        $this->startTrans();
        // 分润成功
        if ($ledger == 1) $flag[] = $this->settlementRevenue();
        // 分润失败
        if ($ledger == 2) $flag[] = $this->settlementRevenue(3);
        if ($this->order['pay_status'] == 1) {
            $flag[] = $this->paymentSuccessful();
        }
        $result = flag_check($flag);
        actionLog($flag,'flag');
        $return = $this->checkTrans($result);
        actionLog($return, '处理结果');
        echo 200;
        die();
    }

    protected $refund_no;

    public function handleRefund()
    {
        $this->refundTradeNo = $this->data['refundRequestNum'];
        if ($this->data['refundStatus'] == 'SUCCESS') {
            $this->refund_no = $this->data['orderNum'];
            $this->startTrans();
            $result = $this->refundSuccess();
            if ($result === true) {
                $this->commitTrans();
            } else {
                $this->rollbackTrans();
            }
            actionLog($result,'处理退款成功数据结果');
            return 200;
        }
        if ($this->data['refundStatus'] == 'FAIL') {
            $this->refundFail();
            return 200;
        }
    }
}