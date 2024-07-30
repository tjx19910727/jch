<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/27
 * Time: 16:50
 */

namespace app\AppFactory\Kernel\Providers\Management;


use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\Management\Export\ExportLogClient;

class ExportProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['exportLog'] = function ($app) {
            return new ExportLogClient($app);
        };
    }
}