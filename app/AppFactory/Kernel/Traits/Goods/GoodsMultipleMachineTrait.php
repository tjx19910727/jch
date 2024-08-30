<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/19
 * Time: 10:15
 */

namespace app\AppFactory\Kernel\Traits\Goods;




use app\AppFactory\Kernel\Model\Goods\GoodsMultipleMachineModel;

trait GoodsMultipleMachineTrait
{
    /**
     * 获取字段值
     * @param $where
     * @param $value
     * @return mixed
     */
    public function getGoodsMultipleMachineValue($where, $value)
    {
        return GoodsMultipleMachineModel::getFieldValue($where, $value);
    }

    /**
     * 获取单列
     * @param $where
     * @param $column
     * @return array
     */
    public function getGoodsMultipleMachineColumn($where, $column)
    {
        return GoodsMultipleMachineModel::getColumn($where, $column);
    }

    /**
     * 统计数量
     * @param $where
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function getGoodsMultipleMachineCount($where)
    {
        return GoodsMultipleMachineModel::getCount($where);
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
    public function getGoodsMultipleMachineList($where, $pageNum = 0, $field = "*", $order = "")
    {
        return GoodsMultipleMachineModel::getList($where, $pageNum, $field, $order);
    }

    /**
     * 获取一条数据
     * @param $where
     * @param string $field
     * @param string $order
     * @return mixed
     */
    public function getGoodsMultipleMachineFind($where, $field = "*", $order = "")
    {
        return GoodsMultipleMachineModel::getFind($where, $field, $order);
    }

    /**
     * 添加
     * @param $insert
     * @return mixed
     */
    public function addGoodsMultipleMachine($insert)
    {
        $data = GoodsMultipleMachineModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    /**
     * 修改
     * @param $update
     * @param array $where
     * @param array $field
     * @return GoodsMultipleMachineModel
     */
    public function updateGoodsMultipleMachine($update,$where = [],$field = [])
    {
        return GoodsMultipleMachineModel::update($update,$where,$field);
    }

    /**
     * 删除
     * @param $where
     * @return mixed
     */
    public function delGoodsMultipleMachine($where)
    {
        return GoodsMultipleMachineModel::whereDel($where);
    }
}