<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/2/24
 * Time: 10:09
 */

namespace app\AppFactory\Kernel\Model\Machine;


use app\AppFactory\Kernel\Model\BaseModel;
use app\AppFactory\Kernel\Model\Machine\MachineModel;

class MachineVersionPlanModel extends BaseModel
{
    protected $pk = "mvp_id";
    protected $name = "machine_version_plan";

    /**
     * 关联设备表，便于 with 查询获取 machine 信息（例如 machine_name）
     * @return \think\Model
     */
    public function machine()
    {
        // local key m_id 对应 devices 表的主键 m_id
        return $this->hasOne(MachineModel::class, 'm_id', 'm_id');
    }
}