<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/22
 * Time: 9:57
 */

namespace app\AppFactory\Wx\Official;


use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;

class OfficialProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['official'] = function ($app) {
            return new OfficialClient($app);
        };
        $app['login'] = function ($app) {
            return new LoginClient($app);
        };
        $app['coupon'] = function ($app) {
            return new CouponClient($app);
        };
    }
}