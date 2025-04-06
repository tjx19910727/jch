<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/30
 * Time: 15:32
 */

namespace app\AppFactory\Kernel\Providers\TimeTask;


use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\TimeTask\Activity\CouponClient;

class ActivityProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['coupon'] = function ($app) {
            return new CouponClient($app);
        };
    }
}