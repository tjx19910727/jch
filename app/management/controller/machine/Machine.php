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
        $where = $this->getWhere($postData, false, ["version" => "like","machine_name" => "like"]);
        //只取vending_machine_type为1的设备，即主柜设备
        $where[] = ['vending_machine_type', '=', 1];
        if (!empty($machineIds)) $where[] = ['machine_id', 'in',$machineIds];
        return $this->app->machine->getMList($where,$pageNum,$this->field,"online asc, m_id desc");
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
                $typeList = [1 => "sleep", 2 => "wakeUp"];
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
        $machine = $this->app->machine->getMachineFind(['machine_id' => $machine_id],'*');
        if(!$machine) return $this->app->machine->rFail($this->app->machine->lang("VMachine.machine_not_exist"));
        if ($machine['recycle_box_total_capacity'] == 0) {
            $result = $this->app->machine->sendToMachine(['machine_id' => $machine_id], "checkRecycleBox", []);
            return is_object($result) ? $result : $this->app->machine->rFail($this->app->machine->lang("VMachine." . $result));
        }
        return $machine;
        
    }

    public function setPickUpDoorOpen(){
        $machine_id = input("machine_id");
        $result = $this->app->machine->sendToMachine(['machine_id' => $machine_id], "pickUpDoorOpen", []);
        return is_object($result) ? $result : $this->app->machine->rFail($this->app->machine->lang("VMachine." . $result));
    }

    public function setPickUpDoorClose(){
        $machine_id = input("machine_id");
        $result = $this->app->machine->sendToMachine(['machine_id' => $machine_id], "pickUpDoorClose", []);
        return is_object($result) ? $result : $this->app->machine->rFail($this->app->machine->lang("VMachine." . $result));
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
    
}