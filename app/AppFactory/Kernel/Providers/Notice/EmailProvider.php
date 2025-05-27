<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/2
 * Time: 9:33
 */

namespace app\AppFactory\Kernel\Providers\Notice;


use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\Notice\Email\EmailClient;

class EmailProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['email'] = function ($app) {
            return new EmailClient($app);
        };
    }
}