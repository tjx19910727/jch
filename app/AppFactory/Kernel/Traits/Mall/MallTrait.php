<?php

/**
 * Created by VSCode.
 * User: Alex-jixiang
 * Date: 2025/12/08
 * Time: 14:00
 */

namespace app\AppFactory\Kernel\Traits\Mall;

use app\AppFactory\Kernel\Model\Mall\MallModel;

trait MallTrait
{
    public function getMallCount($where, $field = '*', $order = '')
    {
        return MallModel::getFind($where, $field, $order);
    }

    public function getMallList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return MallModel::getJoinMallMachineList($where, $pageNum, $field, $order, $eachFun, $group);
    }

    public function getMallFind($where,$field = "*",$order = "")
    {
        return MallModel::getFind($where, $field, $order);
    }

    public function getMallSum($where, $sum)
    {
        return MallModel::getSum($where, $sum);
    }

    public function addMall($insert)
    {
        !isset($this->manager['manager_id']) ? :$insert['creator'] = $this->manager['manager_id'];
        $data = MallModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    public function updateMall($update, $where = [], $field = [])
    {
        !isset($this->manager['manager_id']) ? : $update['updator'] = $this->manager['manager_id'];
        return MallModel::update($update, $where, $field);
    }

    public function delMall($where)
    {
        return MallModel::whereDel($where);
    }

}
