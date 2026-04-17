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

    public function getMachineVersionPlanWithMachineNameList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "")
    {
        return MachineVersionPlanModel::getListAndWith($where,$pageNum,$field,$order,$eachFun,'',0,['machine']);
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
        $mvp = $this->getMachineVersionPlanFind(['mvp_id' => $this->message['mvp_id']]);
        actionLog($this->getLS(),'【SQL】查询更新计划',"DataUpload");
        if ($mvp) {
            $mvp = $mvp->toArray();
            actionLog($mvp,'查询更新计划',"DataUpload");
            $this->startTrans();
            try {
                if ($this->message['status'] == 2) {
                    $flag[] = $this->updateMachine(['m_id' => $this->machine['m_id'], 'version' => $mvp['version_no']]);
                    actionLog($this->getLS(), '【SQL】修改设备版本号', "DataUpload");
                }
                $flag[] = $this->updateMachineVersionPlan(['mvp_id' => $this->message['mvp_id'], 'status' => $this->message['status']]);
                actionLog($this->getLS(), '【SQL】修改设备更新计划状态', "DataUpload");
                if ($this->message['status'] == 2) {
                    $this->completeSameUpdateTimePendingPlans($mvp);
                }
                $this->checkTrans($this->checkFlag($flag));
            } catch (\Exception $e) {
                $this->rollbackTrans();
                actionException($e,1);
            }
        }

    }

    /**
     * 更新完成时，同步将同设备同 update_time 的其他未更新计划置为已下架
     * @param $mvp
     * @return int
     */
    protected function completeSameUpdateTimePendingPlans($mvp)
    {
        if(empty($mvp['m_id']) || empty($mvp['mvp_id'])){
            return 0;
        }
        $where = [
            ['m_id', '=', $mvp['m_id'] ?? 0],
            ['status', '=', 1],
            ['mvp_id', '<>', $mvp['mvp_id']],
        ];
        $affected = MachineVersionPlanModel::where($where)->update(['status' => 4]);
        actionLog([
            'machine_id' => $mvp['machine_id'] ?? '',
            'mvp_id' => $mvp['mvp_id'],
            'update_time' => time(),
            'affected' => $affected,
        ], '更新完成后批量置同时间节点计划为已下架', "DataUpload");
        return $affected;
    }
}