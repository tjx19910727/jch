<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/17
 * Time: 9:59
 */

namespace app\AppFactory\Kernel\Providers\Management;


use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\Management\Suggest\SuggestClient;

class SuggestProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['suggest'] = function ($app) {
            return new SuggestClient($app);
        };
    }
}