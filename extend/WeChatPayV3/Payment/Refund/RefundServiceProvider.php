<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/6/26
 * Time: 16:12
 */

namespace WeChatPayV3\Payment\Refund;


use WeChatPayV3\Kernel\Container;
use WeChatPayV3\Kernel\ServiceProviderInterface;

class RefundServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['refund'] = function ($app) {
            return new RefundClient($app);
        };
    }
}