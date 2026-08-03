<?php

namespace app\AppFactory\Kernel\Model\Warehouse;

use app\AppFactory\Kernel\Model\BaseModel;

class WarehouseTransDetailsModel extends BaseModel
{
    protected $pk = 'id';
    protected $name = 'warehouse_trans_details';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
}

