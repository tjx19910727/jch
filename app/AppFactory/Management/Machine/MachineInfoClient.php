<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:42
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Kernel\Traits\Machine\MachineInfoTrait;
use app\AppFactory\Management\ManagementClient;

class MachineInfoClient extends ManagementClient
{
    use MachineInfoTrait;
}