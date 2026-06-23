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
}
