<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/2
 * Time: 16:32
 */

namespace app\AppFactory\Kernel\Providers\TimeTask;


use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\TimeTask\Goods\GoodsClient;

class GoodsProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['goods'] = function ($app) {
            return new GoodsClient($app);
        };
    }
}