<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/11/10
 * Time: 11:19
 */

namespace Jd\Declares\Attach;


use Jd\Kernel\Container;
use Jd\Kernel\ServiceProviderInterface;

class AttachProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['attach'] = function ($app) {
            return new AttachClient($app);
        };
    }
}