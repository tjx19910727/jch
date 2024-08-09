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
        $field = "machine_id,machine_name,check_stock,stock_reserve,creator_nickname,FROM_UNIXTIME(create_time,'%Y-%m-%d %H:%i:%d') create_time";
        $list = $this->getMachineCheckStockCountList($where,0,$field);
        if ($list) {
            $list = $list->toArray();
            if ($list) {
                $title = [
                    "machine_id" => "设备编号",
                    "machine_name" => "设备名称",
                    "check_stock" => "盘点数量",
                    "stock_reserve" => "备用库存盘点",
                    "creator_nickname" => "盘点人员",
                    "create_time" => "盘点时间",
                ];
                $filename = "库存盘点总览-" . date("YmdHis");
                return $this->sendToExport("统计报表-盘点报表", $filename, $title, $list);
            }
        }
        return $this->r(100,'查无导出数据');
    }
}