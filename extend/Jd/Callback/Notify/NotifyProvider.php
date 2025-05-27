<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/11/10
 * Time: 15:42
 */

namespace Jd\Callback\Notify;


use Jd\Kernel\Container;
use Jd\Kernel\ServiceProviderInterface;

class NotifyProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['notify'] = function ($app) {
            return new NotifyClient($app);
        };
    }
}