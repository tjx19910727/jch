<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/11/10
 * Time: 11:01
 */

namespace Jd\Declares\Shop;


use Jd\Kernel\Container;
use Jd\Kernel\ServiceProviderInterface;

class ShopProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['shop'] = function ($app) {
            return new ShopClient($app);
        };
    }
}