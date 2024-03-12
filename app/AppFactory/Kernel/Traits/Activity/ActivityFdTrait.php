<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/6
 * Time: 11:30
 */

namespace app\AppFactory\Kernel\Traits\Activity;


use app\AppFactory\Kernel\Model\Activity\Fd\ActivityFdModel;

trait ActivityFdTrait
{
    
    public function getActivityFdFind($where,$field = "*",$order = "")
    {
        return ActivityFdModel::getFind($where,$field,$order);
    }

    public function getActivityFdList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "")
    {
        return ActivityFdModel::getList($where,$pageNum,$field,$order,$eachFun);
    }

    public function addActivityFd($insert)
    {
        !isset($this->manager['manager_id']) ?: $insert['creator'] = $this->manager['manager_id'];
        $data = ActivityFdModel::create($insert);
        return $data->fd_id;
    }

    public function updateActivityFd($update,$where = [],$field = [])
    {
        !isset($this->manager['manager_id']) ?: $update['update_id'] = $this->manager['manager_id'];
        return ActivityFdModel::update($update,$where,$field);
    }

    public function delActivityFd($where)
    {
        $ac = $this->getActivityFdFind($where,'fd_id');
        $result = ActivityFdModel::whereDel($where);
        if ($result) {
            $this->delActivityFdUsed(['c_id' => $ac['c_id']]);
            $this->delActivityMachine(['a_id' => $ac['c_id'], 'a_type' => 1]);
            $this->delActivityGoods(['a_id' => $ac['c_id'], 'a_type' => 1]);
        }
        return $result;
    }
}