<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/5
 * Time: 17:40
 */

namespace app\AppFactory\Kernel\Traits\Activity;


use app\AppFactory\Kernel\Model\Activity\ActivityGoodsModel;

trait ActivityGoodsTrait
{
    public function getActivityGoodsColumn($where,$column)
    {
        return ActivityGoodsModel::getColumn($where,$column);
    }

    public function getActivityGoodsFind($where,$field = "*",$order = "")
    {
        return ActivityGoodsModel::getFind($where,$field,$order);
    }

    public function getActivityGoodsList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "")
    {
        return ActivityGoodsModel::getList($where,$pageNum,$field,$order,$eachFun);
    }

    public function getActivityGoodsListByMachine($where,$field = "*", $order = "")
    {
        return ActivityGoodsModel::getListByMachine($where,$field,$order);
    }

    /**
     * 添加营销活动适用商品
     * activity_coupon
     * @param $insert
     * @param $goodsList
     * @return bool|string
     */
    public function addAg($insert,$goodsList)
    {
        $goodsList = json2arr($goodsList);
        if (!$goodsList) {
            return "请选择商品";
        }
        $oldAg = $this->getActivityGoodsColumn($insert,"g_id");
        $addAg = array_diff($goodsList,$oldAg);
        $delAg = array_diff($oldAg,$goodsList);
        if ($addAg) {
            $insertAgAll = [];
            foreach ($addAg as $gv) {
                $g = $this->getGoodsFind(['g_id' => $gv], 'g_id,g_name,pic,sku,market_price,retail_price,gc_id,gc_name');
                if (!$g) {
                    return "查无商品信息：" . $gv;
                }
                $g = $g->toArray();
                $ag = $this->getActivityGoodsFind(array_merge($insert, ['g_id' => $g['g_id']]));
                if (!$ag) {
                    $insertAg = array_merge($insert, $g);
                    $insertAgAll[] = $insertAg;
                }
            }
            if ($insertAgAll) $this->addActivityGoodsMore($insertAgAll);
        }
        if ($delAg) $this->delActivityGoods(array_merge($insert,[['g_id',"in",$delAg]]));
        return true;
    }

    /** 保存微程线上商品活动关联，source_no 使用微程父商品 out_no。 */
    public function addOnlineAg($insert, $onlineGoodsList)
    {
        $onlineGoodsList = json2arr($onlineGoodsList);
        if (!$onlineGoodsList) return true;
        foreach ($onlineGoodsList as $item) {
            $sourceNo = is_array($item) ? trim(strval($item['source_no'] ?? $item['out_no'] ?? '')) : trim(strval($item));
            if ($sourceNo === '') return '线上商品编码不能为空';
            $goods = $this->getWcGoodsFind(['no' => $sourceNo]);
            if (!$goods) return '查无线上商品信息：' . $sourceNo;
            $goods = is_object($goods) && method_exists($goods, 'toArray') ? $goods->toArray() : (array)$goods;
            $where = array_merge($insert, ['goods_source' => 2, 'source_no' => $sourceNo]);
            if (!$this->getActivityGoodsFind($where)) {
                $this->addActivityGoods(array_merge($where, [
                    'g_id' => 0,
                    'g_name' => $goods['name'] ?? '',
                    'pic' => $goods['pic'] ?? '',
                    'sku' => $sourceNo,
                    'market_price' => $goods['price'] ?? 0,
                    'retail_price' => $goods['price'] ?? 0,
                ]));
            }
        }
        return true;
    }

    public function addActivityGoodsMore($all)
    {
        $ag = new ActivityGoodsModel();
        return $ag->saveAll($all);
    }

    public function addActivityGoods($insert)
    {
        $data = ActivityGoodsModel::create($insert);
        return $data->ag_id;
    }

    public function updateActivityGoods($update,$where = [],$field = [])
    {
        return ActivityGoodsModel::update($update,$where,$field);
    }

    public function delActivityGoods($where)
    {
        $result = ActivityGoodsModel::whereDel($where);
        return $result;
    }
}
