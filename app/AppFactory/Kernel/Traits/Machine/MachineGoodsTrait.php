<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:38
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Machine\MachineGoodsModel;

trait MachineGoodsTrait
{
    public function getMachineGoodsFind($where,$field = "*",$order = "")
    {
        return MachineGoodsModel::getFind($where,$field,$order);
    }

    public function getMachineGoodsList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "")
    {
        return MachineGoodsModel::getList($where,$pageNum,$field,$order,$eachFun);
    }

    public function addMachineGoods($insert)
    {
        $data = MachineGoodsModel::create($insert);
        return $data->mg_id;
    }

    public function updateMachineGoods($update,$where = [],$field = [])
    {
        return MachineGoodsModel::update($update,$where,$field);
    }

    public function delMachineGoods($where)
    {
        $result = MachineGoodsModel::whereDel($where);
        return $result;
    }
}