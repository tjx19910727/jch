<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/10/7
 * Time: 14:50
 */

namespace app\AppFactory\Kernel\Traits\Advertisement;


use app\AppFactory\Kernel\Model\Advertisement\AdvertisementResourceModel;

trait AdvertisementResourceTrait
{
    public function getAdvertisementResourceValue($where,$value)
    {
        return AdvertisementResourceModel::getFieldValue($where,$value);
    }

    public function getAdvertisementResourceFind($where,$field = "*", $order = "res_id desc")
    {
        return AdvertisementResourceModel::getFind($where,$field,$order);
    }

    public function getAdvertisementResourceList($where,$pageNum = 0, $field = "*", $order = "res_id desc")
    {
        return AdvertisementResourceModel::getList($where,$pageNum,$field,$order);
    }

    public function addAdvertisementResource($insert)
    {
        !isset($this->manager['manager_id']) ? : $insert['creator'] = 0;
        $res = AdvertisementResourceModel::create($insert);
        return $res->res_id;
    }

    public function updateAdvertisementResource($update,$where = [], $field = [])
    {
        !isset($this->manager['manager_id']) ?: $update['update_id'] = $this->manager['manager_id'];
        return AdvertisementResourceModel::update($update,$where,$field);
    }

    public function delAdvertisementResource($where)
    {
        return AdvertisementResourceModel::destroy($where);
    }
}