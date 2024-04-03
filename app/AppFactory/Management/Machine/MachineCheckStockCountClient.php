<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/13
 * Time: 19:53
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Kernel\Traits\Machine\MachineCheckStockCountTrait;
use app\AppFactory\Management\ManagementClient;

class MachineCheckStockCountClient extends ManagementClient
{
    use MachineCheckStockCountTrait;

//    public function export($where)
//    {
//        $list = $this->getMachineCheckStockCountList($where,0,'machine_id,machine_name,g_name,sku,check_stock');
//    }
}