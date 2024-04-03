<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/19
 * Time: 16:17
 */

namespace app\AppFactory\Kernel\Model\Activity\Lottery;


use app\AppFactory\Kernel\Model\BaseModel;

class ActivityLotteryModel extends BaseModel
{
    protected $pk = "al_id";
    protected $name = "activity_lottery";

    /**
     * 通过设备获取活动列表
     * @param $where
     * @param string $field
     * @param string $order
     * @return ActivityLotteryModel[]|array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getListByMachine($where,$field = "*", $order = "")
    {
        $where .=  " AND am.a_type = 3 ";
        $data = self::alias("al")
            ->join("activity_machine am", "am.a_id = al.al_id","left")
            ->where($where)
            ->field($field)
            ->order($order)
            ->select();
        return $data;
    }
}