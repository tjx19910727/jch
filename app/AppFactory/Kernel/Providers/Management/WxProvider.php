<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/3
 * Time: 10:11
 */

namespace app\AppFactory\Kernel\Providers\Management;


use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\Management\Wx\WxOfficialClient;
use app\AppFactory\Management\Wx\WxTemplateClient;
use app\AppFactory\Management\Wx\WxTemplateLogClient;

class WxProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['wxOfficial'] = function ($app) {
            return new WxOfficialClient($app);
        };
        $app['wxTemplate'] = function ($app) {
            return new WxTemplateClient($app);
        };
        $app['wxTemplateLog'] = function ($app) {
            return new WxTemplateLogClient($app);
        };
    }
}