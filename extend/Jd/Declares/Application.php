<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/11/10
 * Time: 10:46
 */

namespace Jd\Declares;


use Jd\Declares\Customer\CustomerClient;
use Jd\Declares\Customer\CustomerProvider;
use Jd\Declares\Settle\SettleClient;
use Jd\Declares\Settle\SettleProvider;
use Jd\Declares\Shop\ShopClient;
use Jd\Declares\Shop\ShopProvider;
use Jd\Kernel\ServiceContainer;

/**
 * Class Application
 * @property CustomerClient $customer  商户
 * @property SettleClient   $settle    结算
 * @property ShopClient     $shop      店铺
 * @package Jd\Declares
 */
class Application extends ServiceContainer
{
    protected $providers = [
        CustomerProvider::class,
        SettleProvider::class,
        ShopProvider::class,
    ];
}