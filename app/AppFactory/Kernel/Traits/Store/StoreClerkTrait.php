<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/26
 * Time: 13:52
 */

namespace app\AppFactory\Kernel\Traits\Store;


use app\AppFactory\Kernel\Model\Store\StoreClerkModel;

trait StoreClerkTrait
{
    public function getStoreClerkFind($where,$field = "*",$order = "")
    {
        return StoreClerkModel::getFind($where,$field,$order);
    }

    public function getStoreClerkList($where,$pageNum = 0,$field = "*",$order = "")
    {
        return StoreClerkModel::getList($where,$pageNum,$field,$order);
    }

    public function getStoreClerkColumn($where,$column)
    {
        return StoreClerkModel::getColumn($where,$column);
    }

    public function addStoreClerk($insert)
    {
        $insert['creator'] = $this->manager['manager_id'];
        $sc = StoreClerkModel::create($insert);
        return $sc->sc_id;
    }

    public function updateStoreClerk($update,$where = [],$field = [])
    {
        $update['update_id'] = $this->manager['manager_id'];
        $result = StoreClerkModel::update($update,$where,$field);
        return $result;
    }

    public function delStoreClerk($where)
    {
        return StoreClerkModel::destroy($where);
    }
}