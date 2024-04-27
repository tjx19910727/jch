<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/4/22
 * Time: 15:22
 */

namespace app\AppFactory\Kernel\Traits\SaleOrders;


use app\AppFactory\Kernel\Model\SaleOrders\SaleOrdersUnclaimedModel;

trait SaleOrdersUnclaimedTrait
{

    public function getSaleOrdersUnclaimedFind($where,$field = "*", $order = "su_id desc")
    {
        return SaleOrdersUnclaimedModel::getFind($where,$field,$order);
    }

    public function getSaleOrdersUnclaimedSum($where,$sum)
    {
        return SaleOrdersUnclaimedModel::getSum($where,$sum);
    }

    public function getSaleOrdersUnclaimedList($where,$pageNum = 0, $field = "*", $order = "su_id desc")
    {
        return SaleOrdersUnclaimedModel::getList($where,$pageNum,$field,$order);
    }

    public function addSaleOrdersUnclaimed($insert)
    {
        $su = SaleOrdersUnclaimedModel::create($insert);
        actionLog($this->getLS(),'生成退款记录结果');
        return $su->su_id;
    }

    public function updateSaleOrdersUnclaimed($update, $where = [], $field = [])
    {
        return SaleOrdersUnclaimedModel::update($update,$where,$field);
    }

    public function delSaleOrdersUnclaimed($where)
    {
        return SaleOrdersUnclaimedModel::whereDel($where);
    }

}