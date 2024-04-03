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
            $config = [
                "machine_id" => $postData['machine_id'],
                "key" => env("api.md5Key"),
            ];
            $app = AppFactory::machine($config);
            $app->sendMq->triggerUpdateMg($mg_id);
            return $this->r(200,$this->lang("add_success"));
        }
        return $this->r(100,$this->lang("add_fail"));
    }

    /**
     * 根据条件修改设备商品信息
     * @param $postData
     * @return array|string
     */
    public function updateByWhere($postData)
    {
        if (isset($postData['where']['g_id'])) $where["g_id"] = $postData['where']['g_id'];
        if (isset($postData['where']['m_id'])) $where[] = ['m_id',"in",$postData['where']['m_id']];
        $result = $this->updateMachineGoods($postData['update'],$postData['where']);
        if ($result) {
            $mgList = $this->getMachineGoodsList($postData['where'],0,'mg_id,machine_id');
            foreach ($mgList as $k => $v) {
                $config = [
                    "machine_id" => $v['machine_id'],
                    "key" => env("api.md5Key"),
                ];
                $app = AppFactory::machine($config);
                $flag[] = $app->sendMq->triggerUpdateMg($v['mg_id']);
            }
            actionLog($flag,'sendFlag');
            return $this->r(200, $this->lang("action_success"));
        }
        return $this->r(100,$this->lang("action_fail"));
    }

    public function delMg($postData)
    {
        $mg = $this->getMachineGoodsFind($postData);
        if ($mg) {
            $result = $this->delMachineGoods($postData);
            if ($result) {
                $config = [
                    "machine_id" => $mg['machine_id'],
                    "key" => env("api.md5Key"),
                ];
                $app = AppFactory::machine($config);
                $app->sendMq->triggerUpdateMg($mg['mg_id']);
                return $this->r(200,$this->lang("del_success"));
            }
        }
        return $this->r(100,$this->lang("del_fail"));
    }
}