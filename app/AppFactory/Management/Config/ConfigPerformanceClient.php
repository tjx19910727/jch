<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/17
 * Time: 17:17
 */

namespace app\AppFactory\Management\Config;


use app\AppFactory\Kernel\Traits\Config\ConfigPerformanceTrait;
use app\AppFactory\Management\ManagementClient;

class ConfigPerformanceClient extends ManagementClient
{
    use ConfigPerformanceTrait;
}