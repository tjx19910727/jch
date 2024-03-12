<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/2/23
 * Time: 15:01
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Machine\MachineOnlineDetailsModel;


trait MachineOnlineDetailsTrait
{

    public function getMachineOnlineDetailsFind($where,$field = "*",$order = "")
    {
        return MachineOnlineDetailsModel::getFind($where,$field,$order);
    }

    public function getMachineOnlineDetailsList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "",$group = "")
    {
        return MachineOnlineDetailsModel::getList($where,$pageNum,$field,$order,$eachFun,$group);
    }

    public function addMachineOnlineDetails($insert)
    {
        $data = MachineOnlineDetailsModel::create($insert);
        return $data->mod_id;
    }

    public function updateMachineOnlineDetails($update,$where = [],$field = [])
    {
        return MachineOnlineDetailsModel::update($update,$where,$field);
    }

    public function delMachineOnlineDetails($where)
    {
        $result = MachineOnlineDetailsModel::whereDel($where);
        return $result;
    }

    /**
     * 生成新的上线记录
     */
    public function newRecord()
    {
        $details = $this->getMachineOnlineDetailsFind(['m_id' => $this->machine['m_id'],'offline_time' => 0],'mod_id,online_time,d_date','mod_id desc');
        if ($details) {
            // 不是今天的数据，离线时间点为23:59:59，计算在线时长，生成新的上线记录
            if ($details['d_date'] != strtotime(date("Y-m-d"))) {
                $offlineTime = strtotime(date("Y-m-d 23:59:59",$details['online_time']));
                $update['mod_id'] = $details['mod_id'];
                $update['offline_time'] = $offlineTime;
                $update['heart_time'] = $offlineTime;
                $update['sod_duration'] = bcsub($offlineTime,  $details['online_time']);
                $this->updateMachineOnlineDetails($update);
                $insert = [
                    "m_id" => $this->machine['m_id'],
                    "machine_name" => $this->machine['machine_name'],
                    "machine_id" => $this->machine['machine_id'],
                    "online_time" => strtotime(date("Y-m-d 00:00:00")),
                    "heart_time" => time(),
                    "d_date" => strtotime(date("Y-m-d")),
                ];
                $this->addMachineOnlineDetails($insert);
            } else {
                $this->updateMachineOnlineDetails(['mod_id' => $details['mod_id'], 'heart_time' => time()]);
            }
        } else {
            $insert = [
                "m_id" => $this->machine['m_id'],
                "machine_name" => $this->machine['machine_name'],
                "machine_id" => $this->machine['machine_id'],
                "heart_time" => time(),
                "d_date" => strtotime(date("Y-m-d")),
            ];
            $this->addMachineOnlineDetails($insert);
        }
    }
}