<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/22
 * Time: 19:57
 */

namespace app\AppFactory\Pay;


use app\AppFactory\Kernel\Providers\Pay\JdCashierProvider;
use app\AppFactory\Kernel\ServiceContainer;

/**
 * Class Application
 * @property JdCashier\JdCashierClient                                    $jdCashier       京东支付
 * @package app\AppFactory\Pay
 */
class Application extends ServiceContainer
{
    protected $providers = [
        JdCashierProvider::class
    ];
}