<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/6/18
 * Time: 15:23
 */

namespace app\AppFactory\Api;


use app\AppFactory\Kernel\Providers\Api\SendProvider;
use app\AppFactory\Kernel\Providers\Api\V2Provider;
use app\AppFactory\Kernel\ServiceContainer;

/**
 * Class Application
 * @property V2\V2Client               $v2       V2接口
 * @property Send\CallbackClient       $callback     发送通知回调接口
 * @package app\AppFactory\Api
 */
class Application extends ServiceContainer
{
    protected $providers = [
        V2Provider::class,
        SendProvider::class,
    ];
}