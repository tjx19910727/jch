<?php

namespace app\AppFactory\Kernel\Model\Api;

use app\AppFactory\Kernel\Model\BaseModel;

/**
 * 第三方商品同步聚合记录。
 *
 * 记录长期保留 version/dispatched_version 水位，派发成功后不能直接删除，
 * 否则版本会从 1 重新开始并可能被接收方按旧版本丢弃。
 */
class ThirdPartySyncDirtyModel extends BaseModel
{
    protected $pk = 'id';
    protected $name = 'third_party_sync_dirty';
}
