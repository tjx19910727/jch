<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/15
 * Time: 14:29
 */

namespace app\AppFactory\Kernel\Model\Activity\Pick;


use app\AppFactory\Kernel\Model\BaseModel;

class ActivityPickCodeModel extends BaseModel
{
    protected $pk = "apc_id";
    protected $name = "activity_pick_code";

    /**
     * 获取已开始，未结束的取货码信息
     * @param string $where
     * @param string $field
     * @param string $order
     * @return ActivityPickModel[]|array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getFindWithPick($where,$field = "*", $order = "")
    {
        $now = time();
        $data = self::alias("apc")
            ->join("activity_pick ap", "apc.ap_id = ap.id","inner")
            ->where($where)
            // 活动已开始且未结束（end_time > now 或 end_time = 0 表示无限制）
            ->where('ap.start_time', '<', $now)
            ->where(function($query) use ($now){
                $query->where('ap.end_time', '>', $now)->whereOr('ap.end_time', 0);
            })
            ->field($field)
            ->order($order)
            ->find();

        return $data;
    }
}