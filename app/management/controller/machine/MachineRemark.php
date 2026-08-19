<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/8/19
 * Time: 10:00
 */

namespace app\management\controller\machine;

use app\management\controller\Common;
use app\management\validate\Machine\VMachineRemark;

class MachineRemark extends Common
{
    protected $field = 'id,m_id,machine_id,type,remark,creator,create_time,update_time';
    protected $validatePath = VMachineRemark::class;

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        // 未传 type 时默认查询离线备注（type=1）
        if (!isset($postData['type']) || $postData['type'] === '') {
            $postData['type'] = 1;
        }
        $where = $this->getWhere($postData, false, [
            'machine_id' => 'like',
            'remark' => 'like',
            'create_time' => 'between',
        ]);
        // 设备查看权限过滤（超管不限制）
        $permitted = $this->app->machine->resolvePermittedMachineIds();
        if ($permitted !== null) {
            $where[] = ['m_id', 'in', $permitted];
        }
        return $this->app->machineRemark->rQ(
            $this->app->machineRemark->getMachineRemarkList($where, $pageNum, $this->field, 'id desc')
        );
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->machineRemark->rQ(
            $this->app->machineRemark->getMachineRemarkFind($where, $this->field)
        );
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineRemark->addRemark($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineRemark->updateRemark($postData);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineRemark->delRemark($postData);
    }
}
