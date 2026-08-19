<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/13
 * Time: 19:23
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Kernel\Support\Excel;
use app\AppFactory\Kernel\Traits\Machine\MachineCheckStockTrait;
use app\AppFactory\Management\ManagementClient;

class MachineCheckStockClient extends ManagementClient
{
    use MachineCheckStockTrait;

    public function exportExcel($where)
    {
        $list = $this->getMachineCheckStockList($where,0,
            'machine_id,machine_name,sku,g_name,gc_name,
            (CASE WHEN channel_code IS NULL OR channel_code = "" THEN "备用库存" ELSE channel_code END) channel_code,
            system_stock,check_stock,(system_stock-check_stock) stock_profit_loss,
            FROM_UNIXTIME(create_time,"%Y-%m-%d %H:%i:%d") create_time,
            create_date,
            creator,
            (CASE status WHEN 1 THEN "正常" WHEN 2 THEN "多货" WHEN 3 THEN "少货" END) status','create_date desc'
        );
        if ($list) {
            $list = $list->toArray();
            if ($list) {
                $checkStockTotal = 0;
                foreach ($list as $item) {
                    $checkStockTotal += intval($item['check_stock']);
                }
                $list[] = ['check_stock' => '累计总和：' . $checkStockTotal];
                $filename = $this->manager['nickname'] . "-库存盘点详情-按商品-" . date("Y-m-d");
                $title = [
                    "machine_id" => "设备ID",
                    "machine_name" => "设备名称",
                    "sku" => "SKU",
                    "g_name" => "商品名称",
                    "channel_code" => "货架编号",
                    "system_stock" => "系统库存",
                    "check_stock" => "实际库存",
                    "stock_profit_loss" => "盘盈/盘亏",
                    "creator_nickname" => "盘点人",
                    "status" => "盘点结果",
                    "create_time" => "盘点时间",
                ];
                return $this->sendToExport("统计报表-盘点报表", $filename, $title, $list);
            }
        }
        return $this->rFail();
    }
}
