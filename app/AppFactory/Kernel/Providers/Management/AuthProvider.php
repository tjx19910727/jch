<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/19
 * Time: 16:20
 */

namespace app\AppFactory\Kernel\Providers\Management;


use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\Management\Auth\AuthManagerBankClient;
use app\AppFactory\Management\Auth\AuthManagerClient;
use app\AppFactory\Management\Auth\AuthManagerNoticeClient;
use app\AppFactory\Management\Auth\AuthManagerRoleClient;
use app\AppFactory\Management\Auth\AuthManagerWithdrawalClient;
use app\AppFactory\Management\Auth\AuthNodeClient;
use app\AppFactory\Management\Auth\AuthRoleClient;
use app\AppFactory\Management\Auth\AuthRoleNodeClient;

class AuthProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['authManager'] = function ($app) {
            return new AuthManagerClient($app);
        };
        $app['authManagerBank'] = function ($app) {
            return new AuthManagerBankClient($app);
        };
        $app['authManagerNotice'] = function ($app) {
            return new AuthManagerNoticeClient($app);
        };
        $app['authManagerRole'] = function ($app) {
            return new AuthManagerRoleClient($app);
        };
        $app['authManagerWithdrawal'] = function ($app) {
            return new AuthManagerWithdrawalClient($app);
        };
        $app['authNode'] = function ($app) {
            return new AuthNodeClient($app);
        };
        $app['authRole'] = function ($app) {
            return new AuthRoleClient($app);
        };
        $app['authRoleNode'] = function ($app) {
            return new AuthRoleNodeClient($app);
        };
    }
}