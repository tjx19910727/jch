<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:42
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\AppFactory;
use app\AppFactory\Kernel\Traits\Auth\AuthManagerTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineConfigTrait;
use app\AppFactory\Management\ManagementClient;

class MachineConfigClient extends ManagementClient
{
    use MachineConfigTrait;
    use AuthManagerTrait;

    public function updateMc($postData)
    {
        $result = $this->updateMachineConfig($postData);
        if ($result) {
            $mc = $this->getMachineConfigFind(['mc_id' => $postData['mc_id']],'machine_id');
            $mc = $mc->toArray();
            $this->sendToMachine($mc);
        }
        return $this->rU($result);
    }

    public function updateMoreMc($postData)
    {
        $this->startTrans();
        foreach ($postData['mcList'] as $key => $value) {
            $result = $this->updateMachineConfig($value,['m_id' => $value['m_id']]);
            if ($result) {
                $mc = $this->getMachineConfigFind(['m_id' => $value['m_id']],"machine_id");
                $mc = $mc->toArray();
                $this->sendToMachine($mc);
            } else {
                $this->rollbackTrans();
                return $this->r(100,$this->lang("update_fail"), $value);
            }
        }
        $this->commitTrans();
        return $this->r(200,$this->lang("update_success"));
    }

    /**
     * 发送触发更新数据
     * @param array $machine
     */
    public function sendToMachine($machine)
    {
        $config = [
            "machine_id" => $machine['machine_id'],
            "key" => env("api.md5Key"),
        ];
        $app = AppFactory::machine($config);
        $app->sendMq->triggerUpdateMachineConfig();
    }
}