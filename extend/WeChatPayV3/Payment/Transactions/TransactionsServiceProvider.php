<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/10/19
 * Time: 14:38
 */

namespace WeChatPayV3\Payment\Transactions;


use WeChatPayV3\Kernel\Container;
use WeChatPayV3\Kernel\ServiceProviderInterface;

class TransactionsServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['transactions'] = function ($app) {
            return new TransactionsClient($app);
        };
    }
}