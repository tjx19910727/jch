<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/8/5
 * Time: 10:48
 */
namespace Jd\Kernel\Providers;

use Jd\Kernel\Config;
use Jd\Kernel\Container;
use Jd\Kernel\ServiceProviderInterface;

class ConfigProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['config'] = function ($app) {
            return new Config($app->getConfig());
        };
    }
}