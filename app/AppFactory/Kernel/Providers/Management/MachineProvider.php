<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/18
 * Time: 9:41
 */

namespace app\AppFactory\Kernel\Providers\Management;


use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\Management\Machine\MachineChannelClient;
use app\AppFactory\Management\Machine\MachineClient;
use app\AppFactory\Management\Machine\MachineConfigClient;
use app\AppFactory\Management\Machine\MachineGoodsClient;
use app\AppFactory\Management\Machine\MachineGroupClient;
use app\AppFactory\Management\Machine\MachineGroupLangClient;
use app\AppFactory\Management\Machine\MachineHelpClient;
use app\AppFactory\Management\Machine\MachineInfoClient;
use app\AppFactory\Management\Machine\MachineViewClient;

class MachineProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['machineChannel'] = function ($app) {
            return new MachineChannelClient($app);
        };
        $app['machine'] = function ($app) {
            return new MachineClient($app);
        };
        $app['machineConfig'] = function ($app) {
            return new MachineConfigClient($app);
        };
        $app['machineGoods'] = function ($app) {
            return new MachineGoodsClient($app);
        };
        $app['machineGroup'] = function ($app) {
            return new MachineGroupClient($app);
        };
        $app['machineGroupLang'] = function ($app) {
            return new MachineGroupLangClient($app);
        };
        $app['machineHelp'] = function ($app) {
            return new MachineHelpClient($app);
        };
        $app['machineInfo'] = function ($app) {
            return new MachineInfoClient($app);
        };
        $app['machineView'] = function ($app) {
            return new MachineViewClient($app);
        };
    }
}