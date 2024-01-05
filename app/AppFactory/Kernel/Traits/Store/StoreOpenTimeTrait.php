<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/26
 * Time: 14:03
 */

namespace app\AppFactory\Kernel\Traits\Store;


use app\AppFactory\Kernel\Model\Store\StoreOpenTimeModel;

trait StoreOpenTimeTrait
{
    public function getStoreOpenTimeFind($where,$field = "*",$order = "")
    {
        return StoreOpenTimeModel::getFind($where,$field,$order);
    }

    public function getStoreOpenTimeList($where,$pageNum = 0,$field = "*",$order = "")
    {
        return StoreOpenTimeModel::getList($where,$pageNum,$field,$order);
    }

    public function addStoreOpenTime($insert)
    {
        $insert['creator'] = $this->manager['manager_id'];
        $sot = StoreOpenTimeModel::create($insert);
        return $sot->sot_id;
    }

    public function updateStoreOpenTime($update,$where = [],$field = [])
    {
        return StoreOpenTimeModel::update($update,$where,$field);
    }

    public function delStoreOpenTime($where)
    {
        return StoreOpenTimeModel::destroy($where);
    }

    public function isStoreOpen($store_id)
    {
        $now = date("H:i:s");
        $sec = HourMinuteSec2int($now);
        $where['store_id'] = $store_id;
        $where[] = ['start_time',"<=" ,$sec];
        $where[] = ['end_time','>=',$sec];
        $openTime = $this->getStoreOpenTimeFind($where,'sot_id,store_id,start_time,end_time','sot_id desc');
        if ($openTime) {
            return $openTime;
        }
        return false;
    }
}