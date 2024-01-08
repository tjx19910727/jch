<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/4
 * Time: 17:48
 */

namespace app\AppFactory\Management\Config;


use app\AppFactory\Kernel\Traits\Config\ConfigTrait;
use app\AppFactory\Management\ManagementClient;

class ConfigClient extends ManagementClient
{
    use ConfigTrait;
}