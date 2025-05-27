<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/19
 * Time: 10:08
 */

namespace app\AppFactory\Kernel\Traits\Goods;



use app\AppFactory\Kernel\Model\Goods\GoodsMultipleModel;

trait GoodsMultipleTrait
{
    /**
     * 获取字段值
     * @param $where
     * @param $value
     * @return mixed
     */
    public function getGoodsMultipleValue($where, $value)
    {
        return GoodsMultipleModel::getFieldValue($where, $value);
    }

    /**
     * 获取单列
     * @param $where
     * @param $column
     * @return array
     */
    public function getGoodsMultipleColumn($where, $column)
    {
        return GoodsMultipleModel::getColumn($where, $column);
    }

    /**
     * 统计数量
     * @param $where
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function getGoodsMultipleCount($where)
    {
        return GoodsMultipleModel::getCount($where);
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
    public function getGoodsMultipleList($where, $pageNum = 0, $field = "*", $order = "")
    {
        return GoodsMultipleModel::getList($where, $pageNum, $field, $order);
    }

    /**
     * 通过设备获取组合商品列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @param int $page
     * @return GoodsMultipleModel|array|\think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function getGoodsMultipleListByMachine($where,$pageNum = 0,$field = "*",$order = "",$page = 1)
    {
        return GoodsMultipleModel::joinGmm($where,$pageNum,$field,$order,$page);
    }

    /**
     * 通过商品获取组合商品列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return GoodsMultipleModel|array|\think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function getGoodsMultipleListByGoods($where,$pageNum = 0,$field = "*",$order = "")
    {
        return GoodsMultipleModel::joinGmg($where,$pageNum,$field,$order);
    }

    /**
     * 获取一条数据
     * @param $where
     * @param string $field
     * @param string $order
     * @return mixed
     */
    public function getGoodsMultipleFind($where, $field = "*", $order = "")
    {
        return GoodsMultipleModel::getFind($where, $field, $order);
    }

    /**
     * 添加
     * @param $insert
     * @return mixed
     */
    public function addGoodsMultiple($insert)
    {
        if ((!isset($insert['creator']) || !$insert['creator']) && isset($this->manager['manager_id'])) $insert['creator'] = $this->manager['manager_id'];
        $data = GoodsMultipleModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    /**
     * 修改
     * @param $update
     * @param array $where
     * @param array $field
     * @return GoodsMultipleModel
     */
    public function updateGoodsMultiple($update,$where = [],$field = [])
    {
        if ((!isset($update['update_id']) || !$update['update_id']) && isset($this->manager['manager_id'])) $update['update_id'] = $this->manager['manager_id'];
        return GoodsMultipleModel::update($update,$where,$field);
    }

    /**
     * 删除
     * @param $where
     * @return mixed
     */
    public function delGoodsMultiple($where)
    {
        return GoodsMultipleModel::whereDel($where);
    }
}