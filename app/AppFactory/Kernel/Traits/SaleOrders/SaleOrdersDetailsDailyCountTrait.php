<?php

/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 2026/3/19
 * Time: 15:08
 */

namespace app\AppFactory\Kernel\Traits\SaleOrders;


use app\AppFactory\Kernel\Model\SaleOrders\SaleOrdersDetailsDailyCountView;

trait SaleOrdersDetailsDailyCountTrait
{
    public function getSaleOrdersDetailsDailyCountFind($where, $field = "*", $order = "", $group = "")
    {
        $data = SaleOrdersDetailsDailyCountView::getFind($where, $field, $order, $group);
        return $data;
    }

    public function getSaleOrdersDetailsDailyCountList($where, $pageNum = 0, $field = "*", $order = "", $group = "")
    {
        return SaleOrdersDetailsDailyCountView::getList($where, $pageNum, $field, $order, '', $group);
    }
}
