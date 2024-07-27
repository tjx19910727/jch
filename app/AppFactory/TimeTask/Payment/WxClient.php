<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/28
 * Time: 19:19
 */

namespace app\AppFactory\TimeTask\Payment;


use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Traits\Payment\AfterOrderPaymentTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyPayeeTrait;
use app\AppFactory\Kernel\Traits\User\UserTrait;
use app\AppFactory\TimeTask\TimeTaskBase;
use EasyWeChat\Factory;

class WxClient extends TimeTaskBase
{
    use SaleOrdersTrait;
    use StrategyPayeeTrait;
    use AfterOrderPaymentTrait;
    use MachineTrait;
    use UserTrait;

    protected $order;

    /**
     * 查询微信反扫支付结果
     * @param $order_id
     * @return array|mixed|string
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     */
    public function queryMicroPay($order_id)
    {
        $this->order = $this->getSaleOrdersFind(['order_id' => $order_id]);
        if (!$this->order) {
            return $this->rFail("查无订单数据");
        }
        if ($this->order['pay_status'] != 1) return $this->rFail("订单已处理");
        $this->order = $this->order->toArray();
        $wxConfig = $this->getStrategyPayeeContent(['sp_id' => $this->order['sp_id']]);
        if (!$wxConfig) return $this->rFail("查无收款配置信息");
        $app = Factory::payment($wxConfig);
        $result = $app->order->queryByOutTradeNumber($this->order['trade_no']);
        if ($result) {

            $this->order['mch_no'] = $result['transaction_id'] ?? '';
            $this->order['pay_type'] = 1;
            $this->order['pay_method'] = 2;

            if (isset($result['openid']) && $result['openid']) {
                $user = $this->getUserFind(['openid' => $result['openid']]);
                if ($user) {
                    $this->order['user_id'] = $user['user_id'];
                    $this->order['user_name'] = $user['name'];
                } else {
                    $insert['openid'] = $result['openid'];
                    $this->order['user_id'] = $this->addUser($insert);
                }
            }

            // 需要用户输入密码
            if (isset($result['result_code']) && $result['result_code'] == 'FAIL') {
                if ($result['err_code'] == 'USERPAYING') return $this->r(201,'等待用户支付',$result);
                else return $this->r(100, '支付失败：' . $result['err_code_des'], $result);
            }
            // 反扫MICROPAY，确认支付成功
            if (isset($result['result_code']) && $result['result_code'] == 'SUCCESS' && isset($result['trade_type']) && $result['trade_type'] == 'MICROPAY') {
                $this->startTrans();
                // 结算分润收益
                $flag[] = $this->settlementRevenue();
                $flag[] = $this->paymentSuccessful();
                $result = flag_check($flag);
                $return = $this->checkTrans($result);
                $this->sendToMachine(['machine_id' => $this->order['machine_id']],'paymentSuccessful',$return);
                return $return;
            }

            if (isset($result['trade_state'])) {
                switch ($result['trade_state']) {
                    case "SUCCESS":
                        $this->startTrans();
                        // 结算分润收益
                        $flag[] = $this->settlementRevenue();
                        $flag[] = $this->paymentSuccessful();
                        $result = flag_check($flag);
                        $return = $this->checkTrans($result);
                        $this->sendToMachine(['machine_id' => $this->order['machine_id']],'paymentSuccessful',$return);
                        return $return;
                        break;
//                    case "REFUND":
//                        return $this->REFUND($result);
//                        break;
//                    case "NOTPAY":
//                        return $this->NOTPAY($result);
//                        break;
//                    case "CLOSED":
//                        return $this->CLOSED($result);
//                        break;
//                    case "REVOKED":
//                        return $this->REVOKED($result);
//                        break;
//                    case "USERPAYING":
//                        return $this->USERPAYING($result);
//                        break;
//                    case "PAYERROR":
//                        return $this->PAYERROR($result);
//                        break;
                    case "FAIL":
                        $return = $this->r(101, '支付失败：' . $result['err_code_des'], $result);
                        $this->sendToMachine(['machine_id' => $this->order['machine_id']],'paymentFail',$return);
                        return $return;
                        break;
                    default:
                        actionLog($result, '未定义返回类型');
                        return $this->r(100, '未定义返回类型：' . $result['result_code'], $result);
                        break;
                }
            }
            return $this->r(100,'未有符合判断支付结果的内容',$result);
        }
    }
}