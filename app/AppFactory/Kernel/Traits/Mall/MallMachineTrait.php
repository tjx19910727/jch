<?php
/**
 * Created by VSCode.
 * User: Alex-jixiang
 * Date: 2025/12/08
 * Time: 14:00
 */

namespace app\AppFactory\Kernel\Traits\Mall;

use app\AppFactory\Kernel\Model\Mall\MallMachineModel;

trait MallMachineTrait
{
    public function getMallMachineCount($where,$field = '*',$order = '')
    {
        return MallMachineModel::getFind($where,$field,$order);
    }

    public function getMallMachineList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "",$group = "")
    {
        return MallMachineModel::getList($where,$pageNum,$field,$order,$eachFun,$group);
    }

    public function getMallMachineSum($where,$sum)
    {
        return MallMachineModel::getSum($where,$sum);
    }

    public function addMallMachine($insert)
    {
        !isset($this->manager['manager_id']) ? :$insert['creator'] = $this->manager['manager_id'];
        $data = MallMachineModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;

    }

    public function updateMallMachine($update,$where = [],$field = [])
    {
        return MallMachineModel::update($update,$where,$field);
    }

    public function delMallMachine($where)
    {
        return MallMachineModel::whereDel($where);
    }

}