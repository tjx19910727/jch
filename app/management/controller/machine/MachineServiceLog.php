<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/5/4
 * Time: 10:03
 */

namespace app\management\controller\machine;

use app\management\controller\Common;
use app\management\validate\Machine\VMachineServiceLog;

class MachineServiceLog extends Common
{
    protected $field = 'id,m_id,machine_id,name,path,date,remark,create_time';

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, [
            'machine_id' => 'like',
            'name' => 'like',
            'date' => 'between',
        ]);
        return $this->app->machineServiceLog->rQ(
            $this->app->machineServiceLog->getMachineServiceLogList($where, $pageNum, $this->field, 'id desc')
        );
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->machineServiceLog->rQ(
            $this->app->machineServiceLog->getMachineServiceLogFind($where, $this->field)
        );
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, VMachineServiceLog::class . '.add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineServiceLog->addLog($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, VMachineServiceLog::class . '.update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineServiceLog->updateLog($postData);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, VMachineServiceLog::class . '.del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineServiceLog->delLog($postData);
    }

    /**
     * 获取设备运行日志（按日期触发设备上传）
     */
    public function getMachineServiceLog()
    {
        $postData = input();
        try {
            $this->validate($postData, VMachineServiceLog::class . '.getMachineServiceLog');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineServiceLog->getMachineServiceLogByDate($postData);
    }
}
