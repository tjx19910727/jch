<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/20
 * Time: 10:56
 */

namespace app\AppFactory\Management\Goods;


use app\AppFactory\Kernel\Support\Excel;
use app\AppFactory\Kernel\Traits\Auth\AuthManagerMachineTrait;
use app\AppFactory\Kernel\Traits\Auth\AuthManagerTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsLangTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersGoodsCountTrait;
use app\AppFactory\Management\ManagementClient;

class GoodsClient extends ManagementClient
{
    use GoodsTrait, GoodsLangTrait;
    use AuthManagerTrait;
    use SaleOrdersGoodsCountTrait;

    public function addG($postData)
    {
        $g_id = $this->addGoods($postData);
        if ($g_id) {
            $insertLang = [
                "g_id" => $g_id,
                "g_name" => $postData['g_name'] ?? "",
                "gc_id" => $postData['gc_id'] ?? "",
                "gc_name" => $postData['gc_name'] ?? "",
                "manufacturer" => $postData['manufacturer'] ?? "",
                "desc" => $postData['desc'] ?? "",
                "performance" => $postData['performance'] ?? "",
                "lang" => "zh-cn",
            ];
            $this->addGoodsLang($insertLang);
        }
        return $this->rA($g_id);
    }

    public function getPageList($where, $pageNum = 0, $field = "*", $order = "")
    {
        $data = $this->getGoodsList($where, $pageNum, $field, $order);
        return $this->rQ($data);
    }

    /**
     * 概览——商品前10排行榜
     * @param $where
     * @return array|string
     */
    public function get10List($where)
    {
        $list = $this->getSaleOrdersGoodsCountList($where, 0,
            'g_name,totalPrice,totalQuantity,retail_price,pic',
            'totalPrice desc,totalQuantity desc, g_id desc', '', '', 10);
        if ($list) {
            $list = $list->toArray();
        }
        return $this->rQ($list);
    }

    public function exportRankingList($where)
    {
        $list = $this->getSaleOrdersGoodsCountList($where, 0,
            'g_name,totalPrice,totalQuantity,retail_price,pic',
            'totalPrice desc,totalQuantity desc, g_id desc', '', '', 10);
        if ($list) {
            $list = $list->toArray();
            $title = [
                "g_name" => "商品名称",
                "totalQuantity" => "销售量",
                "retail_price" => "单价",
            ];
            $filename = "人气商品排行榜（最近7天）-" . date("YmdHis");
            $result = $this->sendToExport("首页-人气商品排行榜（最近7天）", $filename, $title, $list);
            return $result;
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
        try {
            $path = root_path() . "public" . $data['file_path'];
            $title = ["g_name", "gc_id", "gc_name", "model", "sku", "sku2", "pic", "bar_code", "cost_price", "market_price", "retail_price", "manufacturer", "service_phone", "status"];
            $other = ['creator' => $this->manager['manager_id'] ?? 0, 'ao_id' => $this->manager['ao_id'] ?? 0];
            $goods = Excel::importExcel($path, $title, $other);
            actionLog($goods, '导入的商品数据');
            if ($goods) {
                $result = $this->addMoreGoods($goods);
                return $this->rAction($result);
            }
            return $this->r(100, '获取不到Excel文档中的数据');
        } catch (\Exception $e) {
            actionException($e, 1);
            return $this->rValidate($e->getMessage());
        }
    }

    /**
     * 导出商品
     * @param $where
     * @return array|string
     */
    public function exportExcel($where)
    {
        $list = $this->getGoodsList($where, 0,
            'g_id,g_name,model,sku,cost_price,market_price,retail_price');
        if ($list) {
            $list = $list->toArray();
            $title = [
                'g_id' => "商品ID",
                'g_name' => "商品名称",
                'model' => "商品型号",
                'sku' => "SKU码",
                'cost_price' => "成本价",
                'market_price' => "市场价",
                'retail_price' => "零售价",
            ];
            $filename = "商品列表-" . date("Ymd");
            $result = $this->sendToExport("商品管理-商品列表", $filename, $title, $list);
            return $result;
        }
        return $this->r(100, $this->lang("action_fail"));
    }
}