<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/10/22
 * Time: 14:16
 */

namespace AliPay\system;


use AliPay\Kernel\ServiceContainer;
use AliPay\system\oauth\OauthClient;
use AliPay\system\oauth\OauthProvider;

/**
 * Class Application
 * @property OauthClient $oauth  授权
 * @package AliPay\system
 */
class Application extends ServiceContainer
{
    protected $providers = [
        OauthProvider::class,
    ];
}