<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/9/9
 * Time: 16:23
 */

namespace app\AppFactory\Kernel\Traits\Trip;



use app\AppFactory\Kernel\Model\Trip\TripMultipleModel;

trait TripMultipleTrait
{
    /**
     * 获取字段值
     * @param $where
     * @param $value
     * @return mixed
     */
    public function getTripMultipleValue($where, $value)
    {
        return TripMultipleModel::getFieldValue($where, $value);
    }

    /**
     * 获取单列
     * @param $where
     * @param $column
     * @return array
     */
    public function getTripMultipleColumn($where, $column)
    {
        return TripMultipleModel::getColumn($where, $column);
    }

    /**
     * 统计数量
     * @param $where
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function getTripMultipleCount($where)
    {
        return TripMultipleModel::getCount($where);
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
    public function getTripMultipleList($where, $pageNum = 0, $field = "*", $order = "")
    {
        return TripMultipleModel::getList($where, $pageNum, $field, $order);
    }

    /**
     * 获取一条数据
     * @param $where
     * @param string $field
     * @param string $order
     * @return mixed
     */
    public function getTripMultipleFind($where, $field = "*", $order = "")
    {
        return TripMultipleModel::getFind($where, $field, $order);
    }

    /**
     * 添加
     * @param $insert
     * @return mixed
     */
    public function addTripMultiple($insert)
    {
        if (!isset($insert['creator'])) {
            $insert['creator'] = $this->manager['manager_id'];
        }
        if (!isset($insert['ao_id'])) {
            $insert['ao_id'] = $this->manager['ao_id'];
        }
        $data = TripMultipleModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    /**
     * 修改
     * @param $update
     * @param array $where
     * @param array $field
     * @return TripMultipleModel
     */
    public function updateTripMultiple($update,$where = [],$field = [])
    {
        if (!isset($update['update_id'])) $update['update_id'] = $this->manager['manager_id'];
        return TripMultipleModel::update($update,$where,$field);
    }

    /**
     * 删除
     * @param $where
     * @return mixed
     */
    public function delTripMultiple($where)
    {
        return TripMultipleModel::whereDel($where);
    }
}