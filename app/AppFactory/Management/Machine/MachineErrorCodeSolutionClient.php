<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/30
 * Time: 16:59
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Kernel\Traits\Machine\MachineErrorCodeSolutionTrait;
use app\AppFactory\Management\ManagementClient;

class MachineErrorCodeSolutionClient extends ManagementClient
{
    use MachineErrorCodeSolutionTrait;
}