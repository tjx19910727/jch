<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/23
 * Time: 19:44
 */

namespace app\AppFactory\Kernel\Providers\Management;


use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\Management\Strategy\StrategyIncomeClient;
use app\AppFactory\Management\Strategy\StrategyMachineClient;
use app\AppFactory\Management\Strategy\StrategyManagerClient;
use app\AppFactory\Management\Strategy\StrategyPayeeClient;

class StrategyProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['strategyIncome'] = function ($app) {
            return new StrategyIncomeClient($app);
        };
        $app['strategyMachine'] = function ($app) {
            return new StrategyMachineClient($app);
        };
        $app['strategyManager'] = function ($app) {
            return new StrategyManagerClient($app);
        };
        $app['strategyPayee'] = function ($app) {
            return new StrategyPayeeClient($app);
        };
    }
}