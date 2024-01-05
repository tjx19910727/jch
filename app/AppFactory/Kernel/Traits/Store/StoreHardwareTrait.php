<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/26
 * Time: 13:57
 */

namespace app\AppFactory\Kernel\Traits\Store;


use app\AppFactory\Kernel\Model\Store\StoreHardwareModel;

trait StoreHardwareTrait
{
    /**
     * 获取门店硬件字段数据
     * @param $where
     * @param $value
     * @return mixed
     */
    public function getStoreHardwareValue($where,$value)
    {
        return StoreHardwareModel::getFieldValue($where,$value);
    }

    public function getStoreHardwareFind($where,$field = "*",$order = "")
    {
        return StoreHardwareModel::getFind($where,$field,$order);
    }

    public function getStoreHardwareList($where,$pageNum = 0, $field = "*", $order = "")
    {
        return StoreHardwareModel::getList($where,$pageNum,$field,$order);
    }

    public function addStoreHardware($insert)
    {
        if (isset($this->manager['manager_id']) && !isset($insert['creator'])) $insert['creator'] = $this->manager['manager_id'];
        $sh = StoreHardwareModel::create($insert);
        return $sh->sh_id;
    }

    public function updateStoreHardware($update,$where = [], $field = [])
    {
        if (isset($this->manager['manager_id']) && !isset($update['update_id']))  $update['update_id'] = $this->manager['manager_id'];
        $result = StoreHardwareModel::update($update,$where,$field);
        return $result;
    }

    public function delStoreHardware($where)
    {
        return StoreHardwareModel::destroy($where);
    }
}