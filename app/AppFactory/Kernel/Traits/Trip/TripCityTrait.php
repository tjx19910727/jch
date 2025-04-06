<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/3
 * Time: 14:32
 */

namespace app\AppFactory\Kernel\Traits\Trip;



use app\AppFactory\Kernel\Model\Trip\TripCityModel;

trait TripCityTrait
{
    /**
     * 获取字段值
     * @param $where
     * @param $value
     * @return mixed
     */
    public function getTripCityValue($where, $value)
    {
        return TripCityModel::getFieldValue($where, $value);
    }

    /**
     * 获取单列
     * @param $where
     * @param $column
     * @return array
     */
    public function getTripCityColumn($where, $column)
    {
        return TripCityModel::getColumn($where, $column);
    }

    /**
     * 统计数量
     * @param $where
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function getTripCityCount($where)
    {
        return TripCityModel::getCount($where);
    }

    /**
     * 获取列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return \app\AppFactory\Kernel\Model\BaseModel|\app\AppFactory\Kernel\Model\BaseModel[]|array|string|\think\Collection|\think\Paginator
     */
    public function getTripCityList($where, $pageNum = 0, $field = "*", $order = "")
    {
        return TripCityModel::getList($where, $pageNum, $field, $order);
    }

    /**
     * 获取一条数据
     * @param $where
     * @param string $field
     * @param string $order
     * @return mixed
     */
    public function getTripCityFind($where, $field = "*", $order = "")
    {
        return TripCityModel::getFind($where, $field, $order);
    }

    /**
     * 添加
     * @param $insert
     * @return mixed
     */
    public function addTripCity($insert)
    {
        $data = TripCityModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    /**
     * 修改
     * @param $update
     * @param array $where
     * @param array $field
     * @return TripCityModel
     */
    public function updateTripCity($update,$where = [],$field = [])
    {
        return TripCityModel::update($update,$where,$field);
    }

    /**
     * 删除
     * @param $where
     * @return mixed
     */
    public function delTripCity($where)
    {
        return TripCityModel::whereDel($where);
    }
}