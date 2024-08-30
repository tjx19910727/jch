<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/26
 * Time: 15:35
 */

namespace app\AppFactory\Kernel\Traits\Machine;



use app\AppFactory\Kernel\Model\Machine\MachineFreeGoodsModel;

trait MachineFreeGoodsTrait
{
    /**
     * 获取字段值
     * @param $where
     * @param $value
     * @return mixed
     */
    public function getMachineFreeGoodsValue($where, $value)
    {
        return MachineFreeGoodsModel::getFieldValue($where, $value);
    }

    /**
     * 获取单列
     * @param $where
     * @param $column
     * @return array
     */
    public function getMachineFreeGoodsColumn($where, $column)
    {
        return MachineFreeGoodsModel::getColumn($where, $column);
    }

    /**
     * 统计数量
     * @param $where
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function getMachineFreeGoodsCount($where)
    {
        return MachineFreeGoodsModel::getCount($where);
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
    public function getMachineFreeGoodsList($where, $pageNum = 0, $field = "*", $order = "")
    {
        return MachineFreeGoodsModel::getJoinGoodsList($where, $pageNum, $field, $order);
    }

    /**
     * 获取一条数据
     * @param $where
     * @param string $field
     * @param string $order
     * @return mixed
     */
    public function getMachineFreeGoodsFind($where, $field = "*", $order = "")
    {
        return MachineFreeGoodsModel::getFind($where, $field, $order);
    }

    /**
     * 添加
     * @param $insert
     * @return mixed
     */
    public function addMachineFreeGoods($insert)
    {
        $data = MachineFreeGoodsModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    /**
     * 修改
     * @param $update
     * @param array $where
     * @param array $field
     * @return MachineFreeGoodsModel
     */
    public function updateMachineFreeGoods($update,$where = [],$field = [])
    {
        return MachineFreeGoodsModel::update($update,$where,$field);
    }

    /**
     * 删除
     * @param $where
     * @return mixed
     */
    public function delMachineFreeGoods($where)
    {
        return MachineFreeGoodsModel::whereDel($where);
    }
}