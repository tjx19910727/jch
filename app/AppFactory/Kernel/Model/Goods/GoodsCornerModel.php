<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/4/1
 * Time: 8:46
 */

namespace app\AppFactory\Kernel\Model\Goods;


use app\AppFactory\Kernel\Model\BaseModel;

class GoodsCornerModel extends BaseModel
{
    protected $pk = "id";
    protected $name = "goods_corner";

    /**
     * 关联设备获取商品角标
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @param string $eachFunc
     * @return GoodsCornerModel|GoodsCornerModel[]|array|\think\Collection|\think\Paginator
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getListByAmAg($where,$pageNum = 0,$field = "*",$order = "id asc",$eachFunc = "")
    {
        $data = self::alias("gc")
            ->join("activity_machine am","am.a_id = gc.id AND am.a_type = 5","left")
            ->join("activity_goods ag","ag.a_id = gc.id AND ag.a_type = 5", "left")
            ->where($where)
            ->order($order)
            ->field($field);
        if ($pageNum) {
            $data = $data->paginate($pageNum,false,['query' => request()->param()]);
            if ($eachFunc) {
                $data = $data->each($eachFunc);
            }
        } else {
            $data = $data->select();
        }
        return $data;
    }

    /**
     * 关联设备获取一条商品角标
     * @param $where
     * @param string $field
     * @param string $order
     * @return GoodsCornerModel|array|mixed|null|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getFindByAmAg($where,$field = "*",$order ="id asc")
    {
        $data = self::alias("gc")
            ->join("activity_machine am","am.a_id = gc.id AND am.a_type = 5","left")
            ->join("activity_goods ag","ag.a_id = gc.id AND ag.a_type = 5", "left")
            ->where($where)
            ->order($order)
            ->field($field)
            ->find();
        return $data;
    }
}