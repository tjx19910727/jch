<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/2/26
 * Time: 17:32
 */

namespace app\AppFactory\Management\Goods;


use app\AppFactory\Kernel\Support\Excel;
use app\AppFactory\Kernel\Traits\Goods\GoodsHitTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersGoodsCountTrait;
use app\AppFactory\Management\ManagementClient;

class GoodsHitClient extends ManagementClient
{
    use GoodsHitTrait,SaleOrdersGoodsCountTrait;

    public function getTotalList($where,$pageNum = 0,$field = "*",$order = "")
    {
        $return = $this->rQ($this->getGoodsHitList($where,$pageNum,$field,$order,function ($item) {
            $item['saleNum'] = $this->getSaleOrdersGoodsCountSum(['g_id' => $item['g_id']],'totalQuantity');
            $item['conversion_rate'] = ($item['saleNum'] > 0 ? bcmul(bcdiv($item['saleNum'],$item['hits'],3),100,1) : 0) . "%";
            return $item;
        },"g_id"));
        return $return;
    }

    public function export($where,$eType = 1)
    {
        $field = "*";
        $order = "";
        $group = "";
        if ($eType == 1) {
            $group = "g_id";
            $field = "g_id,g_name,sku,gc_name,count(gh_id) hits";
        }
        if ($eType == 2) {
            $field = 'machine_id,machine_name,g_name,gc_name,sku,g_id,count(gh_id) hits, date_format(create_date,"%Y-%m-%d") create_date';
            $group = "m_id,g_id,create_date";
        }
        $list = $this->getGoodsHitList($where,0,$field,$order,'',$group);
        if ($list) {
            $list = $list->toArray();
            foreach ($list as $key => $item) {
                $item['saleNum'] = $this->getSaleOrdersGoodsCountSum(['g_id' => $item['g_id']],'totalQuantity');
                $item['conversion_rate'] = ($item['saleNum'] > 0 ? bcmul(bcdiv($item['saleNum'],$item['hits'],3),100,1) : 0) . "%";
                $list[$key] = $item;
            }
            if ($eType == 1) {
                $title = [
                    "sku" => "SKU",
                    "g_name" => "商品名称",
                    "gc_name" => "品类",
                    "hits" => "点击数",
                    "saleNum" => "销量",
                    "conversion_rate" => "转化率",
                ];
                $filename = "互动报表(按商品)-" . date("Ymd");
            }
            if ($eType == 2) {
                $title = [
                    "machine_id" => "设备编号",
                    "machine_name" => "设备名称",
                    "sku" => "SKU",
                    "g_name" => "商品名称",
                    "gc_name" => "品类",
                    "hits" => "点击数",
                    "saleNum" => "销量",
                    "conversion_rate" => "转化率",
                ];
                $filename = "互动报表(按设备)-" . date("Ymd");
            }
            return $this->sendToExport("统计报表-互动报表", $filename, $title, $list);
        }
        return $this->rFail();
    }
}