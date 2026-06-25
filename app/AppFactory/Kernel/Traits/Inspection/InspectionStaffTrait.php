<?php

namespace app\AppFactory\Kernel\Traits\Inspection;

use app\AppFactory\Kernel\Model\Inspection\InspectionStaffModel;

trait InspectionStaffTrait
{
    public function getInspectionStaffFind($where, $field = '*', $order = '')
    {
        return InspectionStaffModel::getFind($where, $field, $order);
    }

    public function getInspectionStaffList($where, $pageNum = 0, $field = '*', $order = 'staff_id desc')
    {
        return InspectionStaffModel::getList($where, $pageNum, $field, $order);
    }

    public function addInspectionStaff($insert)
    {
        $data = InspectionStaffModel::create($insert);
        return $data->staff_id;
    }

    public function updateInspectionStaff($update, $where)
    {
        return InspectionStaffModel::where($where)->update($update);
    }

    public function delInspectionStaff($where)
    {
        return InspectionStaffModel::whereDel($where);
    }

    public function countInspectionStaff($where)
    {
        return InspectionStaffModel::where($where)->count();
    }
}
