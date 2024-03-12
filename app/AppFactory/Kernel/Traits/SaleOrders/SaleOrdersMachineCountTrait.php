<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/11
 * Time: 20:10
 */

namespace app\AppFactory\Kernel\Traits\SaleOrders;


use app\AppFactory\Kernel\Model\SaleOrders\SaleOrdersMachineCountView;

trait SaleOrdersMachineCountTrait
{

    public function getSaleOrdersMachineCountList($where,$pageNum = 0, $field = "", $order = "", $eachFunc = "",$group = "",$limit = "")
    {
        return SaleOrdersMachineCountView::getList($where,$pageNum,$field,$order,$eachFunc,$group,$limit);
    }
}