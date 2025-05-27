<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 11:52
 */

namespace app\AppFactory\Management\Config;


use app\AppFactory\Kernel\Traits\Config\ConfigSceneTrait;
use app\AppFactory\Management\ManagementClient;

class ConfigSceneClient extends ManagementClient
{
    use ConfigSceneTrait;
}