<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/3
 * Time: 13:55
 */

namespace app\AppFactory\Kernel\Traits\SaleOrders;



use app\AppFactory\Kernel\Model\SaleOrders\SaleHotelNightlyModel;

trait SaleHotelNightlyTrait
{
    /**
     * 获取字段值
     * @param $where
     * @param $value
     * @return mixed
     */
    public function getSaleHotelNightlyValue($where, $value)
    {
        return SaleHotelNightlyModel::getFieldValue($where, $value);
    }

    /**
     * 获取单列
     * @param $where
     * @param $column
     * @return array
     */
    public function getSaleHotelNightlyColumn($where, $column)
    {
        return SaleHotelNightlyModel::getColumn($where, $column);
    }

    /**
     * 统计数量
     * @param $where
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function getSaleHotelNightlyCount($where)
    {
        return SaleHotelNightlyModel::getCount($where);
    }

    /**
     * 获取列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return \app\AppFactory\Kernel\Model\BaseModel|\app\AppFactory\Kernel\Model\BaseModel[]|array|string|\think\Collection|\think\Paginator
     */
    public function getSaleHotelNightlyList($where, $pageNum = 0, $field = "*", $order = "")
    {
        return SaleHotelNightlyModel::getList($where, $pageNum, $field, $order);
    }

    /**
     * 获取一条数据
     * @param $where
     * @param string $field
     * @param string $order
     * @return mixed
     */
    public function getSaleHotelNightlyFind($where, $field = "*", $order = "")
    {
        return SaleHotelNightlyModel::getFind($where, $field, $order);
    }

    /**
     * 添加
     * @param $insert
     * @return mixed
     */
    public function addSaleHotelNightly($insert)
    {
        $data = SaleHotelNightlyModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    /**
     * 修改
     * @param $update
     * @param array $where
     * @param array $field
     * @return SaleHotelNightlyModel
     */
    public function updateSaleHotelNightly($update,$where = [],$field = [])
    {
        return SaleHotelNightlyModel::update($update,$where,$field);
    }

    /**
     * 删除
     * @param $where
     * @return mixed
     */
    public function delSaleHotelNightly($where)
    {
        return SaleHotelNightlyModel::whereDel($where);
    }
}