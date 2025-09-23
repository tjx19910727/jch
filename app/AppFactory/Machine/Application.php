<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/22
 * Time: 20:11
 */

namespace app\AppFactory\Machine;


use app\AppFactory\Kernel\Providers\Machine\ReceiveProvider;
use app\AppFactory\Kernel\Providers\Machine\RobotProvider;
use app\AppFactory\Kernel\Providers\Machine\SendProvider;
use app\AppFactory\Kernel\ServiceContainer;

/**
 * 设备上报请求与下发通讯管理
 * @property Receive\ActivityClient             $activity       营销活动
 * @property Receive\ApiClient                  $api            API接口上报
 * @property Receive\MqClient                   $mq             Mq上报
 * @property Receive\SaleOrdersClient           $saleOrders     订单数据上报
 * @property Receive\HotelClient                $hotel          酒店
 * @property Receive\RobotClient                $robot          机器人相关
 *
 * @property Send\MqClient                      $sendMq         Mq数据下发
 * @package app\AppFactory\DataUpload
 */
class Application extends ServiceContainer
{
    protected $providers = [
        ReceiveProvider::class,
        SendProvider::class,
        RobotProvider::class,
    ];
}