<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/6/23
 * Time: 10:00
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Machine\OtaVersionModel;

trait OtaVersionTrait
{
    public function getOtaVersionFind($where, $field = "*", $order = "")
    {
        return OtaVersionModel::getFind($where, $field, $order);
    }

    public function getOtaVersionList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "")
    {
        return OtaVersionModel::getList($where, $pageNum, $field, $order, $eachFun);
    }

    public function addOtaVersion($insert)
    {
        !isset($this->manager['manager_id']) ? : $insert['creator'] = $this->manager['manager_id'];
        $data = OtaVersionModel::create($insert);
        return $data->ov_id;
    }

    public function updateOtaVersion($update, $where = [], $field = [])
    {
        !isset($this->manager['manager_id']) ? : $update['update_id'] = $this->manager['manager_id'];
        return OtaVersionModel::update($update, $where, $field);
    }

    public function delOtaVersion($where)
    {
        $result = OtaVersionModel::whereDel($where);
        return $result;
    }
}
