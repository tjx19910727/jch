<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/5
 * Time: 17:41
 */

namespace app\AppFactory\Kernel\Traits\Activity;


use app\AppFactory\Kernel\Model\Activity\ActivityMachineModel;

trait ActivityMachineTrait
{
    public function getActivityMachineColumn($where,$column)
    {
        return ActivityMachineModel::getColumn($where,$column);
    }

    public function getActivityMachineFind($where,$field = "*",$order = "")
    {
        return ActivityMachineModel::getFind($where,$field,$order);
    }

    public function getActivityMachineList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "")
    {
        return ActivityMachineModel::getList($where,$pageNum,$field,$order,$eachFun);
    }

    /**
     * 添加营销活动关联设备信息
     * activity_coupon      activity_pick
     * @param $insert
     * @param $machineList
     * @return bool|string
     */
    public function addAm($insert,$machineList)
    {
        $machineList = json2arr($machineList);
        if (!$machineList) {
            return $this->lang("VActivityMachine.machine_require");
        }
        foreach ($machineList as $mv) {
            $m = $this->getMachineFind(['machine_id' => $mv], 'm_id,machine_id,machine_name');
            if (!$m) {
                return $this->lang("VActivityMachine.machine_no_data") . "：" . $mv;
            }
            $m = $m->toArray();
            $insertAm = array_merge($insert, $m);
            $insertAmAll[] = $insertAm;
        }
        $this->addActivityMachineMore($insertAmAll);
        return true;
    }

    public function addActivityMachineMore($all)
    {
        $am = new ActivityMachineModel();
        return $am->saveAll($all);
    }

    public function addActivityMachine($insert)
    {
        $data = ActivityMachineModel::create($insert);
        return $data->am_id;
    }

    public function updateActivityMachine($update,$where = [],$field = [])
    {
        return ActivityMachineModel::update($update,$where,$field);
    }

    public function delActivityMachine($where)
    {
        $result = ActivityMachineModel::whereDel($where);
        return $result;
    }
}