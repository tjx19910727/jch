<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/9
 * Time: 15:08
 */

namespace app\AppFactory\Kernel\Traits\SaleOrders;


use app\AppFactory\Kernel\Model\SaleOrders\SaleOrdersDailyCountView;

trait SaleOrdersDailyCountTrait
{
    public function getSaleOrdersDailyCountList($where,$pageNum = 0, $field = "*", $order = "",$group = "")
    {
        return SaleOrdersDailyCountView::getList($where,$pageNum,$field,$order,'',$group);
    }
}