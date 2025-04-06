<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/4
 * Time: 17:50
 */

namespace app\AppFactory\Kernel\Providers\Management;


use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\Management\Config\ConfigApiClient;
use app\AppFactory\Management\Config\ConfigClient;
use app\AppFactory\Management\Config\ConfigLangClient;
use app\AppFactory\Management\Config\ConfigPerformanceClient;
use app\AppFactory\Management\Config\ConfigSceneClient;
use app\AppFactory\Management\Config\ConfigSizeClient;

class ConfigProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['config'] = function ($app) {
            return new ConfigClient($app);
        };
        $app['configApi'] = function ($app) {
            return new ConfigApiClient($app);
        };
        $app['configSize'] = function ($app) {
            return new ConfigSizeClient($app);
        };
        $app['configLang'] = function ($app) {
            return new ConfigLangClient($app);
        };
        $app['configPerformance'] = function ($app) {
            return new ConfigPerformanceClient($app);
        };
        $app['configScene'] = function ($app) {
            return new ConfigSceneClient($app);
        };
    }
}