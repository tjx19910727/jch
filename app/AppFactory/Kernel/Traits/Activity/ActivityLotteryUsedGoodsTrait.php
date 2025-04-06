<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/22
 * Time: 20:40
 */

namespace app\AppFactory\Kernel\Traits\Activity;


use app\AppFactory\Kernel\Model\Activity\Lottery\ActivityLotteryUsedGoodsModel;

trait ActivityLotteryUsedGoodsTrait
{
    public function getActivityLotteryUsedGoodsColumn($where,$column)
    {
        return ActivityLotteryUsedGoodsModel::getColumn($where,$column);
    }

    public function getActivityLotteryUsedGoodsCount($where)
    {
        return ActivityLotteryUsedGoodsModel::getCount($where);
    }

    public function getActivityLotteryUsedGoodsSum($where,$sum)
    {
        return ActivityLotteryUsedGoodsModel::getSum($where,$sum);
    }

    public function getActivityLotteryUsedGoodsFind($where,$field = "*", $order = "")
    {
        return ActivityLotteryUsedGoodsModel::getFind($where,$field,$order);
    }

    public function getActivityLotteryUsedGoodsList($where,$pageNum = 0, $field = "*", $order = "")
    {
        return ActivityLotteryUsedGoodsModel::getList($where,$pageNum,$field,$order);
    }

    public function getActivityLotteryUsedGoodsExportList($where,$field = "*", $order = "")
    {
        return ActivityLotteryUsedGoodsModel::getExportList($where,$field,$order);
    }

    public function addActivityLotteryUsedGoodsMore($insertAll)
    {
        $ug = new ActivityLotteryUsedGoodsModel();
        return $ug->saveAll($insertAll);
    }

    public function addActivityLotteryUsedGoods($insert)
    {
        $ug = ActivityLotteryUsedGoodsModel::create($insert);
        return $ug->ug_id;
    }

    public function updateActivityLotteryUsedGoods($update,$where = [],$field = [])
    {
        return ActivityLotteryUsedGoodsModel::update($update,$where,$field);
    }

    public function delActivityLotteryUsedGoods($where)
    {
        return ActivityLotteryUsedGoodsModel::whereDel($where);
    }
}