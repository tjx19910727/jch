<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2025/6/7
 * Time: 15:03
 */

namespace app\AppFactory\Pay\Notify;


use app\AppFactory\Kernel\ServiceContainer;
use app\AppFactory\Kernel\Traits\Payment\AfterOrderPaymentTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersRevenueTrait;
use app\AppFactory\Kernel\Util\SignUtil;
use app\AppFactory\Pay\PayBaseClient;

class PosClient extends PayBaseClient
{
    use AfterOrderPaymentTrait;
    use SaleOrdersRevenueTrait;

    public function __construct(ServiceContainer $app)
    {
        parent::__construct($app);
        $this->data = $app->getConfig();
        if (isset($this->data['machine_id']) && $this->data['machine_id'])
            $this->machine = $this->getMachineFind(['machine_id' => $this->data['machine_id']]);
        $check = SignUtil::checkSign($this->data, $this->machine['signKey']);
        if ($check !== true) {
            $this->r(300, $this->lang("VPos.check_sign_fail"))->send();
            die();
        }
    }

    /**
     * POS机支付回调通知数据
     * @return string
     */
    public function handlePos()
    {
        $message = $this->data;
        $tradeNo = $message['trade_no'];
        $this->order = $this->getSaleOrdersFind(['trade_no' => $tradeNo]);
        actionLog($this->getLS(), '【SQL】查询订单');
        actionLog($this->order, '订单数据');
        if (!$this->order) return $this->rFail($this->lang("VPos.order_no_data"));
        // pay_status，支付状态，3：已支付，5：取消支付
        if ($message['payment_status'] === 'TRADE_SUCCESS') {
            $this->order = $this->order->toArray();
            if ($this->order['pay_status'] != 3) {
                $this->order['mch_no'] = $message['mch_no'];
                $this->startTrans();
                // 结算分润收益
                try {
                    $flag[] = $this->settlementRevenue();
                    $flag[] = $this->paymentSuccessful();
                    actionLog($flag, 'flag');
                    $result = flag_check($flag);
                    $result = $this->checkTrans($result);
                    actionLog($result, '支付成功处理事务结果');
                    return $result;
                } catch (\Exception $e) {
                    $this->rollbackTrans();
                    actionException($e, 1);
                    return $this->rTryCatch($e->getMessage());
                }
            }
        } else {
            $result = $this->paymentFailed();
            actionLog($result, '支付失败结果');
        }
        return $this->rAction(true);
    }
}