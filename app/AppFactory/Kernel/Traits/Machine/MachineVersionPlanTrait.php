<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/2/24
 * Time: 10:10
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Machine\MachineVersionPlanModel;

trait MachineVersionPlanTrait
{
    public function getMachineVersionPlanFind($where,$field = "*",$order = "")
    {
        return MachineVersionPlanModel::getFind($where,$field,$order);
    }

    public function getMachineVersionPlanList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "")
    {
        return MachineVersionPlanModel::getList($where,$pageNum,$field,$order,$eachFun);
    }

    public function addMachineVersionPlan($insert)
    {
        !isset($this->manager['manager_id']) ? :$insert['creator'] = $this->manager['manager_id'];
        $data = MachineVersionPlanModel::create($insert);
        return $data->mvp_id;
    }

    public function updateMachineVersionPlan($update,$where = [],$field = [])
    {
        return MachineVersionPlanModel::update($update,$where,$field);
    }

    public function delMachineVersionPlan($where)
    {
        $result = MachineVersionPlanModel::whereDel($where);
        return $result;
    }

    /**
     * 设备更新上报修改计划状态
     * @return mixed
     */
    public function updateComplete()
    {
        $mvp = $this->getMachineVersionPlanFind(['mvp_id' => $this->message['mvp_id']])->toArray();
        actionLog($this->getLS(),'【SQL】查询更新计划',"DataUpload");
        actionLog($mvp,'查询更新计划',"DataUpload");
        if ($mvp) {
            $this->startTrans();
            try {
                if ($this->message['status'] == 2) {
                    $flag[] = $this->updateMachine(['m_id' => $this->machine['m_id'], 'version' => $mvp['version_no']]);
                    actionLog($this->getLS(), '【SQL】修改设备版本号', "DataUpload");
                }
                $flag[] = $this->updateMachineVersionPlan(['mvp_id' => $this->message['mvp_id'], 'status' => $this->message['status']]);
                actionLog($this->getLS(), '【SQL】修改设备更新计划状态', "DataUpload");
                $this->checkTrans($this->checkFlag($flag));
            } catch (\Exception $e) {
                $this->rollbackTrans();
                actionException($e,1);
            }
        }

    }
}