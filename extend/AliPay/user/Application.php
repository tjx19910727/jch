<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/10/22
 * Time: 17:26
 */

namespace AliPay\user;


use AliPay\Kernel\ServiceContainer;

/**
 * Class Application
 * @property InfoClient $info 用户信息
 * @package AliPay\user
 */
class Application extends ServiceContainer
{
    protected $providers = [
        InfoProvider::class,
    ];
}