<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/13
 * Time: 8:40
 */

namespace app\AppFactory\Mobile;


use app\AppFactory\Kernel\Providers\Mobile\MachineProvider;
use app\AppFactory\Kernel\ServiceContainer;

/**
 * Class Application
 * @property Machine\CheckClient                           $machineCheck    设备检查
 * @property Machine\InfoClient                           $machineInfo    设备信息
 * @package app\AppFactory\Mobile
 */
class Application extends ServiceContainer
{
    protected $providers = [
        MachineProvider::class,
    ];
}