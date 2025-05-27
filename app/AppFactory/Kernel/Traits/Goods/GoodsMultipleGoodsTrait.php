<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/19
 * Time: 10:13
 */

namespace app\AppFactory\Kernel\Traits\Goods;



use app\AppFactory\Kernel\Model\Goods\GoodsMultipleGoodsModel;

trait GoodsMultipleGoodsTrait
{
    /**
     * 获取字段值
     * @param $where
     * @param $value
     * @return mixed
     */
    public function getGoodsMultipleGoodsValue($where, $value)
    {
        return GoodsMultipleGoodsModel::getFieldValue($where, $value);
    }

    /**
     * 获取单列
     * @param $where
     * @param $column
     * @return array
     */
    public function getGoodsMultipleGoodsColumn($where, $column)
    {
        return GoodsMultipleGoodsModel::getColumn($where, $column);
    }

    public function setGoodsMultipleGoodsInc($where,$field,$inc = 1)
    {
        return GoodsMultipleGoodsModel::setInc($where,$field,$inc);
    }

    public function setGoodsMultipleGoodsDec($where,$field,$dec = 1)
    {
        return GoodsMultipleGoodsModel::setDec($where,$field,$dec);
    }

    /**
     * 统计数量
     * @param $where
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function getGoodsMultipleGoodsCount($where)
    {
        return GoodsMultipleGoodsModel::getCount($where);
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
    public function getGoodsMultipleGoodsList($where, $pageNum = 0, $field = "*", $order = "")
    {
        return GoodsMultipleGoodsModel::getList($where, $pageNum, $field, $order);
    }

    /**
     * 获取一条数据
     * @param $where
     * @param string $field
     * @param string $order
     * @return mixed
     */
    public function getGoodsMultipleGoodsFind($where, $field = "*", $order = "")
    {
        return GoodsMultipleGoodsModel::getFind($where, $field, $order);
    }

    public function getGoodsMultipleGoodsJoinGoodsFind($where,$field = "*",$order = "")
    {
        return GoodsMultipleGoodsModel::getJoinGoodsFind($where,$field,$order);
    }

    /**
     * 添加
     * @param $insert
     * @return mixed
     */
    public function addGoodsMultipleGoods($insert)
    {
        $data = GoodsMultipleGoodsModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    /**
     * 修改
     * @param $update
     * @param array $where
     * @param array $field
     * @return GoodsMultipleGoodsModel
     */
    public function updateGoodsMultipleGoods($update,$where = [],$field = [])
    {
        return GoodsMultipleGoodsModel::update($update,$where,$field);
    }

    /**
     * 删除
     * @param $where
     * @return mixed
     */
    public function delGoodsMultipleGoods($where)
    {
        return GoodsMultipleGoodsModel::whereDel($where);
    }
}