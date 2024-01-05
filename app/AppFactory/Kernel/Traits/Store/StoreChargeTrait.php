<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/15
 * Time: 16:24
 */

namespace app\AppFactory\Kernel\Traits\Store;


use app\AppFactory\Kernel\Model\Store\StoreChargeModel;

trait StoreChargeTrait
{
    /**
     * 检查有没有存在收费记录
     * @param $where
     * @return bool
     */
    public function storeChargeBe($where)
    {
        return StoreChargeModel::be($where);
    }

    public function getStoreChargeFind($where,$field = "",$order ="")
    {
        return StoreChargeModel::getFind($where,$field,$order);
    }

    public function getStoreChargeList($where,$pageNum = 0, $field = "", $order = "")
    {
        return StoreChargeModel::getList($where,$pageNum,$field,$order);
    }

    public function addStoreCharge($insert)
    {
        if (!isset($insert['creator'])) $insert['creator'] = $this->manager['manager_id'] ?? 0;
        $sc = StoreChargeModel::create($insert);
        return $sc->id;
    }

    public function updateStoreCharge($update,$where = [], $field = [])
    {
        return StoreChargeModel::update($update,$where,$field);
    }


    // 检查门店收费记录
    public function checkStoreCharge($where)
    {
        $sc = $this->getStoreChargeFind($where,"*","id desc");
        if (!$sc) return $this->rFail("查无门店收费记录，请先缴费！");
        $sc = $sc->toArray();
        if ($sc['expire_time'] < time()) return $this->rFail("收费记录已过期");
        return $sc;
    }

}