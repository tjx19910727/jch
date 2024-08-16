<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/10
 * Time: 10:24
 */

namespace app\AppFactory\Management\MicroMall;


use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Traits\MicroMall\MicroMallMachineTrait;
use app\AppFactory\Management\ManagementClient;

class MicroMallMachineClient extends ManagementClient
{
    use MicroMallMachineTrait;
    use MachineTrait;

    /**
     * 绑定设备
     * @param $postData
     * @return array|bool|string|\think\response\Json
     */
    public function bind($postData)
    {
        try {
            $this->startTrans();
            $mm_id = $postData['mm_id'];
            $m_id = $postData['m_id'] ?? '';
            $mm_ids = explode(",", $mm_id);
            $m_ids = explode(",", $m_id);
            $flag = [];
            foreach ($mm_ids as $key => $value) {
                $oldMList = $this->getMicroMallMachineColumn(['mm_id' => $value], 'm_id');
                $delMList = array_diff($oldMList, $m_ids);
                if ($delMList) {
                    $flag[] = $this->delMicroMallMachine(['mm_id' => $value, ['m_id', 'in', $delMList]]);
                }
                $addMList = array_diff($m_ids, $oldMList);
                if ($addMList) {
                    foreach ($addMList as $mk => $mv) {
                        $insert = [
                            "mm_id" => $value,
                            "m_id" => $mv,
                        ];
                        $flag[] = $this->addMicroMallMachine($insert);
                    }
                }
            }
            $check = $this->checkFlag($flag);
            return $this->checkTrans($check);
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e,1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 获取绑定列表
     * @param $postData
     * @return array|\think\response\Json
     */
    public function getBindList($postData)
    {
        $where['mm_id'] = $postData['mm_id'];
        $list = $this->getMicroMallMachineList($where,$postData['pageNum'] ?? 0,'m_id');
        if ($list) {
            if (isset($postData['pageNum']) && $postData['pageNum']) {
                $list->each(function ($value) {
                    $machine = $this->getMachineFind(['m_id' => $value['m_id']],'machine_id,machine_name');
                    if ($machine) {
                        $machine = $machine->toArray();
                        $value['machine_id'] = $machine['machine_id'];
                        $value['machine_name'] = $machine['machine_name'];
                    }
                    return $value;
                });
            } else {
                $list = $list->toArray();
                foreach ($list as $key => $value) {
                    $machine = $this->getMachineFind(['m_id' => $value['m_id']], 'machine_id,machine_name');
                    if ($machine) {
                        $machine = $machine->toArray();
                        $value['machine_id'] = $machine['machine_id'];
                        $value['machine_name'] = $machine['machine_name'];
                    }
                    $list[$key] = $value;
                }
            }
            return $this->r(200,$this->lang("query_success"),$list);
        }
        return $this->r(100,$this->lang("query_fail"));
    }
}