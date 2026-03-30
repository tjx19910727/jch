<?php

/**
 * 卡激活活动相关数据访问
 */

namespace app\AppFactory\Kernel\Traits\Card;

use app\AppFactory\Kernel\Model\Card\ActivityCardActivationDetailModel;
use app\AppFactory\Kernel\Model\Card\ActivityCardActivationModel;

trait CardActivationTrait
{
    public function getActivityCardActivationFind($where, $field = "*", $order = "id desc")
    {
        return ActivityCardActivationModel::getFind($where, $field, $order);
    }

    public function getActivityCardActivationList($where, $pageNum = 0, $field = "*", $order = "id desc", $eachFun = "", $group = "")
    {
        return ActivityCardActivationModel::getList($where, $pageNum, $field, $order, $eachFun, $group);
    }

    public function getActivityCardActivationColumn($where, $column, $key = "")
    {
        return ActivityCardActivationModel::getColumn($where, $column, $key);
    }

    public function addActivityCardActivation($insert)
    {
        $insert['creator'] = ($this->manager['manager_id'] ?? 0);
        $insert['ao_id'] = ($insert['ao_id'] ?? ($this->manager['ao_id'] ?? 0));
        $data = ActivityCardActivationModel::create($insert);
        return $data->id;
    }

    public function updateActivityCardActivation($update, $where = [], $field = [])
    {
        return ActivityCardActivationModel::update($update, $where, $field);
    }

    public function delActivityCardActivation($where)
    {
        return ActivityCardActivationModel::whereDel($where);
    }

    public function getActivityCardActivationDetailFind($where, $field = "*", $order = "acd_id desc")
    {
        return ActivityCardActivationDetailModel::getFind($where, $field, $order);
    }

    public function getActivityCardActivationDetailList($where, $pageNum = 0, $field = "*", $order = "acd_id desc", $eachFun = "", $group = "")
    {
        return ActivityCardActivationDetailModel::getList($where, $pageNum, $field, $order, $eachFun, $group);
    }

    public function getActivityCardActivationDetailColumn($where, $column, $key = "")
    {
        return ActivityCardActivationDetailModel::getColumn($where, $column, $key);
    }

    public function getActivityCardActivationDetailCount($where)
    {
        return ActivityCardActivationDetailModel::getCount($where);
    }

    public function addActivityCardActivationDetail($insert)
    {
        $data = ActivityCardActivationDetailModel::create($insert);
        return $data->acd_id;
    }

    public function addActivityCardActivationDetailMore($insertAll)
    {
        return ActivityCardActivationDetailModel::insertAll($insertAll);
    }

    public function updateActivityCardActivationDetail($update, $where = [], $field = [])
    {
        return ActivityCardActivationDetailModel::update($update, $where, $field);
    }

    public function delActivityCardActivationDetail($where)
    {
        return ActivityCardActivationDetailModel::whereDel($where);
    }
}
