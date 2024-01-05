<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/12/9
 * Time: 10:22
 */

namespace app\AppFactory\Kernel\Traits\Wx;


use app\AppFactory\Kernel\Model\Wx\WxBatchesModel;

trait WxBatchesTrait
{
    public function getWxBatchesList($where,$pageNum = 0,$field = "*", $order = "")
    {
        return WxBatchesModel::getList($where,$pageNum,$field,$order);
    }

    public function addWxBatches($insert)
    {
        $wb = WxBatchesModel::create($insert);
        return $wb->id;
    }
}