<?php
/**
 * Created by VSCode.
 * User: Alex-jixiang
 * Date: 2025/12/08
 * Time: 14:00
 */

namespace app\AppFactory\Kernel\Traits\Mall;

use app\AppFactory\Kernel\Model\Mall\MallRequestLogsModel;

trait MallRequestLogsTrait
{
    public function getMallRequestLogsCount($where, $field = '*', $order = '')
    {
        return MallRequestLogsModel::getFind($where, $field, $order);
    }

    public function getMallRequestLogsList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return MallRequestLogsModel::getList($where, $pageNum, $field, $order, $eachFun, $group);
    }

    public function getMallRequestLogsSum($where, $sum)
    {
        return MallRequestLogsModel::getSum($where, $sum);
    }

    public function addMallRequestLogs($insert)
    {
        $data = MallRequestLogsModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    public function updateMallRequestLogs($update, $where = [], $field = [])
    {
        return MallRequestLogsModel::update($update, $where, $field);
    }

    public function delMallRequestLogs($where)
    {
        return MallRequestLogsModel::whereDel($where);
    }

}