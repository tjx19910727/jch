<?php
/**
 * Created by VSCode.
 * User: Alex-jixiang
 * Date: 2025/12/09
 * Time: 10:57
 */

namespace app\AppFactory\Kernel\Providers\Management;


use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\Management\WeiCheng\WcGoodsClient;
use app\AppFactory\Management\WeiCheng\WcGoodsTypesClient;
use app\AppFactory\Management\WeiCheng\WcRequestLogsClient;

class WeiChengProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['WcGoods'] = function ($app) {
            return new WcGoodsClient($app);
        };
        $app['WcGoodsTypes'] = function ($app) {
            return new WcGoodsTypesClient($app);
        };
        $app['WcRequestLogs '] = function ($app) {
            return new WcRequestLogsClient($app);
        };
    }
                                                                                                                                                                                                                                                                                        }