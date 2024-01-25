<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/30
 * Time: 17:40
 */

namespace app\AppFactory\Management\Strategy;


use app\AppFactory\Kernel\Traits\Strategy\StrategyMachineTrait;
use app\AppFactory\Management\ManagementClient;

class StrategyMachineClient extends ManagementClient
{
    use StrategyMachineTrait;
}