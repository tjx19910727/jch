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
                if (!$check) {
                    $postData['m_id'] = $value;
                    $postData['machine_id'] = $machine_id;
                    $flag[] = $this->addMachineView($postData);
                }
            }
            return $this->checkTrans($flag);
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e,1);
            return $this->rTryCatch($e->getMessage());
        }
    }
}