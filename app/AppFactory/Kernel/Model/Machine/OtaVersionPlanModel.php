<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/6/23
 * Time: 10:00
 */

namespace app\AppFactory\Kernel\Model\Machine;


use app\AppFactory\Kernel\Model\BaseModel;
use app\AppFactory\Kernel\Model\Machine\MachineModel;

class OtaVersionPlanModel extends BaseModel
{
    protected $pk = "ovp_id";
    protected $name = "ota_version_plan";

    /**
     * 关联设备表，便于 with 查询获取 machine 信息（例如 machine_name）
     * @return \think\Model
     */
    public function machine()
    {
        return $this->hasOne(MachineModel::class, 'm_id', 'm_id');
    }
}
