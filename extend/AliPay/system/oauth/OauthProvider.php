<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/10/22
 * Time: 14:15
 */

namespace AliPay\system\oauth;


use AliPay\Kernel\Container;
use AliPay\Kernel\ServiceProviderInterface;

class OauthProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['oauth'] = function ($app) {
            return new OauthClient($app);
        };
    }
}