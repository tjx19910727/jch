<?php

namespace app\AppFactory\Kernel\Traits\WeiCheng;

use app\AppFactory\Kernel\Model\WeiCheng\WcUserLoginInfoModel;

trait WcUserLoginInfoTrait
{
    public function getWcUserLoginInfoFind($where, $field = "*", $order = "")
    {
        return WcUserLoginInfoModel::getFind($where, $field, $order);
    }

    public function addWcUserLoginInfo($insert)
    {
        $data = WcUserLoginInfoModel::create($insert);
        return $data->wuli_id;
    }

    public function updateWcUserLoginInfo($update, $where = [], $field = [])
    {
        return WcUserLoginInfoModel::update($update, $where, $field);
    }
}
