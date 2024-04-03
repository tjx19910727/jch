<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/14
 * Time: 17:18
 */

namespace app\AppFactory\Management\Auth;


use app\AppFactory\Kernel\Traits\Auth\AuthManagerMachineTrait;
use app\AppFactory\Kernel\Traits\Auth\AuthManagerTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Management\ManagementClient;

class AuthManagerMachineClient extends ManagementClient
{
    use AuthManagerMachineTrait,AuthManagerTrait;
    use MachineTrait;

    public function bind($manager_id,$m_ids)
    {
        $flag = [];
        $this->startTrans();
        $m_ids = explode(",",$m_ids);
        $old_M_ids = $this->getAuthManagerMachineColumn(['manager_id' => $manager_id],'m_id');
        $delList = array_diff($old_M_ids,$m_ids);
        $addList = array_diff($m_ids,$old_M_ids);
        if ($delList) $flag[] = $this->delAuthManagerMachine(['manager_id' => $manager_id,['m_id' ,'in',$delList]]);
        if ($addList) {
            $manager = $this->getAuthManagerFind(['manager_id' => $manager_id],'manager_id,nickname,account')->toArray();
            foreach ($addList as $key => $value) {
                $m = $this->getMachineFind(['m_id' => $value],'m_id,machine_id,machine_name')->toArray();
                $insert = array_merge($manager,$m);
                $insertAll[] = $insert;
            }
            $flag[] = $this->addAuthManagerMachineMore($insertAll);
        }
        $result = $this->checkFlag($flag);
        return $this->checkTrans($result);
    }
}