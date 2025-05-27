<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/2/28
 * Time: 10:35
 */

namespace app\AppFactory\Kernel\Traits\Advertisement;


use app\AppFactory\Kernel\Model\Advertisement\AdvertisementRecordModel;

trait AdvertisementRecordTrait
{

    public function getAdvertisementRecordFind($where,$field = "*",$order = "")
    {
        return AdvertisementRecordModel::getFind($where,$field,$order);
    }

    public function getAdvertisementRecordList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "")
    {
        return AdvertisementRecordModel::getList($where,$pageNum,$field,$order,$eachFun);
    }

    public function addAdvertisementRecord($insert)
    {
        $data = AdvertisementRecordModel::create($insert);
        return $data->ar_id;
    }

    public function updateAdvertisementRecord($update,$where = [],$field = [])
    {
        return AdvertisementRecordModel::update($update,$where,$field);
    }

    public function delAdvertisementRecord($where)
    {
        $result = AdvertisementRecordModel::whereDel($where);
        return $result;
    }
}