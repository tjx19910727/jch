<?php

/**
 * Created by VSCode.
 * User: Alex-jixiang
 * Date: 2026/08/25
 * Time: 14:00
 */

namespace app\AppFactory\Kernel\Model\WeiCheng;

use app\AppFactory\Kernel\Model\BaseModel;

class WcGoodsSyncLogModel extends BaseModel
{
    /**
     * Primary key for the table
     *
     * @var string
     */
    protected $pk = "id";

    /**
     * Table name
     *
     * @var string
     */
    protected $name = "wc_goods_sync_logs";

}
