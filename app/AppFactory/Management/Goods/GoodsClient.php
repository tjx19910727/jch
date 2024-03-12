<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/20
 * Time: 10:56
 */

namespace app\AppFactory\Management\Goods;


use app\AppFactory\Kernel\Support\Excel;
use app\AppFactory\Kernel\Traits\Auth\AuthManagerTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersGoodsCountTrait;
use app\AppFactory\Management\ManagementClient;

class GoodsClient extends ManagementClient
{
    use GoodsTrait;
    use AuthManagerTrait;
    use SaleOrdersGoodsCountTrait;

    public function getPageList($where,$pageNum = 0,$field = "*",$order = "")
    {
        $data = $this->getGoodsList($where,$pageNum,$field,$order);
        return $this->rQ($data);
    }

    /**
     * 概览——商品前10排行榜
     * @param $where
     * @return array|string
     */
    public function get10List($where)
    {
        $list = $this->getSaleOrdersGoodsCountList($where,0,
            'g_name,totalPrice,totalQuantity,retail_price,pic',
            'totalPrice desc,totalQuantity desc, g_id desc','','',2);
        if ($list) {
            $list = $list->toArray();
        }
        return $this->rQ($list);
    }

    /**
     * 导入Excel
     * @param $data
     * @return array|string
     */
    public function importExcel($data)
    {
        $path = root_path() . "public" . $data['file_path'];
        $title = ["g_name","gc_name","model","sku","sku2","pic","bar_code","cost_price","market_price","retail_price","manufacturer","service_phone","status"];
        $other = ['creator' => $this->manager['manager_id'] ?? 0,'ao_id' => $this->manager['ao_id'] ?? 0];
        $goods = Excel::importExcel($path,$title,$other);
        $result = "";
        if ($goods) {
            $result = $this->addMoreGoods($goods);
        }
        return $this->rAction($result);
    }
}