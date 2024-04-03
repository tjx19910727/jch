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

    public function exportSku($where)
    {
        $list = $this->getMachineCheckStockList($where,0,
            'sku,g_name,gc_name,check_stock,stock_reserve,date_format(create_time,"%Y-%m-%d %H:%i:%d") create_time'
        );
        $filename = $this->manager['nickname'] . "-库存盘点详情-按商品-" . date("Y-m-d");
        $title = [
            "sku" => "SKU",
            "g_name" => "商品名称",
            "check_stock" => "盘点库存",
            "stock_reserve" => "备用库存",
            "create_time" => "盘点时间",
        ];
        Excel::exportExcel($list,$title,$filename);
    }

    public function exportMachine($where)
    {

    }
}