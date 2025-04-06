<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:42
 */

namespace app\AppFactory\Management\Machine;


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
                $update['total_flow'] = $result['packagemsg']['total'];
                $update['remain_flow'] = $result['packagemsg']['allowance'];
                $uResult = $this->updateMachineInfo($update,['mi_id' => $postData['mi_id']]);
                if (!$uResult) return $this->rFail($this->lang("update_fail"));
                return $this->r(200,$this->lang("query_success"),$update);
            } else {
                return $this->rFail($result['resultmsg'] ?? '');
            }
        }
        return $this->rFail();
    }
}