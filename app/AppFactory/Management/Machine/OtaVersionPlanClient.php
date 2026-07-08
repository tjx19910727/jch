<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/6/23
 * Time: 10:00
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Traits\Machine\OtaVersionPlanTrait;
use app\AppFactory\Kernel\Traits\Machine\OtaVersionTrait;
use app\AppFactory\Management\ManagementClient;
use think\facade\Cache;

class OtaVersionPlanClient extends ManagementClient
{
    use MachineTrait, OtaVersionTrait, OtaVersionPlanTrait;

    /**
     * 批量发布OTA更新计划
     * @param $postData
     * @return array|string
     */
    public function morePlan($postData)
    {
        $ov = $this->getOtaVersionFind(['ov_id' => $postData['ov_id']], "ov_id,version_no,path,size,desc")->toArray();
        if (!$ov) return $this->rFail("查无OTA固件信息");
        $this->startTrans();
        try {
            $m_id = explode(",", $postData['m_id']);
            $flag = [];
            foreach ($m_id as $v) {
                $machine = $this->getMachineFind(['m_id' => $v], "m_id,machine_id")->toArray();
                $insert = array_merge($ov, $machine);
                $insert['publish_time'] = $postData['publish_time'] ?? time();
                $flag[] = $this->addOtaVersionPlan($insert);
                // 发送触发OTA软件更新
                $this->sendToMachine(['machine_id' => $insert['machine_id']],'updateOtaVersionPlan');
            }
            return $this->checkTrans($this->checkFlag($flag));
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 获取设备OTA版本信息
     * 每设备每2分钟只能请求一次，下发getOtaVersionPlan的mq
     * @param $postData
     * @return array|string
     */
    public function getOtaVersionPlan($postData)
    {
        $mId = $postData['m_id'] ?? 0;
        if ($mId <= 0) {
            return $this->rFail('设备不存在');
        }
        $machineId = $this->getMachineValue(['m_id' => $mId], 'machine_id');
        if (!$machineId) {
            return $this->rFail('设备不存在');
        }

        // 每设备每2分钟只能请求一次
        $cacheKey = 'ota_version_plan:' . $machineId;
        if (Cache::get($cacheKey)) {
            return $this->rFail($this->lang('VOtaVersionPlan.ota_version_frequency'));
        }

        $machine = [
            'm_id' => $mId,
            'machine_id' => $machineId,
        ];

        $sendResult = $this->sendToMachine($machine, 'getOtaVersionPlan', [
            'm_id' => $mId,
        ]);

        if (is_string($sendResult)) {
            return $this->rFail($sendResult);
        }

        if (is_array($sendResult) && isset($sendResult['state']) && $sendResult['state'] != 200) {
            return $sendResult;
        }

        // 设置2分钟缓存
        Cache::set($cacheKey, 1, 120);

        return returnState(200, $this->lang('VOtaVersionPlan.ota_version_send_success'));
    }
}
