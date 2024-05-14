<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:42
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\AppFactory;
use app\AppFactory\Kernel\Traits\Machine\MachineGoodsTrait;
use app\AppFactory\Management\ManagementClient;

class MachineGoodsClient extends ManagementClient
{
    use MachineGoodsTrait;

    public function addMg($postData)
    {
        $mg_id = $this->addMachineGoods($postData);
        if ($mg_id) {
            return $this->r(200,$this->lang("add_success"));
        }
        return $this->r(100,$this->lang("add_fail"));
    }

    public function updateMg($postData)
    {
        $result = $this->updateMachineGoods($postData);
        return $this->rU($result);
    }

    /**
     * 根据条件修改设备商品信息
     * @param $postData
     * @return array|string
     */
    public function updateByWhere($postData)
    {
        $result = $this->updateMachineGoods($postData['update'],$postData['where']);
        if ($result) {
            $mg = $this->getMachineGoodsList($postData['where'],0);
            $this->afterMgUpdate($mg);
            return $this->r(200, $this->lang("action_success"));
        }
        return $this->r(100,$this->lang("action_fail"));
    }

    public function delMg($postData)
    {
        $result = $this->delMachineGoods($postData);
        if ($result) {
            return $this->r(200,$this->lang("del_success"));
        }
        return $this->r(100,$this->lang("del_fail"));
    }
}