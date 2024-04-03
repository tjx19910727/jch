<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/19
 * Time: 16:28
 */

namespace app\AppFactory\Kernel\Traits\Activity;


use app\AppFactory\Kernel\Model\Activity\Lottery\ActivityLotteryConfigModel;

trait ActivityLotteryConfigTrait
{

    public function getActivityLotteryConfigFind($where,$field = "*",$order = "alc_id desc")
    {
        return ActivityLotteryConfigModel::getFind($where,$field,$order);
    }

    public function getActivityLotteryConfigList($where,$pageNum = 0,$field = "*", $order = "alc_id desc")
    {
        return ActivityLotteryConfigModel::getList($where,$pageNum,$field,$order);
    }

    public function addActivityLotteryConfigMore($insert)
    {
        $alc = new ActivityLotteryConfigModel();
        return $alc->saveAll($insert);
    }

    public function addActivityLotteryConfig($insert)
    {
        $alc = ActivityLotteryConfigModel::create($insert);
        return $alc->alc_id;
    }

    public function updateActivityLotteryConfig($update,$where = [],$field = [])
    {
        return ActivityLotteryConfigModel::update($update,$where,$field);
    }

    public function delActivityLotteryConfig($where)
    {
        return ActivityLotteryConfigModel::whereDel($where);
    }
}