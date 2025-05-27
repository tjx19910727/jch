<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/4
 * Time: 10:23
 */

namespace app\AppFactory\Kernel\Providers\Management;


use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\Management\Email\EmailConfigClient;
use app\AppFactory\Management\Email\EmailTemplateClient;
use app\AppFactory\Management\Email\EmailTemplateLogClient;

class EmailProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['emailConfig'] = function ($app) {
            return new EmailConfigClient($app);
        };
        $app['emailTemplate'] = function ($app) {
            return new EmailTemplateClient($app);
        };
        $app['emailTemplateLog'] = function ($app) {
            return new EmailTemplateLogClient($app);
        };
    }
}