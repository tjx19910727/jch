<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/12/9
 * Time: 10:21
 */

namespace app\AppFactory\Kernel\Traits\Wx;


use app\AppFactory\Kernel\Model\Wx\WxBatchesDetailsModel;

trait WxBatchesDetailsTrait
{

    public function getWxBatchesDetailsList($where,$pageNum = 0,$field = "*",$order = "")
    {
        return WxBatchesDetailsModel::getList($where,$pageNum,$field,$order);
    }

    public function addWxBatchesDetails($insert)
    {
        $bd = WxBatchesDetailsModel::create($insert);
        return $bd->bd_id;
    }
}