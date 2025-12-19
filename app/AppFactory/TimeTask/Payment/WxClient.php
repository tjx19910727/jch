<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/28
 * Time: 19:19
 */

namespace app\AppFactory\TimeTask\Payment;


use app\AppFactory\Kernel\Traits\Machine\MachineMqRecordTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Traits\Payment\AfterOrderPaymentTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersRevenueTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyMachineTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyPayeeTrait;
use app\AppFactory\Kernel\Traits\User\UserTrait;
use app\AppFactory\TimeTask\TimeTaskBase;
use EasyWeChat\Factory;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use EasyWeChat\Kernel\Exceptions\InvalidConfigException;

class WxClient extends TimeTaskBase
{
    use SaleOrdersTrait,SaleOrdersRevenueTrait;
    use StrategyPayeeTrait,StrategyMachineTrait;
    use AfterOrderPaymentTrait;
    use MachineTrait,MachineMqRecordTrait;
    use UserTrait;

    protected $order;
    protected $payResult;

    /**
     * 查询微信反扫支付结果
     * @param $order_id
     * @return array|mixed|string
     */
    public function queryMicroPay($order_id)
    {

        try {
            $this->order = $this->getSaleOrdersFind(['order_id' => $order_id]);
            if (!$this->order) {
                return $this->rFail("查无订单数据");
            }
            if ($this->order['pay_status'] != 1) return $this->rFail("订单已处理");
            $this->order = $this->order->toArray();
            $wxConfig = $this->getStrategyPayeeContent(['sp_id' => $this->order['sp_id'], 'sm.s_type' => 1]);
            if($this->order['ao_id'] == 19){
                $wxConfig = $this->getStrategyPayeeContent(['sp_id' => $this->order['sp_id'],'sm.s_type' => 1,'sm.ao_id' => $this->order['ao_id']]);
            }
            if (!$wxConfig) return $this->rFail("查无收款配置信息");
            $app = Factory::payment($wxConfig);
            $this->payResult = $app->order->queryByOutTradeNumber($this->order['trade_no']);
            actionLog($this->payResult, '查询结果');
            if ($this->payResult) {
                $this->order['mch_no'] = $this->payResult['transaction_id'] ?? '';
                $this->order['pay_type'] = 1;
                $this->order['pay_method'] = 2;
                if (isset($this->payResult['openid']) && $this->payResult['openid']) {
                    $user = $this->getUserFind(['openid' => $this->payResult['openid']]);
                    if ($user) {
                        $this->order['user_id'] = $user['user_id'];
                        $this->order['user_name'] = $user['name'];
                    } else {
                        $insert['openid'] = $this->payResult['openid'];
                        $this->order['user_id'] = $this->addUser($insert);
                    }
                }

                // 需要用户输入密码
                if (isset($this->payResult['result_code']) && $this->payResult['result_code'] == 'FAIL') {
                    if ($this->payResult['err_code'] == 'USERPAYING') {
                        return $this->USERPAYING();
                    } else {
                        return $this->FAIL();
                    }
                }

                if (isset($this->payResult['trade_state'])) {
                    if (method_exists($this, $this->payResult['trade_state'])) {
                        $method_name = $this->payResult['trade_state'];
                        return $this->$method_name();
                    }
                    return $this->r(100, '未定义返回类型：' . $this->payResult['result_code'], $this->payResult);
                }
            }
            return $this->r(100, '未有符合判断支付结果的内容', $this->payResult);
        } catch (InvalidArgumentException $e) {
            actionException($e,1,'queryMicroPay');
            return $this->rTryCatch($e->getMessage());
        } catch (InvalidConfigException $e) {
            actionException($e,1,'queryMicroPay');
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 支付成功
     * @return bool|string
     */
    private function SUCCESS()
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
     * 支付失败
     * @return array|\think\response\Json
     */
    private function FAIL()
    {
        $this->paymentFailed();
        return $this->r(101, '支付失败：' . $this->payResult['err_code_des'], $this->payResult);
    }

    /**
     * 等待用户支付
     * @return array|\think\response\Json
     */
    private function USERPAYING()
    {
//        $this->sendToMachine(['machine_id' => $this->order['machine_id']],'paying');
        return $this->r(201,'等待用户支付',$this->payResult);
    }

    /**
     * 支付错误
     * @return array|\think\response\Json
     */
    private function PAYERROR()
    {
        $this->paymentError();
        return $this->r(101, $this->payResult['trade_state_desc'], $this->payResult);
    }
}