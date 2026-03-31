<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/30
 * Time: 17:41
 */

namespace app\AppFactory\Management\Strategy;


use app\AppFactory\Kernel\Traits\Strategy\StrategyManagerTrait;
use app\AppFactory\Management\ManagementClient;

class StrategyManagerClient extends ManagementClient
{
    use StrategyManagerTrait;

    public function getStrategyManagerData($where, $field = "*",$order = ""){
        return $this->getStrategyManagerFind($where, $field,$order);
    }

    public function getStrategyManagerColumnDatas($where, $field = "*",$key = ""){
        return $this->getStrategyManagerColumns($where, $field, $key);
    }
}