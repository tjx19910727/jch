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
use app\AppFactory\Management\Card\CardActivationClient;
use app\AppFactory\Management\Card\CardClient;
use app\AppFactory\Management\Card\CardPointsChangeLogsClient;

class CardProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['card'] = function ($app) {
            return new CardClient($app);
        };
        $app['cardActivation'] = function ($app) {
            return new CardActivationClient($app);
        };
    }
}