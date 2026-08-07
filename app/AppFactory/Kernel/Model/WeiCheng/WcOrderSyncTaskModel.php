<?php

namespace app\AppFactory\Kernel\Model\WeiCheng;

use app\AppFactory\Kernel\Model\BaseModel;

class WcOrderSyncTaskModel extends BaseModel
{
    protected $pk = 'wcst_id';
    protected $name = 'wc_order_sync_task';
}
