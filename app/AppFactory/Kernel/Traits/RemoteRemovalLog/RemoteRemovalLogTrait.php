<?php
/**
 * Created by VSCode.
 * User: Copilot
 * Date: 2026/4/16
 */

namespace app\AppFactory\Kernel\Traits\RemoteRemovalLog;

use app\AppFactory\Kernel\Model\RemoteRemovalLog\RemoteRemovalLogModel;

trait RemoteRemovalLogTrait
{
    public function getRemoteRemovalLogFind($where, $field = "*", $order = "")
    {
        return RemoteRemovalLogModel::getFind($where, $field, $order);
    }

    public function getRemoteRemovalLogList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return RemoteRemovalLogModel::getList($where, $pageNum, $field, $order, $eachFun, $group);
    }

    public function addRemoteRemovalLog($insert)
    {
        $data = RemoteRemovalLogModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    public function updateRemoteRemovalLog($update, $where = [], $field = [])
    {
        return RemoteRemovalLogModel::update($update, $where, $field);
    }

    public function delRemoteRemovalLog($where)
    {
        return RemoteRemovalLogModel::whereDel($where);
    }
}
