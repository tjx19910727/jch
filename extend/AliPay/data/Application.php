<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/10/22
 * Time: 17:17
 */

namespace AliPay\data;


use AliPay\data\bill\BalanceProvider;
use AliPay\Kernel\ServiceContainer;

/**
 * Class Application
 * @property \AliPay\data\bill\BalanceClient $balance   支付宝商家账户余额
 * @package AliPay\data
 */
class Application extends ServiceContainer
{
    protected $providers = [
        BalanceProvider::class,
    ];
}