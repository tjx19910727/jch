<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/4/30
 * Time: 14:14
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Kernel\Traits\Machine\MachineErrorCodeTrait;
use app\AppFactory\Management\ManagementClient;

class MachineErrorCodeClient extends ManagementClient
{
    use MachineErrorCodeTrait;

}