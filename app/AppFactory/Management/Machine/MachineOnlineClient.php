<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/2/24
 * Time: 8:55
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Kernel\Traits\Machine\MachineOnlineTrait;
use app\AppFactory\Management\ManagementClient;

class MachineOnlineClient extends ManagementClient
{
    use MachineOnlineTrait;
}