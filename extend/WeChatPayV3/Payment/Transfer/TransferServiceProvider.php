<?php


namespace WeChatPayV3\Payment\Transfer;


use WeChatPayV3\Kernel\Container;
use WeChatPayV3\Kernel\ServiceProviderInterface;

class TransferServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['transfer'] = function ($app) {
            return new TransferClient($app);
        };
    }
}