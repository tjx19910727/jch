<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/9
 * Time: 11:16
 */

namespace app\AppFactory\Management\Config;


use app\AppFactory\Kernel\Traits\Config\ConfigSizeTrait;
use app\AppFactory\Management\ManagementClient;

class ConfigSizeClient extends ManagementClient
{
    use ConfigSizeTrait;
}