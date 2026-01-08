<?php
/**
 * Created by VSCode.
 * User: Alex-jixiang
 * Date: 2026/1/05
 * Time: 14:00
 */

namespace app\AppFactory\Kernel\Traits\WeiCheng;

use app\AppFactory\Kernel\Model\WeiCheng\WcRequestLogsModel;

trait WcRequestLogsTrait
{
    public function getWcRequestLogsCount($where, $field = '*', $order = '')
    {
        return WcRequestLogsModel::getFind($where, $field, $order);
    }

    public function getWcRequestLogsList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return WcRequestLogsModel::getList($where, $pageNum, $field, $order, $eachFun, $group);
    }

    public function getWcRequestLogsSum($where, $sum)
    {
        return WcRequestLogsModel::getSum($where, $sum);
    }

    public function addWcRequestLogs($insert)
    {
        $data = WcRequestLogsModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    public function updateWcRequestLogs($update, $where = [], $field = [])
    {
        return WcRequestLogsModel::update($update, $where, $field);
    }

    public function delWcRequestLogs($where)
    {
        return WcRequestLogsModel::whereDel($where);
    }

}