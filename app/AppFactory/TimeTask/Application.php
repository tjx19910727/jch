<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/29
 * Time: 14:02
 */

namespace app\AppFactory\TimeTask;

use app\AppFactory\Kernel\Providers\TimeTask\ActivityProvider;
use app\AppFactory\Kernel\Providers\TimeTask\AuthManagerProvider;
use app\AppFactory\Kernel\Providers\TimeTask\ExportProvider;
use app\AppFactory\Kernel\Providers\TimeTask\GoodsProvider;
use app\AppFactory\Kernel\Providers\TimeTask\MachineProvider;
use app\AppFactory\Kernel\Providers\TimeTask\PaymentProvider;
use app\AppFactory\Kernel\ServiceContainer;

/**
 * Class Application
 * 定时任务
 * @property AuthManager\AuthManagerLogClient           $log                   用户事件定时任务
 * @property Machine\MachineClient                      $machine               设备定时任务
 * @property Machine\MachineChannelStockClient          $machineChannelStock   设备库存报表定时任务
 * @property Goods\GoodsClient                          $goods                 商品定时任务
 * @property Payment\AliClient                          $ali                   支付宝定时查询反扫付款码支付结果
 * @property Payment\WxClient                           $wx                    微信定时查询反扫付款码支付结果
 * @property Export\ExportClient                        $export                导出Excel处理
 * @property Activity\CouponClient                      $coupon                优惠券
 * @package app\AppFactory\TimeTask
 */
class Application extends ServiceContainer
{
    protected $providers = [
        MachineProvider::class,
        GoodsProvider::class,
        AuthManagerProvider::class,
        PaymentProvider::class,
        ExportProvider::class,
        ActivityProvider::class,
    ];
}