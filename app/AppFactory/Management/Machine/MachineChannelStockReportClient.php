<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/4/9
 * Time: 14:13
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Kernel\Support\Excel;
use app\AppFactory\Kernel\Traits\Machine\MachineChannelStockReportTrait;
use app\AppFactory\Management\ManagementClient;

class MachineChannelStockReportClient extends ManagementClient
{
    use MachineChannelStockReportTrait;

    /**
     * 查询库存报表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @param string $group
     * @return array|string
     */
    public function getMcsList($where,$pageNum = 0,$field = "*",$order = "",$group = "")
    {
        $data = $this->getMachineChannelStockReportList($where,$pageNum,$field,$order,$group);
        return $this->rQ($data);
    }

    /**
     * 导出库存报表
     * @param $where
     * @param int $eType
     * @return array|string
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public function export($where,$eType = 1)
    {
        $group = "";
        $field = "*";
        if ($eType == 1) {
            $field = "sku,g_name,bar_code,model,gc_name,
        retail_price,
        sum(mc_stock) mc_stock,
        sum(pre_stock) pre_stock,
        sum(standby_stock) standby_stock,
        sum(bad_stock) bad_stock,
        sum(total_stock) total_stock";
            $group = "g_id";
        }
        if ($eType == 2) {
            $field = "machine_id,machine_name,sku,g_name,model,gc_name,retail_price,mc_stock,pre_stock,standby_stock,bad_stock,total_stock";
        }
        $list = $this->getMachineChannelStockReportList($where,0,$field,"total_stock desc",$group);
        if ($list) {
            $list = $list->toArray();
            if ($eType == 1) {
                $title = [
                    "sku" => "SKU",
                    "g_name" => "商品名称",
                    "model" => "型号",
                    "gc_name" => "品类",
                    "retail_price" => "默认售价",
                    "mc_stock" => "库存",
                    "pre_stock" => "预定数量",
                    "standby_stock" => "备用库存",
                    "bad_stock" => "Bad库存",
                    "total_stock" => "总库存",
                ];
                $filename = "库存报表(按商品)-" . date("Ymd");
            }
            if ($eType == 2) {
                $title = [
                    "machine_id" => "设备编号",
                    "machine_name" => "设备名称",
                    "sku" => "SKU",
                    "g_name" => "商品名称",
                    "model" => "型号",
                    "gc_name" => "品类",
                    "retail_price" => "默认售价",
                    "mc_stock" => "库存",
                    "pre_stock" => "预定数量",
                    "standby_stock" => "备用库存",
                    "bad_stock" => "Bad库存",
                    "total_stock" => "总库存",
                ];
                $filename = "库存报表(按设备)-" . date("Ymd");
            }
            return $this->sendToExport("统计报表-库存报表", $filename, $title, $list);
        }
        return $this->rFail();
    }
}