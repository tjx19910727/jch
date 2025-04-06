<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/11/9
 * Time: 16:50
 */

namespace Jd\Api;


use Jd\Api\Auth\AuthClient;
use Jd\Api\Auth\AuthProvider;
use Jd\Kernel\ServiceContainer;

/**
 * Class Application
 * @property AuthClient $auth 授权相关
 * @package Jd\Api
 */
class Application extends ServiceContainer
{
    protected $providers = [
        AuthProvider::class,
    ];
}