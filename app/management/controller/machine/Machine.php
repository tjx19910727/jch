<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:50
 */

namespace app\management\controller\machine;


use app\AppFactory\AppFactory;
use app\management\controller\Common;
use app\management\validate\Machine\VMachine;

class Machine extends Common
{

    protected $field = "*";
    protected $validatePath = VMachine::class;

    public function getList()
    {
        $postData = input();
        $machineIds = [];
        if (isset($postData['machine_group_id']) && $postData['machine_group_id']) {
            $machineIds = $this->app->machine->getMachineGroupMgColumn(['mg_id' => $postData['machine_group_id']],'machine_id');
            unset($postData['machine_group_id']);
            if (!$machineIds) return $this->app->machine->rNoData();
        }
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ["version" => "like","machine_name" => "like"]);
        if ($machineIds) $where[] = ['machine_id', 'in',$machineIds];
        return $this->app->machine->getMList($where,$pageNum,$this->field,"machine_id desc");
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->machine->getMFind($where,$this->field);
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machine->addM($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machine->updateM($postData);
    }

    public function updateMore()
    {
        $postData = input();
        return $this->app->machine->updateMore($postData);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machine->delM($postData['m_id']);
    }

    /**
     * 导出设备
     * @return array|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function exportMachine()
    {
        $postData = input();
        if (isset($postData['lang'])) unset($postData['lang']);
        $machineIds = [];
        if (isset($postData['machine_group_id']) && $postData['machine_group_id']) {
            $machineIds = $this->app->machine->getMachineGroupMgColumn(['mg_id' => $postData['machine_group_id']],'machine_id');
            unset($postData['machine_group_id']);
            if (!$machineIds) return $this->app->machine->rNoData();
        }
        $where = $this->getWhere($postData, false, ["version" => "like","machine_name" => "like"]);
        if ($machineIds) $where[] = ['machine_id', 'in',$machineIds];
        $field = "m_id,machine_id,machine_name,country_id,state_id,city_id,regions_id,street,floor,version,factory,inventory_location,
        (case online when 1 then '" . lang("online") . "' else '" . lang("offline"). "' END) online,
        FROM_UNIXTIME(last_online_time) last_online_time,
        (case device_type when 1 then '" . lang("vending_machine") . "' else '" . lang("store") . "' end) device_type,
        (case machine_level when 1 then '" . lang("simplified_version") . "' else '" . lang("luxury_edition") . "' END) machine_level,
        (case status when 1 then '" . lang("normal") . "' when 2 then '" . lang("disable") . "' when 3 then '" . lang("maintenance") . "' end) status";
        return $this->app->machine->exportM($where,$field,"machine_id desc");
    }

    /**
     * 发送主体控制命令
     * @return array|string
     */
    public function sendMainControl()
    {
        try {
            $postData = input();
            $otherData = ["time_point" => (isset($postData['time_point']) && $postData['time_point'] ? strtotime($postData['time_point']) : time())];
            if (isset($postData['msgType']) && is_int($postData['msgType'])) {
                $typeList = [1 => "sleep", 2 => "wakeUp", 3 => "reboot", 4 => "shutdown", 5 => "update"];
                $postData['msgType'] = $typeList[$postData['msgType']];
            }
            $result = $this->app->machine->sendToMachine($postData, $postData['msgType'], $otherData);
            return is_object($result) ? $result : $this->app->machine->rFail($this->app->machine->lang("VMachine." . $result));
        } catch (\Exception $e) {
            actionException($e,1);
            return $this->app->machine->rTryCatch($e->getMessage());
        }
    }

    /**
     * 设置灯光亮度
     * @return array|string
     */
    public function setLight()
    {
        $machine_id = input("machine_id");
        $light = input("light");
        if (!$machine_id) return returnValidate(lang("VMachine.machine_id_require"));
        if (!$light) return returnValidate(lang("VMachine.light_require"));
        if ($light%10 != 0) return returnValidate(lang("VMachine.light_multiple"));
        $otherData  = ["value" => $light];
        $result = $this->app->machine->sendToMachine(['machine_id' => $machine_id],"light",$otherData);
        return is_object($result) ? $result : $this->app->machine->rFail($this->app->machine->lang("VMachine." . $result));
    }

    /**
     * 设置音量
     * @return array|string
     */
    public function setVolume()
    {
        $machine_id = input("machine_id");
        $volume = input("volume");
        if (!$machine_id) return returnValidate(lang("VMachine.machine_id_require"));
        if (!$volume) return returnValidate(lang("VMachine.volume_require"));
        if ($volume%10 != 0) return returnValidate(lang("VMachine.volume_multiple"));
        $otherData  = ["value" => $volume];
        $result = $this->app->machine->sendToMachine(['machine_id' => $machine_id],"volume",$otherData);
        return is_object($result) ? $result : $this->app->machine->rFail($this->app->machine->lang("VMachine." . $result));
    }

    /**
     * 批量发送主体控制指令
     * @return array|string
     */
    public function sendAllControl()
    {
        try {
            $postData = input();
            $otherData = ["time_point" => (isset($postData['time_point']) && $postData['time_point'] ? strtotime($postData['time_point']) : time())];
            if (isset($postData['msgType']) && is_int($postData['msgType'])) {
                $typeList = [1 => "sleep", 2 => "wakeUp", 3 => "reboot", 4 => "shutdown", 5 => "update"];
                $postData['msgType'] = $typeList[$postData['msgType']];
            }
            $postData['machine_id'] = explode(',',$postData['machine_id']);
            $result = $this->app->machine->sendToArrMachine($postData, $postData['msgType'], $otherData);
            if(!$result) $this->app->machine->rFail($this->app->machine->lang("VMachine." . $result));
            $result = $this->app->machine->sendToArrMachine($postData,"light",$otherData);
            return is_object($result) ? $result : $this->app->machine->rFail($this->app->machine->lang("VMachine." . $result));
        } catch (\Exception $e) {
            actionException($e,1);
            return $this->app->machine->rTryCatch($e->getMessage());
        }
    }

    /**
     * 获取设备开锁密码
     * @return string
     */
    public function getOpenPass(){

    }
}