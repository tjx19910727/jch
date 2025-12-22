<?php
/**
 * Created by VSCode.
 * User: Alex-jixiang
 * Date: 2025/12/08
 * Time: 15:42
 */

namespace app\AppFactory\Management\Mall;


use app\AppFactory\Kernel\Traits\Mall\MallMachineTrait;
use app\AppFactory\Management\ManagementClient;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;

class MallMachineClient extends ManagementClient
{
    use MallMachineTrait, MachineTrait;

    public function getMallMachineInfoList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return $this->rQ($this->getMallMachineList($where, $pageNum, $field, $order, $eachFun, $group));
    }

    public function bind($mall_id,$m_ids)
    {
        $flag = [];
        $this->startTrans();
        try {
            $m_ids = explode(",", $m_ids);
            $flag[] = $this->updateMallMachine(['status' => 1],[[ 'm_id' ,'>', '0']]);//全部置为有效
            $old_M_ids = $this->getMallMachineColumn(['mall_id' => $mall_id], 'm_id');
            $delList = array_diff($old_M_ids, $m_ids);
            $addList = array_diff($m_ids, $old_M_ids);
            if ($delList) {
                $updateData['status'] = 2;
                $updateData['updator'] = $this->manager['manager_id'];
                $where = [['m_id', 'in', $delList]];
                $flag[] = $this->updateMallMachine($updateData, $where);
            }
            if ($addList) {
                $machines_list = $this->getMachineList([['m_id', 'in', $addList]]);
                $machines = [];
                foreach($machines_list as $val){
                    $machines[$val['m_id']] = $val;
                }
                foreach ($addList as $value) {
                    $insertAll[] = [
                        'mall_id' => $mall_id,
                        'm_id' => $value,
                        'machine_id' => $machines[$value]['machine_id'],
                        'creator' => $this->manager['manager_id'],
                    ];
                }
                $flag[] = $this->addMallMachineMore($insertAll);
            }
            $result = $this->checkFlag($flag);
            return $this->checkTrans($result);
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e,1);
            return $this->rTryCatch($e->getMessage());
        }
    }
}