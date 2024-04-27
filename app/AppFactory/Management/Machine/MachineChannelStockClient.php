<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/4/9
 * Time: 8:56
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Kernel\Traits\Machine\MachineChannelStockTrait;
use app\AppFactory\Management\ManagementClient;

class MachineChannelStockClient extends ManagementClient
{
    use MachineChannelStockTrait;

    public function getMcsList($where,$pageNum = 0,$field = "*", $order = "", $group = "")
    {
        $data = $this->getMachineChannelStockList($where,$pageNum,$field,$order,'',$group);
        return $this->rQ($data);
    }

}