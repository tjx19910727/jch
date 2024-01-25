<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/30
 * Time: 17:39
 */

namespace app\AppFactory\Management\Strategy;


use app\AppFactory\Kernel\Traits\Strategy\StrategyPayeeTrait;
use app\AppFactory\Management\ManagementClient;

class StrategyPayeeClient extends ManagementClient
{
    use StrategyPayeeTrait;
}