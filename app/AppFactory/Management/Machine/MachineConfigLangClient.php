<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2025/5/15
 * Time: 15:13
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Kernel\Traits\Machine\MachineConfigLangTrait;
use app\AppFactory\Management\ManagementClient;

class MachineConfigLangClient extends ManagementClient
{
    use MachineConfigLangTrait;
}