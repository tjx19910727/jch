<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/9/27
 * Time: 11:41
 */

namespace app\AppFactory\Kernel\Traits\Store;


use app\AppFactory\Kernel\Model\Store\StoreHostingDetailsModel;

trait StoreHostingDetailsTrait
{
    public function getStoreHostingDetailsList($where,$pageNum = 0, $field = "*", $order = "shd_id desc")
    {
        return StoreHostingDetailsModel::getList($where,$pageNum,$field,$order);
    }

    public function getStoreHostingDetailsValue($where,$value,$order = "")
    {
        return StoreHostingDetailsModel::getFieldValue($where,$value,$order);
    }

    public function getStoreHostingDetailsFind($where,$field = "*", $order = "")
    {
        return StoreHostingDetailsModel::getFind($where,$field,$order);
    }

    public function addStoreHostingDetails($insert)
    {
        $details = StoreHostingDetailsModel::create($insert);
        return $details->shd_id;
    }

    public function getStoreHostingDetailsSum($where,$sum)
    {
        return StoreHostingDetailsModel::getSum($where,$sum);
    }
}