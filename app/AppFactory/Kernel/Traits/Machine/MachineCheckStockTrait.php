<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/13
 * Time: 14:45
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Machine\MachineCheckStockModel;

trait MachineCheckStockTrait
{
    public function getMachineCheckStockFind($where,$field = "*")
    {
        return MachineCheckStockModel::getFind($where,$field);
    }

    public function getMachineCheckStockList($where,$pageNum = 0,$field = "*", $order = "",$group = "")
    {
        return MachineCheckStockModel::getList($where,$pageNum,$field,$order,'',$group);
    }

    public function addMachineCheckStock($insert)
    {
        $mcs = MachineCheckStockModel::create($insert);
        return $mcs->id;
    }

    /**
     * 批量添加
     * @param $insertAll
     * @return \think\Collection
     * @throws \Exception
     */
    public function addMachineCheckStockMore($insertAll)
    {
        $mcs = new MachineCheckStockModel();
        return $mcs->saveAll($insertAll);
    }

    public function delMachineCheckStock($where)
    {
        return MachineCheckStockModel::whereDel($where);
    }
}