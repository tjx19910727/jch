<?php
/**
 * Created by VSCode.
 * User: Alex-jixiang
 * Date: 2025/12/08
 * Time: 15:42
 */

namespace app\AppFactory\Management\Mall;


use app\AppFactory\Kernel\Traits\Mall\MallRequestLogsTrait;
use app\AppFactory\Management\ManagementClient;

class MallRequestLogsClient extends ManagementClient
{
    use MallRequestLogsTrait;

    public function getMallRequestLogsList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return $this->rQ($this->getMallRequestLogsList($where, $pageNum, $field, $order, $eachFun, $group));
    }
    
    public function addMallRequestLogs($postData)
    {
        return $this->rA($this->addMallRequestLogs($postData));
    }

    public function updateMallRequestLogs($update, $where = [], $field = [])
    {
        return $this->rU($this->updateMallRequestLogs($update, $where, $field));
    }

    public function delMallRequestLogs($where)
    {
        return $this->rD($this->delMallRequestLogs($where));
    }
}