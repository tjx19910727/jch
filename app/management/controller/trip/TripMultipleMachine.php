<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/9/10
 * Time: 10:22
 */

namespace app\management\controller\trip;


use app\management\controller\Common;
use app\management\validate\Trip\VTripMultipleMachine;

class TripMultipleMachine extends Common
{

    protected $field = "*";
    protected $validatePath = VTripMultipleMachine::class . ".";

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, []);
        return $this->app->tripMultipleMachine->getList($where, $pageNum, $this->field);
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->tripMultipleMachine->getFind($where, $this->field);
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        $check = $this->app->tripMultipleMachine->getTripMultipleMachineFind(['tm_id' => $postData['tm_id'],'m_id' => $postData['m_id']]);
        if ($check) return returnState(100,lang("VTripMultiple.m_id_unique"));
        return $this->app->tripMultipleMachine->add($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->tripMultipleMachine->update($postData,[],["machine_name"]);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->tripMultipleMachine->del($postData);
    }
}