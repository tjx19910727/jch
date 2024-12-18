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
            'g_id,g_name,totalPrice,totalQuantity,retail_price,pic',
            'totalPrice desc,totalQuantity desc, g_id desc', '', '', 10);
        if ($list) {
            $list = $list->toArray();
            $lang = input("lang");
            if ($lang) {
                $whereGl['lang'] = $lang;
                foreach ($list as $key => $value) {
                    $whereGl['g_id'] = $value['g_id'];
                    $gl = $this->getGoodsLangFind($whereGl, 0, 'g_name');
                    if ($gl) {
                        $value['g_name'] = $gl['g_name'];
                    }
                    $list[$key] = $value;
                }
            }
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
                "g_name" => $this->lang("export.g_name"),
                "totalQuantity" => $this->lang("export.totalQuantity"),
                "retail_price" => $this->lang("export.retail_price"),
            ];
            $filename = $this->lang("export.goods10List") . date("Ymd");
            $result = $this->sendToExport($this->lang("export.goodsRankingFileName"), $filename, $title, $list);
            return $result;
        }
        return $this->rQ($list);
    }

    /**
     * 删除商品
     * @param $where
     * @return array|\think\response\Json
     */
    public function delG($where)
    {
        $goods = $this->getGoodsList($where,0,'g_id,pic,banner,details_pic,`desc`');
        if ($goods) {
            $goods = $goods->toArray();
            $delList = [];
            foreach ($goods as $key => $value) {
                if ($value['pic'] && file_exists($value['pic'])) {
                    $delList = array_merge($delList,$value['pic']);
                }
                if ($value['banner']) {
                    $delList = array_merge($delList,explode(";",$value['banner']));
                }
                if ($value['details_pic']) {
                    $delList = array_merge($delList,explode(";",$value['details_pic']));
                }
                if ($value['desc']) {
                    $delList = array_merge($delList,getImagesFromRichText($value['desc']));
                }
            }
            $result = $this->delGoods($where);
            if ($result) {
                foreach ($delList as $v) {
                    if (file_exists($v) && is_file($v)) {
                        @unlink($v);
                    }
                }
                return $this->r(200,$this->lang("del_success"));
            }
        }
        return $this->r(100,$this->lang("del_fail"));
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
            if (is_object($goods)) return $goods;
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
            'g_id,g_name,gc_name,
            (case g_type when 1 THEN "' . $this->lang("export.g_type1") .
            '" WHEN 2 THEN "' . $this->lang("export.g_type2") .
            '" WHEN 3 THEN "' . $this->lang("export.g_type3") .
            '" ELSE "' . $this->lang("export.g_type_unDefine") . '" END) g_type,
            model,sku,cost_price,market_price,retail_price');
        if ($list) {
            $list = $list->toArray();
            $title = [
                'g_id' => $this->lang("export.g_id"),
                'g_name' => $this->lang("export.g_name") ,
                'g_type' => $this->lang("export.g_type"),
                'gc_name' => $this->lang("export.gc_name"),
                'model' => $this->lang("export.model"),
                'sku' => $this->lang("export.sku"),
                'cost_price' => $this->lang("export.cost_price"),
                'market_price' => $this->lang("export.market_price"),
                'retail_price' => $this->lang("export.retail_price"),
            ];
            $filename =  $this->lang("export.goods_list") . "-" . date("Ymd");
            $result = $this->sendToExport($this->lang("menu.goods_management") . "-" . $this->lang("export.goods_list"), $filename, $title, $list);
            return $result;
        }
        return $this->r(100, $this->lang("action_fail"));
    }
}