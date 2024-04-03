<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/13
 * Time: 17:18
 */

namespace app\mobile\controller\machine;


use app\mobile\controller\Common;
use think\App;

class Info extends Common
{

    public function getMachine()
    {
        return returnData($this->app->machineInfo->getMachineFind(['machine_id' => $this->tokenArr['machine_id']],'m_id,machine_id,machine_name'));
    }

    public function getChannel()
    {
        return returnData($this->app->machineInfo->getMachineChannelList(['machine_id' => $this->tokenArr['machine_id']],0,'sku,channel_code,g_name,stock,mc_id'));
    }
}