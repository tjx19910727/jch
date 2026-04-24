<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/4/21
 * Time: 11:30
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Kernel\Traits\Machine\SimCardInfoTrait;
use app\AppFactory\Management\ManagementClient;

class SimCardInfoClient extends ManagementClient
{
    use SimCardInfoTrait;

    public function getListData($where, $pageNum = 0, $field = "*", $order = "id desc")
    {
        $list = $this->getSimCardInfoList($where, $pageNum, $field, $order);
        return $this->rQ($list);
    }

    public function getFindData($where, $field = "*")
    {
        $data = $this->getSimCardInfoFind($where, $field);
        return $this->rQ($data);
    }
}
