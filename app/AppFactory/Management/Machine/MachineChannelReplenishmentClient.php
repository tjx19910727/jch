<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/12
 * Time: 20:03
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Kernel\Support\Excel;
use app\AppFactory\Kernel\Traits\Machine\MachineChannelReplenishmentTrait;
use app\AppFactory\Management\ManagementClient;

class MachineChannelReplenishmentClient extends ManagementClient
{
    use MachineChannelReplenishmentTrait;

    public function export($where)
    {
        try {
            $list = $this->getMachineChannelReplenishmentList($where, 0, '*', 'id desc');
            if ($list) {
                $list = $list->toArray();
                $title = [
                    "machine_id" => "设备编号",
                    "machine_name" => "设备名称",
                    "channel_code" => "货架编号",
                    "g_name" => "商品名称",
                    "gc_name" => "商品品类",
                    "sku" => "SKU",
                    "before" => "补货前库存",
                    "quantity" => "补货数量",
                    "after" => "补货后库存",
                    "creator_nickname" => "补货员",
                    "create_time" => "补货时间",
                ];
                $filename = "导出补货记录-" . date("YmdHis");
                return $this->r(200, '导出成功', Excel::exportExcel($list, $title, $filename));
            }
            return $this->r(100, '查无补货数据');
        } catch (\PHPExcel_Writer_Exception $e) {
            actionException($e,1);
            return $this->rTryCatch($e->getMessage());
        } catch (\PHPExcel_Exception $e) {
            actionException($e,1);
            return $this->rTryCatch($e->getMessage());
        }
    }
}