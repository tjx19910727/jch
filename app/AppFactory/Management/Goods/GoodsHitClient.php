<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/2/26
 * Time: 17:32
 */

namespace app\AppFactory\Management\Goods;


use app\AppFactory\Kernel\Traits\Goods\GoodsHitTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersGoodsCountTrait;
use app\AppFactory\Management\ManagementClient;

class GoodsHitClient extends ManagementClient
{
    use GoodsHitTrait,SaleOrdersGoodsCountTrait;

    public function getTotalList($where,$pageNum = 0,$field = "*",$order = "conversion_rate desc")
    {
        $return = $this->rQ($this->getGoodsHitList($where,$pageNum,$field,$order,function ($item) {
            $item['saleNum'] = $this->getSaleOrdersGoodsCountSum(['g_id' => $item['g_id']],'totalQuantity');
            $item['conversion_rate'] = ($item['saleNum'] > 0 ? bcmul(bcdiv($item['saleNum'],$item['hits'],3),100,1) : 0) . "%";
            return $item;
        },"g_id"));
        return $return;
    }
}