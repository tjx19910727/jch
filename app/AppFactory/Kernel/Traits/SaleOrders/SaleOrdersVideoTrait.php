<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/8/7
 * Time: 14:17
 */

namespace app\AppFactory\Kernel\Traits\SaleOrders;


use app\AppFactory\Kernel\Model\SaleOrders\SaleOrdersVideoModel;

/**
 * 补扣订单监控数据
 * Trait SaleOrdersVideoTrait
 * @package app\AppFactory\Kernel\Traits\SaleOrders
 */
trait SaleOrdersVideoTrait
{
    /**
     * 查询订单监控数据
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return SaleOrdersVideoModel|SaleOrdersVideoModel[]|array|\think\Collection|\think\Paginator
     * @throws \Exception
     */
    public function getSaleOrdersVideoList($where,$pageNum = 0, $field = '*', $order = 'order_id desc')
    {
        return SaleOrdersVideoModel::getList($where,$pageNum,$field,$order);
    }


    public function getSaleOrdersVideoFind($where,$field = '*', $order = 'order_id desc')
    {
        return SaleOrdersVideoModel::getFind($where,$field,$order);
    }

    public function addSaleOrdersVideo($insert)
    {
        if (!isset($insert['creator']) && $this->manager['manager_id']) $insert['creator'] = $this->manager['manager_id'];
        $sov = SaleOrdersVideoModel::create($insert);
        return $sov->order_id;
    }

    public function updateSaleOrdersVideo($update, $where = [], $field = [])
    {
        return SaleOrdersVideoModel::update($update,$where,$field);
    }
}