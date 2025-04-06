<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/1
 * Time: 17:15
 */

namespace app\AppFactory\Kernel\Traits\UpdateLog;


use app\AppFactory\Kernel\Model\UpdateLog\UpdateLogModel;

trait UpdateLogTrait
{
    public function getUpdateLogFind($where,$field = "*",$order = "")
    {
        return UpdateLogModel::getFind($where,$field,$order);
    }

    public function getUpdateLogList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "")
    {
        return UpdateLogModel::getList($where,$pageNum,$field,$order,$eachFun);
    }

    public function addUpdateLog($insert)
    {
        $data = UpdateLogModel::create($insert);
        return $data->id;
    }

    public function updateUpdateLog($update,$where = [],$field = [])
    {
        return UpdateLogModel::update($update,$where,$field);
    }

    public function delUpdateLog($where)
    {
        $result = UpdateLogModel::whereDel($where);
        return $result;
    }
}