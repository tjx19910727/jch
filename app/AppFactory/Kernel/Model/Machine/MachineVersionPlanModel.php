<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/2/24
 * Time: 10:09
 */

namespace app\AppFactory\Kernel\Model\Machine;


use app\AppFactory\Kernel\Model\BaseModel;

class MachineVersionPlanModel extends BaseModel
{
    protected $pk = "mvp_id";
    protected $name = "machine_version_plan";
}