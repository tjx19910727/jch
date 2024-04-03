<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/18
 * Time: 9:40
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Kernel\Traits\Machine\MachineGroupLangTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineGroupMgTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineGroupTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Management\ManagementClient;

class MachineGroupClient extends ManagementClient
{
    use MachineTrait,MachineGroupMgTrait;
    use MachineGroupTrait,MachineGroupLangTrait;

    public function addMg($postData)
    {
        $m_id = 0;
        if (isset($postData['m_id']) && $postData['m_id']) {
            $m_id = array_unique(explode(",",$postData['m_id']));
            unset($postData['m_id']);
        }
        $this->startTrans();
        $mg_id = $this->addMachineGroup($postData);
        if ($mg_id) {
            $insertLang = [
                "mg_id" => $mg_id,
                "mg_name" => $postData['mg_name'],
                "desc" => $postData['desc'],
                "lang" => "zh-cn",
            ];
            $this->addMachineGroupLang($insertLang);
            if ($m_id) {
                if (is_int($m_id)) $m_id  = [$m_id];
                $mg = $this->getMachineGroupFind(['mg_id' => $mg_id], 'mg_id,mg_name');
                $mg = $mg->toArray();
                foreach ($m_id as $mk => $mv) {
                    $m = $this->getMachineFind(['m_id' => $mv], 'm_id,machine_id,machine_name');
                    if (!$m) {
                        return $this->r(100, $this->lang("VMachine.machine_no_data"));
                    }
                    $m = $m->toArray();
                    $insertAll[] = array_merge($mg, $m);
                }
                $flag[] = $this->addMachineGroupMgMore($insertAll);
            }
            $this->commitTrans();
            return $this->r(200,$this->lang("add_success"));
        }
        $this->rollbackTrans();
        return $this->r(100,$this->lang("add_fail"));
    }

    public function updateMg($postData)
    {
        try {
            $m_id = [];
            if (isset($postData['m_id']) && $postData['m_id']) {
                $m_id = array_unique(explode(",", $postData['m_id']));
                unset($postData['m_id']);
            }
            $this->updateMachineGroup($postData);
            $mg = $this->getMachineGroupFind(['mg_id' => $postData['mg_id']], 'mg_id,mg_name');
            if (!$mg) {
                return $this->r(100, $this->lang("VMachineGoods.mg_no_data"));
            }
            $mg = $mg->toArray();
            if ($m_id && is_int($m_id)) $m_id = [$m_id];
            $oldMid = $this->getMachineGroupMgColumn(['mg_id' => $postData['mg_id']], "m_id");
            $addList = array_diff($m_id, $oldMid);
            $delList = array_diff($oldMid, $m_id);
            $flag = [];
            if ($delList) $flag[] = $this->delMachineGroupMg(['mg_id' => $postData['mg_id'], ['m_id', 'in', $delList]]);
            if ($addList) {
                foreach ($addList as $mk => $mv) {
                    $m = $this->getMachineFind(['m_id' => $mv], 'm_id,machine_id,machine_name');
                    if (!$m) {
                        return $this->r(100, $this->lang("VMachine.machine_no_data"));
                    }
                    $m = $m->toArray();
                    $insertAll[] = array_merge($mg, $m);
                }
                $this->addMachineGroupMgMore($insertAll);
            }
            return $this->r(200, $this->lang("update_success"));
        } catch (\Exception $e) {
            actionException($e,1);
            return $this->rValidate($e->getMessage());
        }
    }
}