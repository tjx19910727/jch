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
        foreach ($goodsList as $gv) {
            $g = $this->getGoodsFind(['g_id' => $gv], 'g_id,g_name,pic,sku,market_price,retail_price,gc_id,gc_name');
            if (!$g) {
                return "查无商品信息：" . $gv;
            }
            $g = $g->toArray();
            $insertAg = array_merge($insert, $g);
            $insertAgAll[] = $insertAg;
        }
        $this->addActivityGoodsMore($insertAgAll);
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