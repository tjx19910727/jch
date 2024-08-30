<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/26
 * Time: 15:35
 */

namespace app\AppFactory\Kernel\Traits\Machine;




use app\AppFactory\Kernel\Model\Machine\MachineFreeHotelModel;

trait MachineFreeHotelTrait
{
    /**
     * 获取字段值
     * @param $where
     * @param $value
     * @return mixed
     */
    public function getMachineFreeHotelValue($where, $value)
    {
        return MachineFreeHotelModel::getFieldValue($where, $value);
    }

    /**
     * 获取单列
     * @param $where
     * @param $column
     * @return array
     */
    public function getMachineFreeHotelColumn($where, $column)
    {
        return MachineFreeHotelModel::getColumn($where, $column);
    }

    /**
     * 统计数量
     * @param $where
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function getMachineFreeHotelCount($where)
    {
        return MachineFreeHotelModel::getCount($where);
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
    public function getMachineFreeHotelList($where, $pageNum = 0, $field = "*", $order = "")
    {
        return MachineFreeHotelModel::getList($where, $pageNum, $field, $order);
    }

    /**
     * 获取一条数据
     * @param $where
     * @param string $field
     * @param string $order
     * @return mixed
     */
    public function getMachineFreeHotelFind($where, $field = "*", $order = "")
    {
        return MachineFreeHotelModel::getFind($where, $field, $order);
    }

    /**
     * 添加
     * @param $insert
     * @return mixed
     */
    public function addMachineFreeHotel($insert)
    {
        $data = MachineFreeHotelModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    /**
     * 修改
     * @param $update
     * @param array $where
     * @param array $field
     * @return MachineFreeHotelModel
     */
    public function updateMachineFreeHotel($update,$where = [],$field = [])
    {
        return MachineFreeHotelModel::update($update,$where,$field);
    }

    /**
     * 删除
     * @param $where
     * @return mixed
     */
    public function delMachineFreeHotel($where)
    {
        return MachineFreeHotelModel::whereDel($where);
    }
}