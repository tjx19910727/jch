<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/21
 * Time: 8:59
 */

namespace app\AppFactory\Kernel\Traits\Warehouse;


use app\AppFactory\Kernel\Model\Warehouse\WarehouseOrderGoodsModel;

trait WarehouseOrderGoodsTrait
{
    /**
     * 获取仓库订单商品列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return WarehouseOrderGoodsModel|WarehouseOrderGoodsModel[]|array|\think\Collection|\think\Paginator
     */
    public function getWarehouseOrderGoodsList($where,$pageNum = 0, $field = "*", $order = "")
    {
        return WarehouseOrderGoodsModel::getList($where,$pageNum,$field,$order);
    }
}