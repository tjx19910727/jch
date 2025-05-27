<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/11/10
 * Time: 10:48
 */

namespace Jd\Declares\Customer;


use Jd\Kernel\Container;
use Jd\Kernel\ServiceProviderInterface;

class CustomerProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['customer'] = function ($app) {
            return new CustomerClient($app);
        };
    }
}