<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/6/18
 * Time: 16:27
 */

namespace app\AppFactory\Management\Config;


use app\AppFactory\Kernel\Traits\Config\ConfigApiTrait;
use app\AppFactory\Management\ManagementClient;

class ConfigApiClient extends ManagementClient
{
    use ConfigApiTrait;
}