<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/30
 * Time: 15:27
 */

namespace app\AppFactory\TimeTask\Activity;


use app\AppFactory\Kernel\Traits\Activity\ActivityCouponUsedTrait;
use app\AppFactory\TimeTask\TimeTaskBase;

class CouponClient extends TimeTaskBase
{
    use ActivityCouponUsedTrait;

    /**
     * 定时清除已过期或作废的优惠券码
     */
    public function clearCouponUsed()
    {
        $where[] = ['status','between',[3,4]];
        $where[] = function ($query) {
            return $query->where("order_id is null");
        };
        $this->delActivityCouponUsed($where);
    }
}