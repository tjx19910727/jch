<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/30
 * Time: 17:35
 */

namespace app\AppFactory\Kernel\Model\Strategy;


use app\AppFactory\Kernel\Model\BaseModel;

class StrategyManagerModel extends BaseModel
{
    protected $pk = "sm_id";
    protected $name = "strategy_manager";
    
    protected $schema = [
        "sm_id" => "int",
        "s_id" => "int",
        "manager_id" => "int",
        "sort" => "int",
        "s_type" => "int",
    ];
}