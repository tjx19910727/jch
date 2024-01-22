<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/20
 * Time: 10:57
 */

namespace app\AppFactory\Kernel\Providers\Management;


use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\Management\Goods\GoodsCategoryClient;
use app\AppFactory\Management\Goods\GoodsCategoryLangClient;
use app\AppFactory\Management\Goods\GoodsClient;
use app\AppFactory\Management\Goods\GoodsLangClient;

class GoodsProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['goods'] = function ($app) {
            return new GoodsClient($app);
        };
        $app['goodsCategory'] = function ($app) {
            return new GoodsCategoryClient($app);
        };
        $app['goodsCategoryLang'] = function ($app) {
            return new GoodsCategoryLangClient($app);
        };
        $app['goodsLang'] = function ($app) {
            return new GoodsLangClient($app);
        };
    }
}