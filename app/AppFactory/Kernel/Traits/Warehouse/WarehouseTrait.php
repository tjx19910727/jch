<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/21
 * Time: 8:57
 */

namespace app\AppFactory\Kernel\Traits\Warehouse;


use app\AppFactory\Kernel\Model\Warehouse\WarehouseModel;

trait WarehouseTrait
{

    public function getWarehouseFind($where,$field = "*",$order = "")
    {
        return WarehouseModel::getFind($where,$field,$order);
    }

    public function getWarehouseList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "")
    {
        $result = WarehouseModel::getList($where,$pageNum,$field,$order,$eachFun);
        return $result;
    }

    public function addWarehouse($insert)
    {
//        $wh = $this->getWarehouseFind(['manager_id' => $insert['manager_id']]);
//        if ($wh) return $this->r(100,'账号仓库已存在，请勿重复添加');
        !isset($this->manager['manager_id']) ? : $insert['creator'] = $this->manager['manager_id'];
        $data = WarehouseModel::create($insert);
        return $data->wh_id;
    }

    public function updateWarehouse($update,$where = [],$field = [])
    {
        !isset($this->manager['manager_id']) ? : $update['update_id'] = $this->manager['manager_id'];
        return WarehouseModel::update($update,$where,$field);
    }

    public function delWarehouse($where)
    {
        return WarehouseModel::destroy($where);
    }
}