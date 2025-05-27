<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/21
 * Time: 11:58
 */

namespace app\AppFactory\Kernel\Traits\Activity;


use app\AppFactory\Kernel\Model\Activity\Lottery\ActivityLotteryUsedModel;

trait ActivityLotteryUsedTrait
{

    public function getActivityLotteryUsedFind($where,$field = "*",$order = "al_id desc")
    {
        return ActivityLotteryUsedModel::getFind($where,$field,$order);
    }

    public function getActivityLotteryUsedList($where,$pageNum = 0,$field = "*", $order = "al_id desc")
    {
        return ActivityLotteryUsedModel::getList($where,$pageNum,$field,$order);
    }

    public function addActivityLotteryUsed($insert)
    {
        $alu = ActivityLotteryUsedModel::create($insert);
        return $alu->alu_id;
    }

    public function updateActivityLotteryUsed($update,$where = [],$field = [])
    {
        return ActivityLotteryUsedModel::update($update,$where,$field);
    }

    public function delActivityLotteryUsed($where)
    {
        return ActivityLotteryUsedModel::whereDel($where);
    }
}