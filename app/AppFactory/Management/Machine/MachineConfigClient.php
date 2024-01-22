<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:42
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Kernel\Traits\Machine\MachineConfigTrait;
use app\AppFactory\Management\ManagementClient;

class MachineConfigClient extends ManagementClient
{
    use MachineConfigTrait;
}