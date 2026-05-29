<?php

namespace app\AppFactory\Kernel\Model\Machine;

use app\AppFactory\Kernel\Model\BaseModel;

class PreReplenishmentOrderModel extends BaseModel
{
    protected $pk = 'id';
    protected $name = 'pre_replenishment_order';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
}
