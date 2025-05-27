<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/20
 * Time: 14:41
 */

namespace app\AppFactory\Kernel\Providers\Management;


use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\Management\Template\TemplateClient;
use app\AppFactory\Management\Template\TemplateLayoutClient;
use app\AppFactory\Management\Template\TemplatePluginsClient;
use app\AppFactory\Management\Template\TemplateViewClient;

class TemplateProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['template'] = function ($app) {
            return new TemplateClient($app);
        };
        $app['templateLayout'] = function ($app) {
            return new TemplateLayoutClient($app);
        };
        $app['templateView'] = function ($app) {
            return new TemplateViewClient($app);
        };
        $app['templatePlugins'] = function ($app) {
            return new TemplatePluginsClient($app);
        };
    }
}