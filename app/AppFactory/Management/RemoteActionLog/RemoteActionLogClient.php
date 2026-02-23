<?php
/**
 * Created by VSCode.
 * User: Alex-jixiang
 * Date: 2025/12/08
 * Time: 15:42
 */

namespace app\AppFactory\Management\RemoteActionLog;


use app\AppFactory\Kernel\Traits\RemoteActionLog\RemoteActionLogTrait;
use app\AppFactory\Management\ManagementClient;

class RemoteActionLogClient extends ManagementClient
{
    use RemoteActionLogTrait;

    public function getRemoteActionLogsList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return $this->rQ($this->getRALogsCount($where, $pageNum, $field, $order, $eachFun, $group));
    }
    
    public function addRemoteActionLog($postData)
    {
        return $this->rA($this->addRALog($postData));
    }

    public function updateRemoteActionLog($update, $where = [], $field = [])
    {
        return $this->rU($this->updateRALog($update, $where, $field));
    }

    public function delRemoteActionLog($where)
    {
        return $this->rD($this->delRALog($where));
    }
}