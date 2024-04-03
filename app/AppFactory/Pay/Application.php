<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/22
 * Time: 19:57
 */

namespace app\AppFactory\Pay;


use app\AppFactory\Kernel\Providers\Pay\AliProvider;
use app\AppFactory\Kernel\Providers\Pay\JdCashierProvider;
use app\AppFactory\Kernel\Providers\Pay\SaleOrdersProvider;
use app\AppFactory\Kernel\Providers\Pay\WxProvider;
use app\AppFactory\Kernel\ServiceContainer;

/**
 * Class Application
 * @property SaleOrders\PaymentClient                                     $payment         订单支付
 * @property Notify\JdCashierClient                                       $jdNotify        京东收银回调处理
 * @property Notify\AliClient                                             $aliNotify       支付宝回调处理
 * @property Notify\WxClient                                              $wxNotify        微信回调处理
 * @package app\AppFactory\Pay
 */
class Application extends ServiceContainer
{
    protected $providers = [
        JdCashierProvider::class,
        AliProvider::class,
        WxProvider::class,
        SaleOrdersProvider::class,
    ];
}