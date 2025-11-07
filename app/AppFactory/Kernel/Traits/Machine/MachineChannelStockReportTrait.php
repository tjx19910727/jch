<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/4/9
 * Time: 14:06
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Machine\MachineChannelStockReportView;

trait MachineChannelStockReportTrait
{
    public function getMachineChannelStockReportList($where,$pageNum = 0,$field = "*", $order = "", $group = "")
    {
        return MachineChannelStockReportView::getList($where,$pageNum,$field,$order,'',$group);
    }

    public function getMachineChannelStockReportJoinMchList($where,$pageNum = 0,$field = "*", $order = "", $group = "")
    {
        return MachineChannelStockReportView::joinMachineList($where,$pageNum,$field,$order,'',$group);
    }
}