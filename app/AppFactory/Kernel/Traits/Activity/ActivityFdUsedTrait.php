<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/26
 * Time: 10:04
 */

namespace app\AppFactory\Kernel\Traits\Activity;


use app\AppFactory\Kernel\Model\Activity\Fd\ActivityFdUsedModel;

trait ActivityFdUsedTrait
{

    public function getActivityFdUsedFind($where,$field = "*",$order = "")
    {
        return ActivityFdUsedModel::getFind($where,$field,$order);
    }

    public function getActivityFdUsedList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "")
    {
        return ActivityFdUsedModel::getList($where,$pageNum,$field,$order,$eachFun);
    }

    public function addActivityFdUsed($insert)
    {
        $data = ActivityFdUsedModel::create($insert);
        return $data->fdu_id;
    }

    public function updateActivityFdUsed($update,$where = [],$field = [])
    {
        return ActivityFdUsedModel::update($update,$where,$field);
    }

}