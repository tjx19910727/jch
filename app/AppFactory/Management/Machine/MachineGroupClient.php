<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/18
 * Time: 9:40
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Kernel\Traits\Machine\MachineGroupTrait;
use app\AppFactory\Management\ManagementClient;

class MachineGroupClient extends ManagementClient
{
    use MachineGroupTrait;
}