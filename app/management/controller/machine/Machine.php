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
use app\AppFactory\Kernel\Traits\Machine\MachineErrorCodeTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersTrait;

use app\AppFactory\Kernel\Traits\Payment\AfterOrderPaymentTrait;
class Machine extends Common
{
    use MachineErrorCodeTrait,SaleOrdersTrait,AfterOrderPaymentTrait;

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
        $order = $this->buildMachineListOrder($postData);
        unset($postData['version_sort'],$postData['stock_ratio']);
        $where = $this->getWhere($postData, false, ["version" => "like","machine_name" => "like"]);
        //只取vending_machine_type为1的设备，即主柜设备
        $where[] = ['vending_machine_type', '=', 1];//vending_machine_type字段已废弃，入库默认值为1，代码层面涉及此字段的不用管
        if (!empty($machineIds)) $where[] = ['machine_id', 'in',$machineIds];
        return $this->app->machine->getMList($where,$pageNum,$this->field,$order);
    }

    private function buildMachineListOrder($postData)
    {
        $orderList = [];
        if (!empty($postData['version_sort'])) {
            $versionDirection = $postData['version_sort'] == 1 ? 'asc' : 'desc';
            $orderList[] = "version {$versionDirection}";
        }
        if (!empty($postData['stock_ratio'])) {
            $stockRatioDirection = $postData['stock_ratio'] == 1 ? 'asc' : 'desc';
            $orderList[] = "(SELECT IF(SUM(capacity) > 0, SUM(stock) / SUM(capacity), 0) FROM machine_channel WHERE m_id = a.m_id AND status <> 2) {$stockRatioDirection}";
        }
        $orderList[] = 'online asc';
        $orderList[] = 'm_id desc';
        return implode(', ', $orderList);
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

    /**
     * 设置单个设备在营状态
     * @return array|string
     */
    public function setOperating()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.setOperating');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machine->setOperating($postData);
    }

    /**
     * 批量设置设备在营状态
     * @return array|string
     */
    public function setOperatingBatch()
    {
        $postData = input();
        return $this->app->machine->setOperatingBatch($postData);
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
        $field = "m_id,machine_id,machine_name,ao_id,country_id,state_id,city_id,regions_id,street,floor,version,factory,inventory_location,
        IFNULL((SELECT GROUP_CONCAT(DISTINCT mg.mg_name ORDER BY mg.id SEPARATOR ',') FROM machine_group_mg mg WHERE mg.m_id = a.m_id),'') machine_group_name,
        (case online when 1 then '" . lang("online") . "' else '" . lang("offline"). "' END) online,
        FROM_UNIXTIME(last_online_time) last_online_time,
        (case device_type when 1 then '" . lang("vending_machine") . "' else '" . lang("store") . "' end) device_type,
        (case machine_level when 1 then '" . lang("simplified_version") . "' else '" . lang("luxury_edition") . "' END) machine_level,
    (case is_operating when 1 then '在营' else '停营' END) is_operating,
        (case status when 1 then '" . lang("normal") . "' when 2 then '" . lang("disable") . "' when 3 then '" . lang("maintenance") . "' end) status";
        //只取vending_machine_type为1的设备，即主柜设备
        $where[] = ['vending_machine_type', '=', 1];
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
                $typeList = [1 => "sleep", 2 => "wakeUp", 3 => "reboot", 4 => "shutdown", 5 => "update", 6=> "powerWakeUp", 7=>"initialization"];
                $postData['msgType'] = $typeList[$postData['msgType']];
            }
            if(!empty($postData['powerTime'])) {
                $Mchtime = explode(',',$postData['powerTime']);
                $Mchtime[0] = !empty($Mchtime[0])?strtotime($Mchtime[0]):0;
                $Mchtime[1] = !empty($Mchtime[1])?strtotime($Mchtime[1]):0;
                $otherData['powerTime'] = $Mchtime[0]+$Mchtime[1];
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
     * 设置设备暂停营业 setMachineCkcOnOff
     * @return array|string
     */
    public function setMachineCkcOnOff()
    {
        //这里严谨一点需要验证是否在开机时间范围内、营业时间范围内、逻辑复杂，暂时不这么搞，后续有需求再改
        $machine_id = input("machine_id");
        $ckc_status = input("ckc_status");
        if (!$machine_id) return returnValidate(lang("VMachine.machine_id_require"));
        if (!$ckc_status) return returnValidate(lang("VMachine.ckc_status_require"));
        $otherData  = ["ckc_status" => $ckc_status];
        $result = $this->app->machine->sendToMachine(['machine_id' => $machine_id], "machineCkcOnOff", $otherData);
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
            $lightArr = ["time_point" => (isset($postData['time_point']) && $postData['time_point'] ? strtotime($postData['time_point']) : time())];
            if (isset($postData['msgType']) && is_int($postData['msgType'])) {
                $typeList = [1 => "sleep", 2 => "wakeUp",3 => "machineCkcOnOff"];
                $postData['msgType'] = $typeList[$postData['msgType']];
            }
            $postData['machine_id'] = explode(',',$postData['machine_id']);
            $result = $this->app->machine->sendToArrMachine($postData, $postData['msgType'], $otherData);
            if(!$result) $this->app->machine->rFail($this->app->machine->lang("VMachine." . $result));
            if($postData['msgType'] == 'sleep') $lightArr = ['value' => 0];
            else $lightArr = ['value' => 100];
            $result = $this->app->machine->sendToArrMachine($postData,"light",$lightArr);
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
        $getData = input();
        try{
            return returnData($this->app->machine->getPass($getData['machine_id']));
        } catch (\Exception $e){
            return $this->app->machine->rFail('获取失败');
        }
    }

    /**
     * 远程动作 doorOpen powerWakeUp initialization axisOffset
     * @return array|string
     */
    public function remoteAction()
    {
        $machine_id = input("machine_id");
        $type = input('type');
        $otherData = input("otherData") ?? [];
        if (!$machine_id) return returnValidate(lang("VMachine.machine_id_require"));
        if ($type == 'axisOffset'){
            if(!$otherData['x_axis'] && !$otherData['y_axis']) return returnValidate(lang("VMachine.x_y_axis_require"));
        }
        $result = $this->app->machine->sendToMachine(['machine_id' => $machine_id], $type, $otherData);
        return is_object($result) ? $result : $this->app->machine->rFail($this->app->machine->lang("VMachine." . $result));
    }

    /**
     * 远程出货
     * @return array|string
     */
    public function remoteOutGoods(){
        $postData = input();
        $result = $this->app->machine->setRemoteOutGoods($postData);
        return is_object($result) ? $result : $this->app->machine->rFail($this->app->machine->lang("VMachine." . $result));
    }


    // 远程退货动作组 获取回收箱当前数量、打开出料箱门、关闭出料箱门、拍照上传、回收商品 checkRecycleBox、pickUpDoorOpen、pickUpDoorClose、takePhotos、recycGoods
    /**
     * 远程退货动作组 获取回收箱当前数量、剩余数量
     * @return array|string
     */
    public function getRecycleBoxInfo(){
        $machine_id = input("machine_id");
        if (!$machine_id) return returnValidate(lang("VMachine.machine_id_require"));
        $send = 0;
        $n = 0;
        while (1) {
            $machine = $this->app->machine->getMachineFind(
                ['machine_id' => $machine_id],
                'machine_id,recycle_box_total_capacity,recycle_box_remain_capacity'
            );
            if (!$machine) return $this->app->machine->rFail($this->app->machine->lang("VMachine.machine_no_data"));
            if ($machine['recycle_box_remain_capacity'] != '-1') {
                return returnState(200, lang("query_success"), $machine);
            }
            if (!$send) {
                $this->app->machine->sendToMachine(['machine_id' => $machine_id], "checkRecycleBox", []);
                $send = 1;
            }
            sleep(1);
            $n++;
            if ($n >= 20) {
                return returnState(300, lang("VMachine.get_recycle_box_overtime"));
            }
        }
    }

    public function setPickUpDoorOpen(){
        $machine_id = input("machine_id");
        return $this->waitRemoteActionLogResult($machine_id, "pickUpDoorOpen");
    }

    public function setPickUpDoorClose(){
        $machine_id = input("machine_id");
        return $this->waitRemoteActionLogResult($machine_id, "pickUpDoorClose");
    }

    // public function remoteTakePhotos(){
    //     $sod_id = input('sod_id');
    //     $machine_id = input("machine_id");
    //     $refund_photo = $this->getSaleOrdersDetailsColumn(['sod_id' => $sod_id], 'refund_photo');
    //     if (!$refund_photo) {
    //         $result = $this->app->machine->sendToMachine(['machine_id' => $machine_id], "takePhotos", ['sod_id' => $sod_id]);
    //         return is_object($result) ? returnState(200,'正在从机器端获取拍照文件，请稍做等待后下载',$result) :
    //         $this->app->machine->rFail($this->app->machine->lang("VMachine." . $result));
    //     }
    //     return returnState(200,'查询成功',$refund_photo);
    // }

    public function getRecycGoods(){
        $machine_id = input("machine_id");
        $sod_id = input("sod_id");
        if (!$machine_id) return returnValidate(lang("VMachine.machine_id_require"));
        $result = $this->app->machine->sendToMachine(['machine_id' => $machine_id], "recycGoods", ['sod_id' => $sod_id]);
        return is_object($result) ? $result : $this->app->machine->rFail($this->app->machine->lang("VMachine." . $result));
    }

    /**
     * 通过 remote_action_log 等待设备动作回执。
     * 下发前先创建日志，设备回执后按 log_id 更新 status，再轮询该日志状态返回结果。
     * @param string $machine_id
     * @param string $msgType
     * @return array|string
     */
    protected function waitRemoteActionLogResult($machine_id, $msgType)
    {
        if (!$machine_id) return returnValidate(lang("VMachine.machine_id_require"));
        $logId = $this->app->machine->addRALog([
            'machine_id' => $machine_id,
            'type' => $msgType,
            'status' => 1,
            'manager_id' => $this->manager['manager_id'] ?? 0,
            'operator_at' => date('Y-m-d H:i:s'),
        ]);
        $result = $this->app->machine->sendToMachine(['machine_id' => $machine_id], $msgType, ['log_id' => $logId]);
        if (!is_object($result)) {
            $this->app->machine->updateRALog(
                ['status' => 4, 'operator_at' => date('Y-m-d H:i:s')],
                ['id' => $logId],
                ['status', 'operator_at']
            );
            $msg = $result ? $this->app->machine->lang("VMachine." . $result) : $this->app->machine->lang("VMachine.machine_no_data");
            return $this->app->machine->rFail($msg);
        }

        $n = 0;
        $overtime = 20;
        while (1) {
            $log = $this->app->machine->getRALogsFind(['id' => $logId], 'id,machine_id,type,status,operator_at');
            if ($log) {
                $log = is_object($log) ? $log->toArray() : $log;
                if (intval($log['status']) === 3) {
                    return returnState(200, lang("query_success"), $log);
                }
                if (intval($log['status']) === 4) {
                    return returnState(100, lang("action_fail"), $log);
                }
            }
            sleep(1);
            $n++;
            if ($n >= $overtime) {
                return returnState(300, lang("VMachine.pick_up_door_overtime"), ['log_id' => $logId]);
            }
        }
    }
    
    public function exportEmptyChannel(){
        $postData = input();
        $where = $this->getWhere($postData, false, ["version" => "like","machine_name" => "like"]);
        return $this->app->machineChannel->exportEmptyList($where);
    }
    public function exportBadChannel(){
        $postData = input();
        $where = $this->getWhere($postData, false, ["version" => "like","machine_name" => "like"]);
        return $this->app->machineChannel->exportBadList($where);
    }
    public function exportStockOutChannel(){
        $postData = input();
        $where = $this->getWhere($postData, false, ["version" => "like","machine_name" => "like"]);
        return $this->app->machineChannel->exportStockOutList($where);
    }

    /**
     * 导出设备货道库存明细
     * @return array|string
     */
    public function exportStockRatio()
    {
        $mId = input('m_id');
        if (!$mId) {
            return returnValidate(lang("VMachine.machine_id_require"));
        }
        return $this->app->machineChannel->exportStockRatioByMachine($mId);
    }
}