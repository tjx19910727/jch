<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/28
 * Time: 15:15
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Kernel\Support\Excel;
use app\AppFactory\Kernel\Traits\Machine\MachineGroupMgTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineGroupTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Management\ManagementClient;

class MachineGroupMgClient extends ManagementClient
{
    use MachineTrait,MachineGroupTrait;
    use MachineGroupMgTrait;

    public function mgBindMachine($postData)
    {
        $mg_id = $postData['mg_id'];
        $m_id = array_unique(explode(",",$postData['m_id']));
        $mg = $this->getMachineGroupFind(['mg_id' => $mg_id],'mg_id,mg_name');
        if (!$mg) {
            return $this->r(100,$this->lang("VMachineGoods.mg_no_data"));
        }
        $mg = $mg->toArray();
        $oldMid = $this->getMachineGroupMgColumn(['mg_id' => $mg_id],"m_id");
        $addList = array_diff($m_id,$oldMid);
        $delList = array_diff($oldMid,$m_id);
        $flag = [];
        if ($delList) $flag[] = $this->delMachineGroupMg(['mg_id' => $mg_id,['m_id','in',$delList]]);
        if ($addList) {
            foreach ($addList as $mk => $mv) {
                $m = $this->getMachineFind(['m_id' => $mv], 'm_id,machine_id,machine_name');
                if (!$m) {
                    return $this->r(100, $this->lang("VMachine.machine_no_data"));
                }
                $m = $m->toArray();
                $insertAll[] = array_merge($mg, $m);
            }
            $flag[] = $this->addMachineGroupMgMore($insertAll);
        }
        $result = $this->checkFlag($flag);
        return $this->checkTrans($result);
    }

    public function machineBindMg($postData)
    {
        $m_id = $postData['m_id'];
        $mg_id = array_unique(explode(",",$postData['mg_id']));
        $m = $this->getMachineFind(['m_id' => $m_id],'m_id,machine_id,machine_name');
        if (!$m) {
            return $this->r(100,$this->lang("VMachine.machine_no_data"));
        }
        $m = $m->toArray();
        $oldMgId = $this->getMachineGroupMgColumn(['m_id' => $m_id],"mg_id");
        $addList = array_diff($mg_id,$oldMgId);
        $delList = array_diff($oldMgId,$mg_id);
        $flag = [];
        if ($delList) $flag[] = $this->delMachineGroupMg(['m_id' => $m_id,['mg_id','in',$delList]]);
        if ($addList) {
            foreach ($addList as $mk => $mv) {
                $mg = $this->getMachineGroupFind(['mg_id' => $mv], 'mg_id,mg_name');
                if (!$mg) {
                    return $this->r(100, $this->lang("VMachineGoods.mg_no_data"));
                }
                $mg = $mg->toArray();
                $insertAll[] = array_merge($mg, $m);
            }
            $flag[] = $this->addMachineGroupMgMore($insertAll);
        }
        $result = $this->checkFlag($flag);
        return $this->checkTrans($result);
    }

    /**
     * 导出设备分组的设备列表信息
     * @param $where
     * @return array|string
     */
    public function exportMachine($where)
    {
        try {
            $list = $this->getMachineGroupMgList($where, 0, 'mg_name,machine_id,machine_name');
            if ($list) {
                $list = $list->toArray();
                $title = ["mg_name" => "分组名称",'machine_id' => "设备编号", "machine_name" => "设备名称"];
                $filename = "设备分组-" . date("Ymd");
                $result = Excel::exportExcel($list, $title, $filename);
                return $this->rAction($result);
            }
            return $this->r(100, $this->lang("action_fail"));
        } catch (\PHPExcel_Writer_Exception $e) {
            actionException($e,1);
            return $this->rValidate($e->getMessage());
        } catch (\PHPExcel_Exception $e) {
            actionException($e,1);
            return $this->rValidate($e->getMessage());
        }

    }
}