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
use app\AppFactory\Management\RemoteActionLog\RemoteActionLogClient;

class RemoteActionLogProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['remoteActionLog'] = function ($app) {
            return new RemoteActionLogClient($app);
        };
    }
                                                                                                                                                                                                                                                                                        }