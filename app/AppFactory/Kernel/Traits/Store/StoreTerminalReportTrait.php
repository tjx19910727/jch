<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/9/26
 * Time: 11:24
 */

namespace app\AppFactory\Kernel\Traits\Store;


use app\AppFactory\Kernel\Model\Store\StoreTerminalReportModel;

trait StoreTerminalReportTrait
{
    public function getStoreTerminalReportList($where,$pageNum = 0,$field = "*",$order = "create_time desc")
    {
        return StoreTerminalReportModel::getList($where,$pageNum,$field,$order);
    }

    public function addStoreTerminalReport($insert)
    {
        StoreTerminalReportModel::create($insert);
    }

}