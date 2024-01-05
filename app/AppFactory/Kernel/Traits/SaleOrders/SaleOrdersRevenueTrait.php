<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/11
 * Time: 15:13
 */

namespace app\AppFactory\Kernel\Traits\SaleOrders;


use app\AppFactory\Kernel\Model\SaleOrders\SaleOrdersRevenueModel;

trait SaleOrdersRevenueTrait
{
    public function saleOrdersRevenueBe($where)
    {
        return SaleOrdersRevenueModel::be($where);
    }

    public function getSaleOrdersRevenueFind($where,$field = "*", $order = "")
    {
        return SaleOrdersRevenueModel::getFind($where,$field,$order);
    }

    public function getSaleOrdersRevenueList($where,$pageNum = 0, $field = "*", $order = "sor_id desc")
    {
        return SaleOrdersRevenueModel::getList($where,$pageNum,$field,$order);
    }

    public function addSaleOrdersRevenue($insert)
    {
        $sor = SaleOrdersRevenueModel::create($insert);
        return $sor->sor_id;
    }

    public function updateSaleOrdersRevenue($update,$where = [], $field = [])
    {
        return SaleOrdersRevenueModel::update($update,$where,$field);
    }
}