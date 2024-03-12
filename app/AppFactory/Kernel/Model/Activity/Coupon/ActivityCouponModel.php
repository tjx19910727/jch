<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/2
 * Time: 11:56
 */

namespace app\AppFactory\Kernel\Model\Activity\Coupon;


use app\AppFactory\Kernel\Model\BaseModel;

class ActivityCouponModel extends BaseModel
{
    protected $pk = "c_id";
    protected $name = "activity_coupon";

    public static function getListByMachine($where,$field = "*",$order = "")
    {
        $data = self::alias("ac")
            ->join("activity_machine am","am.a_id = ac.c_id","left")
            ->where($where)
            ->field($field)
            ->order($order)
            ->select();
        return $data;
    }
}