<?php

namespace app\AppFactory\Kernel\Traits\Warehouse;

use app\AppFactory\Kernel\Model\Warehouse\WarehouseTransDetailsModel;
use app\AppFactory\Kernel\Model\Warehouse\WarehouseTransModel;

trait WarehouseTransTrait
{
    public function getWarehouseTransFind($where, $field = '*', $order = '')
    {
        return WarehouseTransModel::getFind($where, $field, $order);
    }

    public function getWarehouseTransList($where, $pageNum = 0, $field = '*', $order = 'id desc')
    {
        return WarehouseTransModel::getList($where, $pageNum, $field, $order);
    }

    public function getWarehouseTransDetailsList($where, $field = '*', $order = 'id asc')
    {
        return WarehouseTransDetailsModel::getList($where, 0, $field, $order);
    }
}

