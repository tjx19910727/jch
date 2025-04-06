<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/10
 * Time: 14:33
 */

namespace app\AppFactory\Management\Strategy;


use app\AppFactory\Kernel\Traits\Strategy\StrategyIncomeTrait;
use app\AppFactory\Management\ManagementClient;

class StrategyIncomeClient extends ManagementClient
{
    use StrategyIncomeTrait;
}