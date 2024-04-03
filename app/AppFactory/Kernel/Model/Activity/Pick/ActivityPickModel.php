<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/15
 * Time: 14:28
 */

namespace app\AppFactory\Kernel\Model\Activity\Pick;


use app\AppFactory\Kernel\Model\BaseModel;

class ActivityPickModel extends BaseModel
{
    protected $pk = "id";
    protected $name = "activity_pick";

    /**
     * 获取取货码活动设备列表
     * @param string $where
     * @param string $field
     * @param string $order
     * @return ActivityPickModel[]|array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getListByMachine($where,$field = "*", $order = "")
    {
        $where .=  " AND am.a_type = 4 ";
        $data = self::alias("ap")
            ->join("activity_machine am", "am.a_id = ap.id","left")
            ->where($where)
            ->field($field)
            ->order($order)
            ->select();
        return $data;
    }
}