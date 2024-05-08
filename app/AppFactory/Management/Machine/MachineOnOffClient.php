<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/8
 * Time: 9:51
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Kernel\Traits\Machine\MachineOnOffTrait;
use app\AppFactory\Management\ManagementClient;

class MachineOnOffClient extends ManagementClient
{
    use MachineOnOffTrait;
}