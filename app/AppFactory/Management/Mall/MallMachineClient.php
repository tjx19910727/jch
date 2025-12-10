<?php
/**
 * Created by VSCode.
 * User: Alex-jixiang
 * Date: 2025/12/08
 * Time: 15:42
 */

namespace app\AppFactory\Management\Mall;


use app\AppFactory\Kernel\Traits\Mall\MallMachineTrait;
use app\AppFactory\Management\ManagementClient;

class MallMachineClient extends ManagementClient
{
    use MallMachineTrait;

    public function getMallMachineList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return $this->rQ($this->getMallMachineList($where, $pageNum, $field, $order, $eachFun, $group));
    }
    
    public function addMallMachine($postData)
    {
        return $this->rA($this->addMallMachine($postData));
    }

    public function updateMallMachine($update, $where = [], $field = [])
    {
        return $this->rU($this->updateMallMachine($update, $where, $field));
    }

    public function delMallMachine($where)
    {
        return $this->rD($this->delMallMachine($where));
    }
}