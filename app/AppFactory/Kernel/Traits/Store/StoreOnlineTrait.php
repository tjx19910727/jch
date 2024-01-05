<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/28
 * Time: 17:56
 */

namespace app\AppFactory\Kernel\Traits\Store;


use app\AppFactory\Kernel\Model\Store\StoreOnlineDetailsModel;
use app\AppFactory\Kernel\Model\Store\StoreOnlineModel;

trait StoreOnlineTrait
{
    public function getStoreOnlineSum($where,$sum)
    {
        return StoreOnlineModel::getSum($where,$sum);
    }

    public function addStoreOnline($insert)
    {
        $online = StoreOnlineModel::create($insert);
        return $online->online_id;
    }

    public function getStoreOnlineFind($where,$field = "*",$order = "")
    {
        $data = StoreOnlineModel::getFind($where,$field,$order);
        return $data;
    }

    public function getStoreOnlineList($where,$pageNum = 0, $field = "*",$order = "")
    {
        return StoreOnlineModel::getList($where,$pageNum,$field,$order);
    }

    public function getStoreOnlineDetailsList($where,$pageNum = 0,$field = "*",$order = "",$eachFun = "",$group = "")
    {
        $data = StoreOnlineDetailsModel::getList($where,$pageNum,$field,$order,$eachFun,$group);
        return $data;
    }

    public function getStoreOnlineDetailsFind($where,$field = "*",$order = "")
    {
        $data = StoreOnlineDetailsModel::getFind($where,$field,$order);
        return $data;
    }

    public function addStoreOnlineDetails($insert)
    {
        $insert['d_date'] = strtotime(date("Y-m-d"));
        $sod = StoreOnlineDetailsModel::create($insert);
        return $sod->sod_id;
    }

    public function updateStoreOnlineDetails($update,$where = [],$field = [])
    {
        return StoreOnlineDetailsModel::update($update,$where,$field);
    }
}