<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/11/7
 * Time: 11:32
 */

namespace app\AppFactory\Kernel\Traits\Activity;


use app\AppFactory\Kernel\Model\Activity\ActivityTimeModel;

trait ActivityTimeTrait
{
    /**
     * 获取活动时间段列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return ActivityTimeModel|ActivityTimeModel[]|array|\think\Collection|\think\Paginator
     * @throws \Exception
     */
    public function getActivityTimeList($where,$pageNum = 0, $field = "*",$order = "at_id desc")
    {
        return ActivityTimeModel::getList($where,$pageNum,$field,$order);
    }

    /**
     * 添加活动时间段
     * @param $insert
     * @return mixed
     */
    public function addActivityTime($insert)
    {
        $at = ActivityTimeModel::create($insert);
        return $at->at_id;
    }

    /**
     * 修改活动时间段
     * @param $update
     * @param array $where
     * @param array $field1
     * @return ActivityTimeModel
     */
    public function updateActivityTime($update,$where = [], $field = [])
    {
        return ActivityTimeModel::update($update,$where,$field);
    }

    /**
     * 删除活动时间段
     * @param $where
     * @return bool
     */
    public function delActivityTime($where)
    {
        return ActivityTimeModel::destroy($where);
    }
}