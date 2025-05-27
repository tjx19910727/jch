<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/13
 * Time: 14:45
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Machine\MachineCheckStockCountView;

trait MachineCheckStockCountTrait
{
    public function getMachineCheckStockCountFind($where,$field = "*")
    {
        return MachineCheckStockCountView::getFind($where,$field);
    }

    public function getMachineCheckStockCountList($where,$pageNum = 0,$field = "*", $order = "",$eachFunc = '',$group = "")
    {
        return MachineCheckStockCountView::getList($where,$pageNum,$field,$order,$eachFunc,$group);
    }

}