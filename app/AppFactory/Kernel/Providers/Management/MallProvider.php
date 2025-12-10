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
use app\AppFactory\Management\Mall\MallClient;
use app\AppFactory\Management\Mall\MallMachineClient;
use app\AppFactory\Management\Mall\MallRequestLogsClient;

class MallProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['mall'] = function ($app) {
            return new MallClient($app);
        };
        $app['mallMachine'] = function ($app) {
            return new MallMachineClient($app);
        };
        $app['mallRequestLogs'] = function ($app) {
            return new MallRequestLogsClient($app);
        };
    }
                                                                                                                                                                                                                                                                                        }