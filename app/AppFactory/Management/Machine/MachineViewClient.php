<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/20
 * Time: 15:01
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineViewTrait;
use app\AppFactory\Management\ManagementClient;

class MachineViewClient extends ManagementClient
{
    use MachineViewTrait;
    use MachineTrait;

    /**
     * 批量分配视图给设备
     * @param $postData
     * @return array|string
     */
    public function addMore($postData)
    {
        $m_ids = explode(",",$postData['m_id']);
        unset($postData['m_id']);
        $flag = [];
        $this->startTrans();
        try {
            foreach ($m_ids as $key => $value) {
                $machine_id = $this->getMachineValue(['m_id' => $value], 'machine_id');
                if (!$machine_id) return $this->rFail($this->lang("VMachineView.machine_id_query_no_data"));
                $check = $this->getMachineViewFind(['m_id' => $value, 'view_id' => $postData['view_id']]);
                actionLog($this->getLS(),'查询设备视图');
                actionLog($check ,'设备视图');
                if (!$check) {
                    $postData['m_id'] = $value;
                    $postData['machine_id'] = $machine_id;
                    $flag[] = $this->addMachineView($postData);
                    actionLog($this->getLS(),'分配视图');
                    $this->sendToMachine(['machine_id' => $machine_id],'updateMachineView');
                }
            }
            actionLog($flag);
            return $this->checkTrans($flag);
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e,1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    public function updateMv($postData)
    {
        $result = $this->updateMachineView($postData);
        if ($result) {
            $machine_id = $postData['machine_id'] ?? $this->getMachineViewValue(['mv_id' => $postData['mv_id']],'machine_id');
            $this->sendToMachine(['machine_id' => $machine_id],'updateMachineView');
            return $this->rSuccess();
        }
        return $this->rFail();
    }

    public function delMv($postData)
    {
        $machine_id = $this->getMachineViewValue(['mv_id' => $postData['mv_id']],'machine_id');
        $result = $this->delMachineView($postData);
        if ($result) {
            $this->sendToMachine(['machine_id' => $machine_id],'updateMachineView');
            return $this->rSuccess();
        }
        return $this->rFail();
    }
}