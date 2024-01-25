<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/11/9
 * Time: 13:57
 */


namespace Jd\Payment;

use Jd\Kernel\ServiceContainer;
use Jd\Payment\Order\OrderProvider;

/**
 * Class Application
 * @property \Jd\Payment\Order\OrderClient $order  订单支付
 */
class Application extends ServiceContainer
{
    protected $providers = [
        OrderProvider::class,
    ];
}