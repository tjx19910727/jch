<?php
/**
 * Created by VSCode.
 * User: Alex-jixiang
 * Date: 2025/12/08
 * Time: 14:00
 */

namespace app\AppFactory\Kernel\Traits\RemoteActionLog;

use app\AppFactory\Kernel\Model\RemoteActionLog\RemoteActionLogModel;

trait RemoteActionLogTrait
{
    public function getRALogsCount($where, $field = '*', $order = '')
    {
        return RemoteActionLogModel::getFind($where, $field, $order);
    }

    public function getRALogsList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return RemoteActionLogModel::getList($where, $pageNum, $field, $order, $eachFun, $group);
    }

    public function getRALogsSum($where, $sum)
    {
        return RemoteActionLogModel::getSum($where, $sum);
    }

    public function addRALog($insert)
    {
        $data = RemoteActionLogModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    public function updateRALog($update, $where = [], $field = [])
    {
        return RemoteActionLogModel::update($update, $where, $field);
    }

    public function delRALog($where)
    {
        return RemoteActionLogModel::whereDel($where);
    }

}