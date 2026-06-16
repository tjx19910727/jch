<?php

namespace app\AppFactory\Kernel\Model\Machine;

use app\AppFactory\Kernel\Model\BaseModel;

class PreReplenishmentLogModel extends BaseModel
{
    protected $pk = 'id';
    protected $name = 'pre_replenishment_log';
    protected $createTime = 'created_at';
    protected $updateTime = false;
}
