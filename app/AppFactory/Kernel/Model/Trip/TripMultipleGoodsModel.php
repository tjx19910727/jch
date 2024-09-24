<?php

namespace app\AppFactory\Kernel\Model\Trip;
use app\AppFactory\Kernel\Model\BaseModel;


class TripMultipleGoodsModel extends BaseModel
{
    //
    protected $pk = "tmg_id";
    protected $name = "trip_multiple_goods";

    /**
     * 获取Join商品列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return TripMultipleGoodsModel|TripMultipleGoodsModel[]|array|\think\Collection|\think\Paginator
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getListJoinGoods($where,$pageNum = 0,$field = "*", $order = "")
    {
        $data = self::alias("tmg")
            ->join("goods g","tmg.g_id = g.g_id","left")
            ->where($where)
            ->field($field)
            ->order($order);
        if ($pageNum) {
            $data = $data->paginate(['query' => request()->param(),'list_rows' => $pageNum],false);
        } else {
            $data = $data->select();
        }
        return $data;
    }

    /**
     * 获取一条Join商品的数据
     * @param $where
     * @param string $field
     * @param string $order
     * @return TripMultipleGoodsModel|array|mixed|null|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getFindJoinGoods($where,$field = "*", $order = "")
    {
        $data = self::alias("tmg")
            ->join("goods g","tmg.g_id = g.g_id","left")
            ->where($where)
            ->field($field)
            ->order($order)
            ->find();
        return $data;
    }

    public static function getListJoinMc($where,$field = "*",$order = "")
    {
        $data = self::alias("tmg")
            ->join("machine_channel mc","mc.g_id = tmg.g_id","left")
            ->where($where)
            ->field($field)
            ->order($order)
            ->select();
        return $data;
    }
}
