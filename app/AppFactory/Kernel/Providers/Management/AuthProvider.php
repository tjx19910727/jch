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
use app\AppFactory\Management\Auth\AuthManagerClient;
use app\AppFactory\Management\Auth\AuthManagerMachineClient;
use app\AppFactory\Management\Auth\AuthManagerRoleClient;
use app\AppFactory\Management\Auth\AuthNodeClient;
use app\AppFactory\Management\Auth\AuthOrganizationClient;
use app\AppFactory\Management\Auth\AuthOrganizationRoleClient;
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
        $app['authManagerRole'] = function ($app) {
            return new AuthManagerRoleClient($app);
        };
        $app['authManagerMachine'] = function ($app) {
            return new AuthManagerMachineClient($app);
        };
        $app['authNode'] = function ($app) {
            return new AuthNodeClient($app);
        };
        $app['authOrganization'] = function ($app) {
            return new AuthOrganizationClient($app);
        };
        $app['authOrganizationRole'] = function ($app) {
            return new AuthOrganizationRoleClient($app);
        };
        $app['authRole'] = function ($app) {
            return new AuthRoleClient($app);
        };
        $app['authRoleNode'] = function ($app) {
            return new AuthRoleNodeClient($app);
        };
    }
}