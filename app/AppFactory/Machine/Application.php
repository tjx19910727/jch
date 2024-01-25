<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/22
 * Time: 20:11
 */

namespace app\AppFactory\Machine;


use app\AppFactory\Kernel\Providers\Machine\ReceiveProvider;
use app\AppFactory\Kernel\ServiceContainer;

/**
 * 设备上报请求与下发通讯管理
 * @property Receive\ApiClient               $api            API接口上报
 * @property Receive\SetClient               $set            设置上报
 * @property Receive\SocketClient            $report         报告上报
 * @package app\AppFactory\Machine
 */
class Application extends ServiceContainer
{
    protected $providers = [
        ReceiveProvider::class,
    ];
}