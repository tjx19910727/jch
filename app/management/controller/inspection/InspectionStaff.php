<?php

namespace app\management\controller\inspection;

use app\management\controller\Common;
use app\management\validate\Inspection\VInspectionStaff;

class InspectionStaff extends Common
{
    protected $field = 'staff_id,staff_code,account_name,mobile,contact_address,expire_time,ao_id,status,remark,creator,update_id,create_time,update_time';

    public function getList()
    {
        $postData = input();
        $pageNum = intval($postData['pageNum'] ?? 0);
        $where = $this->buildWhere($postData);
        return $this->app->inspectionStaff->getList($where, $pageNum, $this->field, 'staff_id desc');
    }

    public function getFind()
    {
        $postData = input();
        if (empty($postData['staff_id'])) return returnState(100, '巡检人员ID不能为空');
        $where = $this->getWhere($postData, false, []);
        return $this->app->inspectionStaff->getFind($where, $this->field);
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, VInspectionStaff::class . '.add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->inspectionStaff->addData($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, VInspectionStaff::class . '.update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->inspectionStaff->updateData($postData);
    }

    public function del()
    {
        $staffId = input('staff_id');
        if (!$staffId) return returnState(100, '巡检人员ID不能为空');
        return $this->app->inspectionStaff->delData($staffId);
    }

    public function export()
    {
        $postData = input();
        return $this->app->inspectionStaff->export($this->buildWhere($postData));
    }

    protected function buildWhere($postData)
    {
        return $this->getWhere($postData, false, [
            'staff_code' => 'like',
            'account_name' => 'like',
            'mobile' => 'like',
            'contact_address' => 'like',
            'expire_time' => 'between',
        ]);
    }
}
