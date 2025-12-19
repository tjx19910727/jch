<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/28
 * Time: 19:19
 */

namespace app\AppFactory\TimeTask\Payment;


use AliPay\Factory;
use app\AppFactory\Kernel\Traits\Machine\MachineMqRecordTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Traits\Payment\AfterOrderPaymentTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersRevenueTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyMachineTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyPayeeTrait;
use app\AppFactory\Kernel\Traits\User\UserTrait;
use app\AppFactory\TimeTask\TimeTaskBase;

class AliClient extends TimeTaskBase
{

    use SaleOrdersTrait,SaleOrdersRevenueTrait;
    use MachineTrait;
    use StrategyPayeeTrait,StrategyMachineTrait;
    use AfterOrderPaymentTrait,MachineMqRecordTrait;
    use UserTrait;

    protected $order;

    /**
     * 查询支付宝反扫支付结果
     * @param $order_id
     * @return array|\think\response\Json
     * @throws \Exception
     */
    public function queryMicroPay($order_id)
    {
        $this->order = $this->getSaleOrdersFind(['order_id' => $order_id]);
        if (!$this->order) {
            return $this->rFail("查无订单数据");
        }
        if ($this->order['pay_status'] != 1) return $this->rFail("订单已处理");
        $this->order = $this->order->toArray();
        $aliConfig = $this->getStrategyPayeeContent(['sp_id' => $this->order['sp_id'],'sm.s_type' => 1]);
        if($this->order['ao_id'] == 19){
            $aliConfig = $this->getStrategyPayeeContent(['sp_id' => $this->order['sp_id'],'sm.s_type' => 1,'sm.ao_id' => $this->order['ao_id']]);
        }
        if (!$aliConfig) return $this->rFail("查无收款配置信息");
        $app = Factory::trade($aliConfig);
        $result = $app->trade->query($this->order['trade_no']);
        if ($result) {
            $this->order['mch_no'] = $result['trade_no'] ?? '';
            $this->order['pay_type'] = 2;
            $this->order['pay_method'] = 2;

            if (isset($result['buyer_user_id']) && $result['buyer_user_id']) {
                $user = $this->getUserFind(['openid' => $result['buyer_user_id']]);
                if ($user) {
                    $this->order['user_id'] = $user['user_id'];
                    $this->order['user_name'] = $user['name'];
                } else {
                    $insert['openid'] = $result['buyer_user_id'];
                    $insert['wx_id'] = $this->order['sp_id'];
                    $this->order['user_id'] = $this->addUser($insert);
                }
            }

            $status = strtolower($result['trade_status']);
            if (method_exists($this,$status)) {
                return $this->$status();
            }
            return $this->rFail("没有对应处理方法类型");
        }
        return $this->rFail("查询错误");
    }

    /**
     * 交易成功
     * @return array|string
     */
    public function trade_success()
    {
        $this->startTrans();
        // 结算分润收益
        $flag[] = $this->settlementRevenue();
        $flag[] = $this->paymentSuccessful();
        $result = flag_check($flag);
        $return = $this->checkTrans($result);
        return $return;
    }

    /**
     * 交易结束
     * @return array|string
     */
    public function trade_finished()
    {
        $this->startTrans();
        // 结算分润收益
        $flag[] = $this->settlementRevenue();
        $flag[] = $this->paymentSuccessful();
        $result = flag_check($flag);
        $return = $this->checkTrans($result);
        return $return;
    }

    /**
     * 等待买家付款
     * @return array|string
     */
    public function wait_buyer_pay()
    {
        $this->sendToMachine(['machine_id' => $this->order['machine_id']],'paying',['trade_no' => $this->order['trade_no']]);
        return $this->r(100,'等待用户支付');
    }

    /**
     * 交易关闭
     * @return array|string
     */
    public function trade_closed()
    {
        $return = $this->r(100,'交易关闭','',false);
        $this->sendToMachine(['machine_id' => $this->order['machine_id']],'payClose',['trade_no' => $this->order['trade_no']]);
        return json($return);
    }

}