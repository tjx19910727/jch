<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 16:25
 */

namespace app\management\controller\machine;


use app\AppFactory\AppFactory;
use app\AppFactory\Kernel\Support\SimiotService\Simiot;
use app\management\controller\Common;
use app\management\validate\Machine\VMachineInfo;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersTrait;

class MachineInfo extends Common
{
    use SaleOrdersTrait;

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
        if(!empty($info['iccid'])){
            //获取流量池数据进行覆盖
            $pool_result = Simiot::queryPool($info['iccid']);
            $info['remain_flow'] = $pool_result['result'][0]['traffic_left'] ?? 0;
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
        if (!in_array($field,["screen_img","camera_img","exchange_img","remote_refund_goods"])) return returnState(100,lang("query_out_range"));
        if (!$machine_id) return returnState(100,lang("VMachineInfo.machine_id_require"));
        $send = "";
        $n = 0;
        while(1) {
            //远程退货图片
            if($field == "remote_refund_goods"){
                $sod_id = input('sod_id') ?? '';
                $sod = $this->getSaleOrdersDetailsFind(['sod_id' => $sod_id])->toArray();
                if ($sod['refund_photo']) {
                    return returnState(200,lang("query_success"),$sod['refund_photo']);
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
                $this->app->machine->sendToMachine(['machine_id' => $machine_id],"img",["field" => $field]);
                $send = 1;
            }
            sleep(1);
            $n++;
            if ($n >= 50) {
                return returnState(300,lang("action_machine_overtime"));
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