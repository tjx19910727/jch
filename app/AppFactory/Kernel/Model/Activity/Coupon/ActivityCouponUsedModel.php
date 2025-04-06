<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/2
 * Time: 11:56
 */

namespace app\AppFactory\Kernel\Model\Activity\Coupon;


use app\AppFactory\Kernel\Model\BaseModel;

class ActivityCouponUsedModel extends BaseModel
{
    protected $pk = "cu_id";
    protected $name = "activity_coupon_used";
}