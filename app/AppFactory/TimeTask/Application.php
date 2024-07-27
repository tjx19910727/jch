<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/29
 * Time: 14:02
 */

namespace app\AppFactory\TimeTask;

use app\AppFactory\Kernel\Providers\TimeTask\AuthManagerProvider;
use app\AppFactory\Kernel\Providers\TimeTask\GoodsProvider;
use app\AppFactory\Kernel\Providers\TimeTask\MachineProvider;
use app\AppFactory\Kernel\ServiceContainer;
use app\AppFactory\TimeTask\Pay\PayProvider;

/**
 * Class Application
 * @property AuthManager\AuthManagerLogClient           $log                   用户事件定时任务
 * @property Machine\MachineClient                      $machine               设备定时任务
 * @property Machine\MachineChannelStockClient          $machineChannelStock   设备库存报表定时任务
 * @property Goods\GoodsClient                          $goods                 商品定时任务
 * @property Pay\MicroPayClient                         $microPay              把势支付
 * @package app\AppFactory\TimeTask
 */
class Application extends ServiceContainer
{
    protected $providers = [
        MachineProvider::class,
        GoodsProvider::class,
        AuthManagerProvider::class,
        PayProvider::class,
    ];
}