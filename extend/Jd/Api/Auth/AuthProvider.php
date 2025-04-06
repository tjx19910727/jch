<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/11/9
 * Time: 16:51
 */

namespace Jd\Api\Auth;


use Jd\Kernel\Container;
use Jd\Kernel\ServiceProviderInterface;

class AuthProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['auth'] = function ($app) {
            return new AuthClient($app);
        };
    }
}