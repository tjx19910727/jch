<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/4/21
 * Time: 11:30
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Kernel\Traits\Machine\SimCardInfoTrait;
use app\AppFactory\Kernel\Traits\Machine\SimSignalLogTrait;
use app\AppFactory\Management\ManagementClient;

class SimCardInfoClient extends ManagementClient
{
    use SimCardInfoTrait;
    use SimSignalLogTrait;

    public function getListData($where, $pageNum = 0, $field = "a.*", $order = "a.id desc")
    {
        $list = $this->getSimCardInfoListJoinMachine($where, $pageNum, $field, $order);
        return $this->rQ($list);
    }

    public function getFindData($where, $field = "*")
    {
        $data = $this->getSimCardInfoFind($where, $field);
        return $this->rQ($data);
    }

    public function getMachineListData($where, $pageNum = 0, $field = "a.*", $order = "a.id desc")
    {
        $list = $this->getSimCardMachineListJoinMachine($where, $pageNum, $field, $order);
        return $this->rQ($list);
    }

    public function getMachineFindData($where, $field = "*")
    {
        $data = $this->getSimCardMachineFind($where, $field);
        return $this->rQ($data);
    }

    public function getSignalListData($where, $pageNum = 0, $field = "a.*", $order = "a.id desc")
    {
        $list = $this->getSimSignalLogListJoinMachine($where, $pageNum, $field, $order);
        return $this->rQ($list);
    }

    public function getSignalFindData($where, $field = "*")
    {
        $data = $this->getSimSignalLogFind($where, $field);
        return $this->rQ($data);
    }
}
