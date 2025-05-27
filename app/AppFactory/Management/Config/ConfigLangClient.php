<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/16
 * Time: 17:05
 */

namespace app\AppFactory\Management\Config;


use app\AppFactory\Kernel\Traits\Config\ConfigLangTrait;
use app\AppFactory\Management\ManagementClient;

class ConfigLangClient extends ManagementClient
{
    use ConfigLangTrait;
}