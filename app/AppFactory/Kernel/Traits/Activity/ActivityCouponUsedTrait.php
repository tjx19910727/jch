<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/4
 * Time: 13:58
 */

namespace app\AppFactory\Kernel\Traits\Activity;


use app\AppFactory\Kernel\Model\Activity\Coupon\ActivityCouponUsedModel;

trait ActivityCouponUsedTrait
{
    public function getActivityCouponUsedValue($where,$value)
    {
        return ActivityCouponUsedModel::getFieldValue($where,$value);
    }

    public function getActivityCouponUsedCount($where)
    {
        return ActivityCouponUsedModel::getCount($where);
    }

    public function getActivityCouponUsedColumn($where,$column)
    {
        return ActivityCouponUsedModel::getColumn($where,$column);
    }

    /**
     * 查优惠券使用记录详情
     * @param $where
     * @param string $field
     * @param string $order
     * @return mixed
     */
    public function getActivityCouponUsedFind($where,$field = "*",$order = "")
    {
        return ActivityCouponUsedModel::getFind($where,$field,$order);
    }

    public function getActivityCouponUsedList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "")
    {
        return ActivityCouponUsedModel::getList($where,$pageNum,$field,$order,$eachFun);
    }

    public function addActivityCouponUsedMore($insertAll)
    {
        $used = new ActivityCouponUsedModel();
        return $used->saveAll($insertAll);
    }

    public function addActivityCouponUsed($insert)
    {
        $data = ActivityCouponUsedModel::create($insert);
        return $data->cu_id;
    }

    public function updateActivityCouponUsed($update,$where = [],$field = [])
    {
        return ActivityCouponUsedModel::update($update,$where,$field);
    }

    public function delActivityCouponUsed($where)
    {
        $result = ActivityCouponUsedModel::whereDel($where);
        return $result;
    }


}