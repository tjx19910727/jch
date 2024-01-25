<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/16
 * Time: 9:00
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Machine\MachineGroupLangModel;

trait MachineGroupLangTrait
{
    public function getMachineGroupLangFind($where,$field = "*",$order = "gcl_id desc")
    {
        return MachineGroupLangModel::getFind($where,$field,$order);
    }

    public function getMachineGroupLangList($where,$pageNum = 0, $field = "*",$order = "gcl_id desc")
    {
        return MachineGroupLangModel::getList($where,$pageNum,$field,$order);
    }

    public function addMachineGroupLang($insert)
    {
        if (isset($this->manager['manager_id']))  $insert['creator'] = $this->manager['manager_id'] ?? 0;
        $data = MachineGroupLangModel::create($insert);
        return $data->mgl_id;
    }

    public function updateMachineGroupLang($update,$where = [],$field = ["mg_name","desc","lang"])
    {
        if (isset($this->manager['manager_id'])) $update['update_id'] = $this->manager['manager_id'] ?? 0;
        return MachineGroupLangModel::update($update,$where,$field);
    }

    public function delMachineGroupLang($where)
    {
        return MachineGroupLangModel::whereDel($where);
    }
}