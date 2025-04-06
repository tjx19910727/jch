<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/11/10
 * Time: 10:55
 */

namespace Jd\Declares\Settle;


use Jd\Kernel\Container;
use Jd\Kernel\ServiceProviderInterface;

class SettleProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['settle'] = function ($app) {
            return new SettleClient($app);
        };
    }
}