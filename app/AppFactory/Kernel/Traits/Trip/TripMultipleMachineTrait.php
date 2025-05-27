<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/9/9
 * Time: 16:45
 */

namespace app\AppFactory\Kernel\Traits\Trip;




use app\AppFactory\Kernel\Model\Trip\TripMultipleMachineModel;

trait TripMultipleMachineTrait
{
    /**
     * 获取字段值
     * @param $where
     * @param $value
     * @return mixed
     */
    public function getTripMultipleMachineValue($where, $value)
    {
        return TripMultipleMachineModel::getFieldValue($where, $value);
    }

    /**
     * 获取单列
     * @param $where
     * @param $column
     * @return array
     */
    public function getTripMultipleMachineColumn($where, $column)
    {
        return TripMultipleMachineModel::getColumn($where, $column);
    }

    /**
     * 统计数量
     * @param $where
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function getTripMultipleMachineCount($where)
    {
        return TripMultipleMachineModel::getCount($where);
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
    public function getTripMultipleMachineList($where, $pageNum = 0, $field = "*", $order = "")
    {
        return TripMultipleMachineModel::getList($where, $pageNum, $field, $order);
    }

    /**
     * 获取一条数据
     * @param $where
     * @param string $field
     * @param string $order
     * @return mixed
     */
    public function getTripMultipleMachineFind($where, $field = "*", $order = "")
    {
        return TripMultipleMachineModel::getFind($where, $field, $order);
    }

    /**
     * 添加
     * @param $insert
     * @return mixed
     */
    public function addTripMultipleMachine($insert)
    {
        $data = TripMultipleMachineModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    /**
     * 修改
     * @param $update
     * @param array $where
     * @param array $field
     * @return TripMultipleMachineModel
     */
    public function updateTripMultipleMachine($update,$where = [],$field = [])
    {
        return TripMultipleMachineModel::update($update,$where,$field);
    }

    /**
     * 删除
     * @param $where
     * @return mixed
     */
    public function delTripMultipleMachine($where)
    {
        return TripMultipleMachineModel::whereDel($where);
    }
}