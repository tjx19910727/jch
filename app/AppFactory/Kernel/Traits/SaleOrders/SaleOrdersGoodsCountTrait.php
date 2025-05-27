<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/11
 * Time: 20:10
 */

namespace app\AppFactory\Kernel\Traits\SaleOrders;


use app\AppFactory\Kernel\Model\SaleOrders\SaleOrdersGoodsCountView;

trait SaleOrdersGoodsCountTrait
{
    public function getSaleOrdersGoodsCountSum($where,$sum)
    {
        return SaleOrdersGoodsCountView::getSum($where,$sum);
    }

    public function getSaleOrdersGoodsCountList($where,$pageNum = 0, $field = "", $order = "", $eachFunc = "",$group = "",$limit = "")
    {
        return SaleOrdersGoodsCountView::getList($where,$pageNum,$field,$order,$eachFunc,$group,$limit);
    }
}