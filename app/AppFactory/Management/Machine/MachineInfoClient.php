<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:42
 */

namespace app\AppFactory\Management\Machine;

use app\AppFactory\Kernel\Support\SimiotService\Simiot;
use app\AppFactory\Kernel\Support\ZdSimService\ZdSim;
use app\AppFactory\Kernel\Traits\Machine\MachineInfoTrait;
use app\AppFactory\Management\ManagementClient;
use app\management\validate\Machine\VMachineInfo;

class MachineInfoClient extends ManagementClient
{
    use MachineInfoTrait;

    public function updateMore($postData)
    {
        $this->startTrans();
        try {
            foreach ($postData['miList'] as $key => $value) {
                try {
                    validate(VMachineInfo::class)->scene("updateMore")->check($value);
                } catch (\Exception $e) {
                    $this->rollbackTrans();
                    return $this->rValidate($e->getMessage());
                }
                $flag[] = $this->updateMachineInfo($value, ['m_id' => $value['m_id']]);
            }
            $check = $this->checkFlag($flag);
            return $this->checkTrans($check);
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e,1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 刷新物联网卡流量数据
     * @param $postData
     * @return array|\think\response\Json
     */
    public function refreshSim($postData)
    {
        $result = ZdSim::queryCard($postData['iccid']);
        $result = json2arr($result);
        if ($result) {
            if ($result['resultcode'] == 0) {
                $update['iccid'] = $result['cardmsg']['iccid'];
                $update['operator'] = $result['cardmsg']['operatortype'];
                $update['valid_time'] = strtotime($result['cardmsg']['validdate']);
                foreach ($result['packagemsg'] as $key => $value) {
                    if ($value["ptype"] == "流量套餐") {
                        $update['total_flow'] = $value['total'];
                        $update['remain_flow'] = $value['allowance'];
                        break;
                    }
                }
                $uResult = $this->updateMachineInfo($update,['mi_id' => $postData['mi_id']]);
                if (!$uResult) return $this->rFail($this->lang("update_fail"));
                return $this->r(200,$this->lang("query_success"),$update);
            } else {
                return $this->getSimiotData($postData['iccid'], $postData['mi_id']);
            }
        }else{
            // 查询新物联接口
            return $this->getSimiotData($postData['iccid'], $postData['mi_id']);
        }
    }

    public function getSimiotData($iccid, $mi_id)
    {
        $result = Simiot::queryCard($iccid);
        $result = json2arr($result);
        $arr = ['china_mobile' => '中国移动', 'china_unicom' => '中国联通', 'china_telecom' => '中国电信'];
        if (isset($result['code']) && $result['code'] == 0) {
            $newResult = $result['result'][0] ?? [];
            $key = $newResult['carrier'] ?? 'china_mobile';
            $update['iccid'] = $newResult['iccid'] ?? $iccid;
            $update['operator'] = $arr[$key];
            if(isset($newResult['package'][0]['current_period_usage'])){
                $update['total_flow'] = $newResult['package'][0]['current_period_usage'];
            }
            if(!empty($newResult['package'][0]['package_capacity']) && isset($update['total_flow'])){
                $capacity_type = $newResult['package'][0]['capacity_type'] ?? 'gb';
                if($capacity_type == 'mb'){
                    $count = 1024;
                }elseif($capacity_type == 'gb'){
                    $count = 1024 * 1024;
                }else{
                    $count = 1;
                }
                $update['remain_flow'] = $newResult['package'][0]['package_capacity'] * $count - $update['total_flow'];//剩余流量
            }
            $update['valid_time'] = $newResult['package'][0]['end_time'] ?? '';
            $update['valid_time'] = $update['valid_time'] ? strtotime($update['valid_time']) : 0;
            if($update['valid_time'] > 2147483647){
                $update['valid_time'] = 2147483647;
            }
            $uResult = $this->updateMachineInfo($update,['mi_id' => $mi_id]);
            if (!$uResult){
                return $this->rFail($this->lang("update_fail"));
            } 

            return $this->r(200,$this->lang("query_success"),$update);
        } else {
            return $this->rFail($result['message'] ?? $this->lang("query_fail"));
        }
    }
}