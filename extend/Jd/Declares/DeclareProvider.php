<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/11/10
 * Time: 11:43
 */

namespace Jd\Declares;


use Jd\Kernel\Container;
use Jd\Kernel\ServiceProviderInterface;

class DeclareProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['declare'] = function ($app) {
            return new DeclareClient($app);
        };
    }
}