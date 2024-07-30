<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/29
 * Time: 17:39
 */

namespace app\AppFactory\Kernel\Providers\TimeTask;


use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\TimeTask\Payment\AliClient;
use app\AppFactory\TimeTask\Payment\WxClient;

class PaymentProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['ali'] = function ($app) {
            return new AliClient($app);
        };
        $app['wx'] = function ($app) {
            return new WxClient($app);
        };
    }
}