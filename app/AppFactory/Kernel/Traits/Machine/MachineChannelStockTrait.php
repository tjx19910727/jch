<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:35
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Machine\MachineChannelStockModel;

trait MachineChannelStockTrait
{
    public function getMachineChannelStockCount($where)
    {
        return MachineChannelStockModel::getCount($where);
    }

    public function getMachineChannelStockValue($where,$value)
    {
        return MachineChannelStockModel::getFieldValue($where,$value);
    }

    public function getMachineChannelStockColumn($where,$column)
    {
        return MachineChannelStockModel::getColumn($where,$column);
    }

    public function getMachineChannelStockFind($where,$field = "*",$order = "")
    {
        return MachineChannelStockModel::getFind($where,$field,$order);
    }

    public function getMachineChannelStockList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "",$group = '')
    {
        return MachineChannelStockModel::getList($where,$pageNum,$field,$order,$eachFun,$group);
    }

    public function addMachineChannelStockMore($insertAll)
    {
        $mcs = new MachineChannelStockModel();
        return $mcs->saveAll($insertAll);
    }

    public function delMachineChannelStock($where)
    {
        return MachineChannelStockModel::whereDel($where);
    }

}