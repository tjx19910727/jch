<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/6/24
 */

namespace app\AppFactory\Kernel\Traits\Template;


use app\AppFactory\Kernel\Model\Template\MachineVoiceTemplateModel;
use app\AppFactory\Kernel\Model\Template\MachineVoiceDetailModel;

trait MachineVoiceTemplateTrait
{
    public function getVoiceTemplateFind($where, $field = "*", $order = "")
    {
        return MachineVoiceTemplateModel::getFind($where, $field, $order);
    }

    public function getVoiceTemplateList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "")
    {
        return MachineVoiceTemplateModel::getList($where, $pageNum, $field, $order, $eachFun);
    }

    public function addVoiceTemplate($insert)
    {
        if (!isset($insert['manager_id']) && isset($this->manager['manager_id'])) {
            $insert['manager_id'] = $this->manager['manager_id'];
        }
        $data = MachineVoiceTemplateModel::create($insert);
        return $data->id;
    }

    public function updateVoiceTemplate($update, $where = [], $field = [])
    {
        return MachineVoiceTemplateModel::update($update, $where, $field);
    }

    public function delVoiceTemplate($where)
    {
        return MachineVoiceTemplateModel::whereDel($where);
    }

    public function getVoiceDetailList($where, $pageNum = 0, $field = "*", $order = "voice_id asc")
    {
        return MachineVoiceDetailModel::getList($where, $pageNum, $field, $order);
    }

    public function getVoiceDetailColumn($where, $column)
    {
        return MachineVoiceDetailModel::getColumn($where, $column);
    }

    public function getVoiceDetailCount($where)
    {
        return MachineVoiceDetailModel::getCount($where);
    }

    public function delVoiceDetail($where)
    {
        return MachineVoiceDetailModel::whereDel($where);
    }

    public function addVoiceDetailMore($insertAll)
    {
        if (!$insertAll) return true;
        return MachineVoiceDetailModel::insertAll($insertAll);
    }
}
