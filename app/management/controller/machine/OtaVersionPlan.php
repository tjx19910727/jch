<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/6/23
 * Time: 10:00
 */

namespace app\management\controller\machine;


use app\management\controller\Common;
use app\management\validate\Machine\VOtaVersionPlan;

class OtaVersionPlan extends Common
{

    protected $field = "*";

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ["version_no" => "like", "machine_id" => "like"]);
        $machineIds = $this->app->authManagerMachine->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], 'machine_id');
        if ($machineIds) $where[] = ['machine_id', 'in', $machineIds];
        return returnData($this->app->otaVersionPlan->getOtaVersionPlanWithMachineNameList($where, $pageNum, $this->field, 'ovp_id desc'));
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->otaVersionPlan->getFind($where, $this->field);
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, VOtaVersionPlan::class . '.add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->otaVersionPlan->morePlan($postData);
    }

    public function update()
    {
        $postData = input();
        if (!isset($postData['ovp_id']) || !$postData['ovp_id']) return returnValidate("请选择计划ID");
        if (!isset($postData['status']) || !in_array($postData['status'], [1, 5])) return returnValidate("状态类型错误，不允许操作");
        return $this->app->otaVersionPlan->update($postData, [], ["status"]);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, VOtaVersionPlan::class . '.del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->otaVersionPlan->del($postData);
    }
}
