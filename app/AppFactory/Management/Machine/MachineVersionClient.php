<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/2/24
 * Time: 10:14
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Kernel\Traits\Machine\MachineVersionTrait;
use app\AppFactory\Management\ManagementClient;

class MachineVersionClient extends ManagementClient
{
    use MachineVersionTrait;
}