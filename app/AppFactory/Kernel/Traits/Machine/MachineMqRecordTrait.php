<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/31
 * Time: 11:13
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Machine\MachineMqRecordModel;

trait MachineMqRecordTrait
{
    public function getMachineMqRecordFind($where,$field = "*",$order = "")
    {
        return MachineMqRecordModel::getFind($where,$field,$order);
    }

    public function getMachineMqRecordList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "")
    {
        return MachineMqRecordModel::getList($where,$pageNum,$field,$order,$eachFun);
    }

    public function addMachineMqRecord($insert)
    {
        $data = MachineMqRecordModel::create($insert);
        return $data->mr_id;
    }

    public function updateMachineMqRecord($update,$where = [],$field = [])
    {
        return MachineMqRecordModel::update($update,$where,$field);
    }

    public function delMachineMqRecord($where)
    {
        $result = MachineMqRecordModel::whereDel($where);
        return $result;
    }
}