<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/8/5
 * Time: 10:48
 */
namespace WeChatPayV3\Kernel\Providers;

use WeChatPayV3\Kernel\Config;
use WeChatPayV3\Kernel\Container;
use WeChatPayV3\Kernel\ServiceProviderInterface;

class ConfigProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['config'] = function ($app) {
            return new Config($app->getConfig());
        };
    }
}