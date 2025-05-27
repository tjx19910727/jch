<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/19
 * Time: 16:29
 */

namespace app\AppFactory\Kernel\Traits\Activity;


use app\AppFactory\Kernel\Model\Activity\Lottery\ActivityLotteryContentModel;

trait ActivityLotteryContentTrait
{
    public function getActivityLotteryContentSum($where,$sum)
    {
        return ActivityLotteryContentModel::getSum($where,$sum);
    }

    public function getActivityLotteryContentFind($where,$field = "*",$order = "c_id desc")
    {
        return ActivityLotteryContentModel::getFind($where,$field,$order);
    }

    public function getActivityLotteryContentList($where,$pageNum = 0,$field = "*", $order = "c_id desc")
    {
        return ActivityLotteryContentModel::getList($where,$pageNum,$field,$order);
    }

    public function addActivityLotteryContent($insert)
    {
        $alc = ActivityLotteryContentModel::create($insert);
        return $alc->c_id;
    }

    public function updateActivityLotteryContent($update,$where = [],$field = [])
    {
        return ActivityLotteryContentModel::update($update,$where,$field);
    }

    public function delActivityLotteryContent($where)
    {
        return ActivityLotteryContentModel::whereDel($where);
    }
}