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
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Management\ManagementClient;

class MachineChannelStockReportClient extends ManagementClient
{
    use MachineChannelStockReportTrait, MachineTrait;

    /**
     * 查询库存报表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @param string $group
     * @return array|string
     */
    public function getMcsList($where,$pageNum = 0,$field = "*",$order = "",$group = "",$isOperating = null)
    {
        if (!$this->validateStockReportOperatingStatus($isOperating)) {
            return $this->rValidate('设备在营状态参数错误');
        }
        $where = $this->applyStockReportOperatingWhere($where, $isOperating);
        $where = $this->applyStockReportDataScope($where);
        $data = $this->getMachineChannelStockReportList($where,$pageNum,$field,$order,$group);
        return $this->rQ($data);
    }

    /**
     * 在营状态属于 machine 表，先换算成设备 ID 再筛选库存报表视图。
     *
     * @param array $where
     * @param mixed $isOperating
     * @param string $machineIdField
     * @return array
     */
    protected function applyStockReportOperatingWhere($where, $isOperating, $machineIdField = 'm_id')
    {
        if (!in_array(intval($isOperating), [1, 2], true)) {
            return $where;
        }
        $mIds = $this->getMachineColumn(['is_operating' => intval($isOperating)], 'm_id');
        $where[] = [$machineIdField, 'in', $mIds ?: [0]];
        return $where;
    }

    /**
     * 主账号按组织查询，子账号按明确授权的设备查询。
     * 子账号无设备授权时使用不存在的设备 ID，防止权限条件退化为全量查询。
     *
     * @param array $where
     * @param string $machineIdField
     * @param string $aoIdField
     * @return array
     */
    protected function applyStockReportDataScope($where, $machineIdField = 'm_id', $aoIdField = 'ao_id')
    {
        if (intval($this->manager['pid'] ?? 0) > 0) {
            $mIds = $this->getAuthManagerMachineColumn(
                ['manager_id' => $this->manager['manager_id']],
                'm_id'
            );
            $where[] = [$machineIdField, 'in', $mIds ?: [0]];
            return $where;
        }

        $where[$aoIdField] = intval($this->manager['ao_id'] ?? 0);
        return $where;
    }

    /**
     * 导出库存报表
     * @param $where
     * @param int $eType
     * @return array|string
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public function export($where,$eType = 1,$isOperating = null)
    {
        if (!$this->validateStockReportOperatingStatus($isOperating)) {
            return $this->rValidate('设备在营状态参数错误');
        }
        $group = "";
        $field = "*";
        if ($eType == 1) {
            $where = $this->applyStockReportOperatingWhere($where, $isOperating);
            $where = $this->applyStockReportDataScope($where);
            $field = "sku,g_name,bar_code,model,gc_name,
        retail_price,
        sum(mc_stock) mc_stock,
        sum(pre_stock) pre_stock,
        sum(standby_stock) standby_stock,
        sum(bad_stock) bad_stock,
        sum(total_stock) total_stock";
            $group = "g_id";
            $list = $this->getMachineChannelStockReportList($where,0,$field,"total_stock desc",$group);
        }
        if ($eType == 2) {
            $where = $this->applyStockReportOperatingWhere($where, $isOperating, 'mcs.m_id');
            $where = $this->applyStockReportDataScope($where, 'mcs.m_id', 'mcs.ao_id');
            $field = "mcs.machine_id,mcs.machine_name,mcs.sku,mcs.g_name,mcs.model,mcs.gc_name,mcs.retail_price,mcs.mc_stock,mcs.pre_stock,mcs.standby_stock,mcs.bad_stock,mcs.total_stock,m.factory,m.inventory_location";
            $list = $this->getMachineChannelStockReportJoinMchList($where,0,$field,"total_stock desc",$group);
        }
        $list = isset($list) && $list ? $list->toArray() : [];
        if ($list) {
            if ($eType == 1) {
                $title = [
                    "sku" => "SKU",
                    "g_name" => "商品名称",
                    "bar_code" => "商品条码",
                    "model" => "商品型号",
                    "mc_stock" => "库存",
                    "pre_stock" => "预定数量",
                    "standby_stock" => "备用库存",
                    "bad_stock" => "Bad库存",
                    "total_stock" => "总数",
                    "retail_price" => "默认售价",
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
                    "factory" => '所属工厂',
                    "inventory_location" => '库存地点'
                ];
                $filename = "库存报表(按设备)-" . date("Ymd");
            }
            return $this->sendToExport("统计报表-库存报表", $filename, $title, $list);
        }
        return $this->rFail('暂无可导出的库存报表数据');
    }
    /**
     * 库存报表只支持在营、在库两种设备状态。
     * @param mixed $isOperating
     * @return bool
     */
    private function validateStockReportOperatingStatus($isOperating)
    {
        if ($isOperating === '' || $isOperating === null) {
            return true;
        }
        return in_array(intval($isOperating), [1, 2], true);
    }
}