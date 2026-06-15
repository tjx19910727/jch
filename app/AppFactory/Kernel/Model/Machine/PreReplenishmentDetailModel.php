<?php

namespace app\AppFactory\Kernel\Model\Machine;

use app\AppFactory\Kernel\Model\BaseModel;

class PreReplenishmentDetailModel extends BaseModel
{
    protected $pk = 'id';
    protected $name = 'pre_replenishment_detail';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
}
