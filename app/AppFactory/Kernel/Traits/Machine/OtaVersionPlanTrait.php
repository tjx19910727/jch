<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/6/23
 * Time: 10:00
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Machine\OtaVersionPlanModel;

trait OtaVersionPlanTrait
{
    public function getOtaVersionPlanFind($where, $field = "*", $order = "")
    {
        return OtaVersionPlanModel::getFind($where, $field, $order);
    }

    public function getOtaVersionPlanList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "")
    {
        return OtaVersionPlanModel::getList($where, $pageNum, $field, $order, $eachFun);
    }

    public function getOtaVersionPlanWithMachineNameList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "")
    {
        return OtaVersionPlanModel::getListAndWith($where, $pageNum, $field, $order, $eachFun, '', 0, ['machine']);
    }

    public function addOtaVersionPlan($insert)
    {
        !isset($this->manager['manager_id']) ? : $insert['creator'] = $this->manager['manager_id'];
        $data = OtaVersionPlanModel::create($insert);
        return $data->ovp_id;
    }

    public function updateOtaVersionPlan($update, $where = [], $field = [])
    {
        return OtaVersionPlanModel::update($update, $where, $field);
    }

    public function delOtaVersionPlan($where)
    {
        $result = OtaVersionPlanModel::whereDel($where);
        return $result;
    }
}
