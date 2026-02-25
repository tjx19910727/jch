<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/4
 * Time: 17:48
 */

namespace app\AppFactory\Management\Config;


use app\AppFactory\AppFactory;
use app\AppFactory\Kernel\Traits\Auth\AuthManagerTrait;
use app\AppFactory\Kernel\Traits\Config\ConfigTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Management\ManagementClient;

class ConfigClient extends ManagementClient
{
    use ConfigTrait,AuthManagerTrait;
    use MachineTrait;

    public function getParentConfigFind($where,$field,$order = "")
    {
        // $ids = $this->getParentIdList($this->manager['pid']);
        // $ids[] = $this->manager['manager_id'];
        // $ids[] = $this->manager['creator'];
        $where['creator'] = 1;
        return $this->rQ($this->getConfigFind($where,$field,$order));
    }

    /**
     * 修改系统配置参数
     * @param $postData
     * @return array|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function updateC($postData)
    {
        $result = $this->updateConfig($postData);
        if ($result) {
            if ($postData['config_name'] == "systemInfo") {
                $machineIds = $this->getMachineList(['online' => 1],0,'machine_id');
                if ($machineIds) {
                    foreach ($machineIds as $v) {
                        $this->sendToMachine($v,'updateSystemInfo');
                    }
                }
            }
        }
        return $this->rU($result);
    }

}