<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/12
 * Time: 20:03
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Kernel\Traits\Machine\MachineChannelReplenishmentTrait;
use app\AppFactory\Management\ManagementClient;

class MachineChannelReplenishmentClient extends ManagementClient
{
    use MachineChannelReplenishmentTrait;


}