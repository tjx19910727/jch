<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/9/9
 * Time: 16:45
 */

namespace app\AppFactory\Kernel\Traits\Trip;



use app\AppFactory\Kernel\Model\Trip\TripMultipleGoodsModel;

trait TripMultipleGoodsTrait
{
    /**
     * 获取字段值
     * @param $where
     * @param $value
     * @return mixed
     */
    public function getTripMultipleGoodsValue($where, $value)
    {
        return TripMultipleGoodsModel::getFieldValue($where, $value);
    }

    /**
     * 获取单列
     * @param $where
     * @param $column
     * @return array
     */
    public function getTripMultipleGoodsColumn($where, $column)
    {
        return TripMultipleGoodsModel::getColumn($where, $column);
    }

    /**
     * 统计数量
     * @param $where
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function getTripMultipleGoodsCount($where)
    {
        return TripMultipleGoodsModel::getCount($where);
    }

    /**
     * 获取列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return \app\AppFactory\Kernel\Model\BaseModel|\app\AppFactory\Kernel\Model\BaseModel[]|array|string|\think\Collection|\think\Paginator
     * @throws \Exception
     */
    public function getTripMultipleGoodsList($where, $pageNum = 0, $field = "*", $order = "")
    {
        return TripMultipleGoodsModel::getList($where, $pageNum, $field, $order);
    }

    /**
     * 获取关联商品列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return TripMultipleGoodsModel|TripMultipleGoodsModel[]|array|\think\Collection|\think\Paginator
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getTripMultipleGoodsJoinGoodsList($where,$pageNum = 0,$field = "*",$order = "")
    {
        return TripMultipleGoodsModel::getListJoinGoods($where,$pageNum,$field,$order);
    }

    public function getTripMultipleGoodsJoinMcList($where,$field = "*",$order = "")
    {
        return TripMultipleGoodsModel::getListJoinMc($where,$field,$order);
    }

    /**
     * 获取一条数据
     * @param $where
     * @param string $field
     * @param string $order
     * @return mixed
     */
    public function getTripMultipleGoodsFind($where, $field = "*", $order = "")
    {
        return TripMultipleGoodsModel::getFind($where, $field, $order);
    }

    /**
     * 添加
     * @param $insert
     * @return mixed
     */
    public function addTripMultipleGoods($insert)
    {
        $data = TripMultipleGoodsModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    /**
     * 修改
     * @param $update
     * @param array $where
     * @param array $field
     * @return TripMultipleGoodsModel
     */
    public function updateTripMultipleGoods($update,$where = [],$field = [])
    {
        return TripMultipleGoodsModel::update($update,$where,$field);
    }

    /**
     * 删除
     * @param $where
     * @return mixed
     */
    public function delTripMultipleGoods($where)
    {
        return TripMultipleGoodsModel::whereDel($where);
    }
}