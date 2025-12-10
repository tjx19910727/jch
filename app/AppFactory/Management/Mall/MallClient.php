<?php
/**
 * Created by VSCode.
 * User: Alex-jixiang
 * Date: 2025/12/08
 * Time: 15:42
 */

namespace app\AppFactory\Management\Mall;


use app\AppFactory\Kernel\Traits\Mall\MallTrait;
use app\AppFactory\Management\ManagementClient;

class MallClient extends ManagementClient
{
    use MallTrait;

    public function getMallInfoList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return $this->rQ($this->getMallList($where, $pageNum, $field, $order, $eachFun, $group));
    }

    public function addMallInfo($postData)
    {
        return $this->rA($this->addMall($postData));
    }

    public function updateMallInfo($update, $where = [], $field = [])
    {
        return $this->rU($this->updateMall($update, $where, $field));
    }

    public function delMallInfo($where)
    {
        return $this->rD($this->delMall($where));
    }
}