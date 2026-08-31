<?php

namespace app\AppFactory\Kernel\Model\Warehouse;

use app\AppFactory\Kernel\Model\BaseModel;

class WarehouseTransModel extends BaseModel
{
    protected $pk = 'id';
    protected $name = 'warehouse_trans';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
}

