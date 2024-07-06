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
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Management\ManagementClient;
use app\management\validate\Machine\VMachineConfig;

class MachineConfigClient extends ManagementClient
{
    use MachineTrait,MachineConfigTrait;
    use AuthManagerTrait;

    public function updateMc($postData)
    {
        $result = $this->updateMachineConfig($postData);
        if ($result) {
            $mc = $this->getMachineConfigFind(['mc_id' => $postData['mc_id']],'machine_id');
            $mc = $mc->toArray();
            $this->sendToMachine(['machine_id' => $mc['machine_id']],'updateMachineConfig');
        }
        return $this->rU($result);
    }

    public function updateMoreMc($postData)
    {
        $this->startTrans();
        try {
            foreach ($postData['mcList'] as $key => $value) {
                validate(VMachineConfig::class)->scene("mcList")->check($value);
                $result = $this->updateMachineConfig($value, ['m_id' => $value['m_id']]);
                if ($result) {
                    $mc = $this->getMachineConfigFind(['m_id' => $value['m_id']], "machine_id");
                    $mc = $mc->toArray();
                    $this->sendToMachine(['machine_id' => $mc['machine_id']],'updateMachineConfig');
                } else {
                    $this->rollbackTrans();
                    return $this->r(100, $this->lang("update_fail"), $value);
                }
            }
            $this->commitTrans();
            return $this->r(200, $this->lang("update_success"));
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e,1);
            return $this->r(100,$this->lang($e->getMessage()));
        }
    }

}