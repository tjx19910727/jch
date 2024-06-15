<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/2/24
 * Time: 10:15
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\AppFactory;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineVersionPlanTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineVersionTrait;
use app\AppFactory\Management\ManagementClient;

class MachineVersionPlanClient extends ManagementClient
{
    use MachineTrait, MachineVersionTrait,MachineVersionPlanTrait;

    public function morePlan($postData)
    {
        $mv = $this->getMachineVersionFind(['mv_id' => $postData['mv_id']],"mv_id,version_no,path,size,desc")->toArray();
        if (!$mv) return $this->rFail("查无设备软件信息");
        $this->startTrans();
        try {
            $m_id = explode(",", $postData['m_id']);
            $flag = [];
            foreach ($m_id as $v) {
                $machine = $this->getMachineFind(['m_id' => $v], "m_id,machine_id,version original_version")->toArray();
                $insert = array_merge($mv, $machine);
                $insert['publish_time'] = $postData['publish_time'] ?? time();
                $flag[] = $this->addMachineVersionPlan($insert);
                $this->sendPlanToMachine($machine);
            }
            return $this->checkTrans($this->checkFlag($flag));
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e,1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 发送触发货道更新数据
     * @param $machine
     */
    public function sendPlanToMachine($machine)
    {
        $config = [
            "machine_id" => $machine['machine_id'],
            "key" => env("api.md5Key"),
        ];
        $app = AppFactory::machine($config);
        $app->sendMq->triggerUpdateVersion();
    }
}