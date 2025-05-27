<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/10/22
 * Time: 15:22
 */

namespace AliPay\fund;


use AliPay\fund\trans\TransProvider;
use AliPay\Kernel\ServiceContainer;

/**
 * Class Application
 * @property \AliPay\fund\trans\CommonClient $common 转账业务
 * @property \AliPay\fund\trans\UniClient  $uni  单笔转账
 * @property \AliPay\fund\AccountClient  $account  支付宝资金账户资产
 * @package AliPay\fund
 */
class Application extends ServiceContainer
{
    protected $providers = [
        TransProvider::class,
        FundProvider::class,
    ];
}