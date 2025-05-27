<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/22
 * Time: 20:39
 */

namespace app\AppFactory\Kernel\Model\Activity\Lottery;


use app\AppFactory\Kernel\Model\BaseModel;

class ActivityLotteryUsedGoodsModel extends BaseModel
{
    protected $pk = "ug_id";
    protected $name = "activity_lottery_used_goods";

    public static function getExportList($where,$field = "*", $order = "")
    {
        $data = self::alias("ug")
            ->join("activity_lottery_used alu","alu.alu_id = ug.alu_id","left")
            ->where($where)
            ->field($field)
            ->order($order)
            ->select();
        return $data;
    }
}