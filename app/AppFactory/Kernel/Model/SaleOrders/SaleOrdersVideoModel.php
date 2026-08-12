<?php

namespace app\AppFactory\Kernel\Model\SaleOrders;

use app\AppFactory\Kernel\Model\BaseModel;

/**
 * 交易视频分段记录。
 *
 * sale_orders 等原表上的 transaction_video 字段继续保留，作为旧接口兼容值；
 * 一笔业务实际上传的所有视频以本表为准。
 */
class SaleOrdersVideoModel extends BaseModel
{
    const TYPE_SALE_ORDER = 1;
    const TYPE_DOOR_OPEN = 2;
    const TYPE_REMOTE_OUT_GOODS = 3;

    protected $pk = 'sov_id';
    protected $name = 'sale_orders_video';
}
