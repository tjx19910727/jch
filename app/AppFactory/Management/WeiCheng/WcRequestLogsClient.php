<?php
/**
 * Created by VSCode.
 * User: Alex-jixiang
 * Date: 2026/1/5
 * Time: 15:42
 */

namespace app\AppFactory\Management\WeiCheng;


use app\AppFactory\Kernel\Traits\WeiCheng\WcRequestLogsTrait;
use app\AppFactory\Management\ManagementClient;

class WcRequestLogsClient extends ManagementClient
{
    use WcRequestLogsTrait;

    public function getWcRequestLogsList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return $this->rQ($this->getWcRequestLogsList($where, $pageNum, $field, $order, $eachFun, $group));
    }
    
    public function addWcRequestLogs($postData)
    {
        return $this->rA($this->addWcRequestLogs($postData));
    }

    public function updateWcRequestLogs($update, $where = [], $field = [])
    {
        return $this->rU($this->updateWcRequestLogs($update, $where, $field));
    }

    public function delWcRequestLogs($where)
    {
        return $this->rD($this->delWcRequestLogs($where));
    }

    
}                                                                                                                                                                                                                                                                                                             