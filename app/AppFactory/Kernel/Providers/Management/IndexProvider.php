<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/15
 * Time: 9:33
 */

namespace app\AppFactory\Kernel\Providers\Management;


use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\Management\Index\CargoDamageDataClient;
use app\AppFactory\Management\Index\SaleDataClient;
use app\AppFactory\Management\Index\StoreDataClient;
use app\AppFactory\Management\Index\TodoClient;

class IndexProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['saleData'] = function ($app) {
            return new SaleDataClient($app);
        };
        $app['storeData'] = function ($app) {
            return new StoreDataClient($app);
        };
        $app['cargoDamageData'] = function ($app) {
            return new CargoDamageDataClient($app);
        };
        $app['todo'] = function ($app) {
            return new TodoClient($app);
        };
    }
}