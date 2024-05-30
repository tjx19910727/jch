<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/2/24
 * Time: 10:02
 */

namespace app\management\controller\machine;


use app\management\controller\Common;
use app\management\validate\Machine\VMachineVersionPlan;

class MachineVersionPlan extends Common
{

    protected $field = "*";

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ["version_no" => "like","machine_id" => "like"]);
        return $this->app->machineVersionPlan->getList($where,$pageNum,$this->field,'mvp_id desc');
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->machineVersionPlan->getFind($where,$this->field);
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, VMachineVersionPlan::class . '.add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineVersionPlan->morePlan($postData);
    }

    public function update()
    {
        $postData = input();
        if (!isset($postData['mvp_id']) || !$postData['mvp_id']) return returnValidate("请选择计划ID");
        if (!isset($postData['status']) ||  !in_array($postData['status'],[1,4])) return returnValidate("状态类型错误，不允许操作");
        return $this->app->machineVersionPlan->update($postData,[],["status"]);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, VMachineVersionPlan::class . '.del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineVersionPlan->del($postData);
    }
}