<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/13
 * Time: 19:53
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Kernel\Support\Excel;
use app\AppFactory\Kernel\Traits\Machine\MachineCheckStockCountTrait;
use app\AppFactory\Management\ManagementClient;

class MachineCheckStockCountClient extends ManagementClient
{
    use MachineCheckStockCountTrait;

    public function export($where)
    {
        $list = $this->getMachineCheckStockCountList($where,0,'*');
        if ($list) {
            $list = $list->toArray();
            $title = [
                "machine_id" => "设备编号",
                "machine_name" => "设备名称",
                "check_stock" => "盘点数量",
                "stock_reserve" => "备用库存盘点",
                "creator_nickname" => "盘点人员",
                "create_time" => "盘点时间",
            ];
            $filename = "导出库存盘点号总览-"  . date("YmdHis");
            return $this->r(200,'导出成功',Excel::exportExcel($list,$title,$filename));
        }
        return $this->r(100,'查无导出数据');
    }
}