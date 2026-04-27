<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 16:25
 */

namespace app\management\controller\machine;


use app\AppFactory\Kernel\Support\SimiotService\Simiot;
use app\management\controller\Common;
use app\management\validate\Machine\VMachineInfo;

class MachineInfo extends Common
{
    protected $field = "*";
    protected $validatePath = VMachineInfo::class;

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, []);
        return $this->app->machineInfo->getList($where,$pageNum,$this->field);
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        $info = $this->app->machineInfo->getFind($where);
        if (is_object($info) && method_exists($info, 'getData')) {
            $payload = $info->getData();
            $machineInfo = $payload['data'] ?? [];
            // 获取流量池数据进行覆盖
            $pool_result = Simiot::queryPool();
            $machineInfo['remain_flow'] = $pool_result['result'][0]['traffic_left'] ?? 0;
            return returnState($payload['state'] ?? 200, $payload['msg'] ?? lang('query_success'), $machineInfo);
        }
        return $info;
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineInfo->add($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineInfo->update($postData);
    }

    public function updateMoreMi()
    {
        $postData = input();
        return $this->app->machineInfo->updateMore($postData);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineInfo->del($postData);
    }

    /** 
     * 获取设备实时图片
     * @return array|string
     */
    public function getImg()
    {
        $field = input('field');
        $machine_id = input('machine_id');
        $sodId = intval(input('sod_id'));
        if (!in_array($field,["screen_img","camera_img","exchange_img","remote_refund_goods"])) return returnState(100,lang("query_out_range"));
        if (!$machine_id) return returnState(100,lang("VMachineInfo.machine_id_require"));
        $send = "";
        $n = 0;
        if($field == "remote_refund_goods"){
            $logId = $this->app->machine->addRALog([
                'machine_id' => $machine_id,
                'type' => 'remote_refund_goods_img',
                'status' => 1,
                'manager_id' => $this->manager['manager_id'] ?? 0,
                'operator_at' => date('Y-m-d H:i:s'),
            ]);
            $result = $this->app->machine->sendToMachine(['machine_id' => $machine_id], 'img', ['log_id' => $logId, 'field' => $field]);
            if (!is_object($result)) {
                $this->app->machine->updateRALog(
                    ['status' => 4, 'operator_at' => date('Y-m-d H:i:s')],
                    ['id' => $logId],
                    ['status', 'operator_at']
                );
                $msg = $result ? $this->app->machine->lang("VMachine." . $result) : $this->app->machine->lang("VMachine.machine_no_data");
                return $this->app->machine->rFail($msg);
            }
            // 已经下发过一次获取远程退货图片的指令，标记为已发送，避免在循环里再次下发
            $send = 1;
        }
        
        while(1) {  
            //远程退货图片
            if($field == "remote_refund_goods"){
                $log = $this->app->machine->getRALogsFind(['id' => $logId], 'id,machine_id,type,status,field,operator_at');
                if ($log) {
                    $log = is_object($log) ? $log->toArray() : $log;
                    if (intval($log['status']) == 3 || !empty($log['field'])) {
                        return returnState(200, lang("query_success"), $log);
                    }
                    if (intval($log['status']) == 4) {
                        return returnState(100, lang("action_fail"), $log);
                    }
                }
            }else{
                $shotImg = $this->app->machineInfo->getMachineInfoValue(['machine_id' => $machine_id],$field);
                if ($shotImg) {
                    $this->app->machineInfo->updateMachineInfo([$field => ""],['machine_id' => $machine_id]);
                    return returnState(200,lang("query_success"),$shotImg);
                }
            }
            if (!$send) {
                // 下发获取首页截屏、设备内部照片、出货箱照片
                $content = ["field" => $field];
                if ($field == "remote_refund_goods") {
                    if ($logId) {
                        $content['log_id'] = $logId;
                    }
                    if ($sodId) {
                        $content['sod_id'] = $sodId;
                    }
                }
                $this->app->machine->sendToMachine(['machine_id' => $machine_id],"img",$content);
                $send = 1;
            }
            sleep(1);
            $n++;
            if ($n >= 20) {
                return returnState(300,lang("VMachineInfo.get_img_overtime"));
            }
        }
    }

    /**
     * 刷新物联网卡流量
     * @return array|\think\response\Json
     */
    public function refreshSim()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.refreshSim');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineInfo->refreshSim($postData);
    }

    /**
     * 下发获取中控电脑数据
     * @return array|string
     */
    public function refreshComputer()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.refreshComputer');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        $n = 0;
        $send = 0;
        $machine_id = $postData['machine_id'];
        $now = time();
        $overtime = 50;
        while(1){
            // 终端在50秒内没有上报
            if (!$this->app->machine->getMachineMqRecordFind(['machine_id' => $machine_id,'path' => "uploadInfo","type" => 1, "from" => 2,["create_time","between",[$now,$now + $overtime]]],'mr_id')) {
                if (!$send) {
                    $result = $this->app->machine->sendToMachine(['machine_id' => $machine_id], 'getComputerInfo');
                    actionLog($result, '下发获取中控电脑数据命令结果');
                    $send = 1;
                }
                sleep(1);
                $n++;
                if ($n >= $overtime) {
                    return returnState(100, lang("VMachineInfo.get_computer_overtime"));
                }
            } else {
                return $this->app->machineInfo->getFind(['mi_id' => $postData['mi_id']], 'mi_id,cpu_utility,cpu_temperature,memory_usage,disk_occupancy');
            }
        }
        return returnState(100,lang("query_fail"));
    }
}
