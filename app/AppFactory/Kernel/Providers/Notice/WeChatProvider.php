<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/2
 * Time: 9:34
 */

namespace app\AppFactory\Kernel\Providers\Notice;


use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\Notice\WeChat\WeChatClient;

class WeChatProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['weChat'] = function ($app) {
            return new WeChatClient($app);
        };
    }
}