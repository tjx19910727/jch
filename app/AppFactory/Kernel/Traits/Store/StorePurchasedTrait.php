<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/26
 * Time: 14:06
 */

namespace app\AppFactory\Kernel\Traits\Store;


use app\AppFactory\Kernel\Model\Store\StorePurchasedModel;

trait StorePurchasedTrait
{
    public function getStorePurchasedFind($where,$field = "*",$order = "")
    {
        return StorePurchasedModel::getFind($where,$field,$order);
    }

    public function getStorePurchasedList($where,$pageNum = 0,$field = "*",$order = "")
    {
        return StorePurchasedModel::getList($where,$pageNum,$field,$order);
    }

    public function addStorePurchased($insert)
    {
        $insert['creator'] = $this->manager['manager_id'];
        $sp = StorePurchasedModel::create($insert);
        return $sp->sp_id;
    }

    public function updateStorePurchased($update,$where = [],$field = [])
    {
        $update['update_id'] = $this->manager['manager_id'];
        return StorePurchasedModel::update($update,$where,$field);
    }

    public function delStorePurchased($where)
    {
        return StorePurchasedModel::destroy($where);
    }
}