<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/3
 * Time: 13:54
 */

namespace app\AppFactory\Kernel\Traits\SaleOrders;



use app\AppFactory\Kernel\Model\SaleOrders\SaleHotelModel;

trait SaleHotelTrait
{
    /**
     * 获取字段值
     * @param $where
     * @param $value
     * @return mixed
     */
    public function getSaleHotelValue($where, $value)
    {
        return SaleHotelModel::getFieldValue($where, $value);
    }

    /**
     * 获取单列
     * @param $where
     * @param $column
     * @return array
     */
    public function getSaleHotelColumn($where, $column)
    {
        return SaleHotelModel::getColumn($where, $column);
    }

    /**
     * 统计数量
     * @param $where
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function getSaleHotelCount($where)
    {
        return SaleHotelModel::getCount($where);
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
    public function getSaleHotelList($where, $pageNum = 0, $field = "*", $order = "")
    {
        return SaleHotelModel::getList($where, $pageNum, $field, $order);
    }

    public function getHotelJoinSaleOrdersList($where,$pageNum = 0,$field = "*", $order = "")
    {
        return SaleHotelModel::joinSaleOrdersList($where,$pageNum,$field,$order);
    }

    /**
     * 获取一条数据
     * @param $where
     * @param string $field
     * @param string $order
     * @return mixed
     */
    public function getSaleHotelFind($where, $field = "*", $order = "")
    {
        return SaleHotelModel::getFind($where, $field, $order);
    }

    /**
     * 获取自营酒店核销码
     * @return mixed
     * @throws \think\db\exception\DbException
     */
    public function getHotelCheckOffCode()
    {
        while(1){
            $code = $this->get_rand_string(8,'num');
            if (!SaleHotelModel::getCount(['checkOff_code' => $code,'checkOff_status' => 1])) {
                break;
            }
        }
        return $code;
    }

    /**
     * 添加
     * @param $insert
     * @return mixed
     */
    public function addSaleHotel($insert)
    {
        $data = SaleHotelModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    /**
     * 修改
     * @param $update
     * @param array $where
     * @param array $field
     * @return SaleHotelModel
     */
    public function updateSaleHotel($update,$where = [],$field = [])
    {
        return SaleHotelModel::update($update,$where,$field);
    }

    /**
     * 删除
     * @param $where
     * @return mixed
     */
    public function delSaleHotel($where)
    {
        return SaleHotelModel::whereDel($where);
    }
}