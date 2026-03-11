<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/30
 * Time: 17:33
 */

namespace app\AppFactory\Kernel\Model\Strategy;

use app\AppFactory\Kernel\Model\Auth\AuthOrganizationModel;
use app\AppFactory\Kernel\Model\BaseModel;

class StrategyPayeeModel extends BaseModel
{
    protected $pk = "sp_id";
    protected $name = "strategy_payee";

    protected $schema = [
        "sp_id" => "int",
        "sp_name" => "string",
        "title" => "string",
        "payee_type" => "int",
        "app_id" => "string",
        "mch_id" => "string",
        "content" => "string",
        "ico" => "string",
        "status" => "int",
        "ao_id" => "int",
        "creator" => "int",
        "create_time" => "int",
        "update_id" => "int",
        "update_time" => "int",
    ];

    public function aoData()
    {
        return $this->hasOne(AuthOrganizationModel::class, "ao_id", "ao_id")->field("ao_id,organization_name");
    }

}