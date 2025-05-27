<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/9/9
 * Time: 16:24
 */

namespace app\AppFactory\Kernel\Traits\Trip;




use app\AppFactory\Kernel\Model\Trip\TripMultipleHotelModel;

trait TripMultipleHotelTrait
{
    /**
     * 获取字段值
     * @param $where
     * @param $value
     * @return mixed
     */
    public function getTripMultipleHotelValue($where, $value)
    {
        return TripMultipleHotelModel::getFieldValue($where, $value);
    }

    /**
     * 获取单列
     * @param $where
     * @param $column
     * @return array
     */
    public function getTripMultipleHotelColumn($where, $column)
    {
        return TripMultipleHotelModel::getColumn($where, $column);
    }

    /**
     * 统计数量
     * @param $where
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function getTripMultipleHotelCount($where)
    {
        return TripMultipleHotelModel::getCount($where);
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
    public function getTripMultipleHotelList($where, $pageNum = 0, $field = "*", $order = "")
    {
        return TripMultipleHotelModel::getList($where, $pageNum, $field, $order);
    }

    /**
     * 获取一条数据
     * @param $where
     * @param string $field
     * @param string $order
     * @return mixed
     */
    public function getTripMultipleHotelFind($where, $field = "*", $order = "")
    {
        return TripMultipleHotelModel::getFind($where, $field, $order);
    }

    /**
     * 添加
     * @param $insert
     * @return mixed
     */
    public function addTripMultipleHotel($insert)
    {
        $data = TripMultipleHotelModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    /**
     * 修改
     * @param $update
     * @param array $where
     * @param array $field
     * @return TripMultipleHotelModel
     */
    public function updateTripMultipleHotel($update,$where = [],$field = [])
    {
        return TripMultipleHotelModel::update($update,$where,$field);
    }

    /**
     * 删除
     * @param $where
     * @return mixed
     */
    public function delTripMultipleHotel($where)
    {
        return TripMultipleHotelModel::whereDel($where);
    }
}