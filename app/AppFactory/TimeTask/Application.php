<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/29
 * Time: 14:02
 */

namespace app\AppFactory\TimeTask;

use app\AppFactory\Kernel\Providers\TimeTask\GoodsProvider;
use app\AppFactory\Kernel\Providers\TimeTask\MachineProvider;
use app\AppFactory\Kernel\ServiceContainer;

/**
 * Class Application
 * @property Machine\MachineClient    $machine               设备定时任务
 * @property Goods\GoodsClient        $goods                 商品定时任务
 * @package app\AppFactory\TimeTask
 */
class Application extends ServiceContainer
{
    protected $providers = [
        MachineProvider::class,
        GoodsProvider::class,
    ];
}