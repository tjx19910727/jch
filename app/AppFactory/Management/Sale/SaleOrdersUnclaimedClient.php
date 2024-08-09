<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/4/22
 * Time: 15:25
 */

namespace app\AppFactory\Management\Sale;


use app\AppFactory\Kernel\Support\Excel;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersUnclaimedTrait;
use app\AppFactory\Management\ManagementClient;

class SaleOrdersUnclaimedClient extends ManagementClient
{
    use SaleOrdersUnclaimedTrait,MachineTrait;

    public function getSouList($where,$pageNum = 0,$field = "*", $order = "")
    {
        $machineIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], "machine_id");
        if ($machineIds) $where[] = ['machine_id', 'in', $machineIds];
        return $this->r(200,$this->lang("query_success"),$this->getSaleOrdersUnclaimedList($where,$pageNum,$field,$order));
    }


    /**
     * 修改未取事件状态
     * status：2.已清除（人工清除回收箱），3.已取消（人工判定设备为误判）
     * 清除或取消都需要减设备回收箱库存，当在其他位置重置了回收箱或回收箱库存值不足，为避免出现负数，重置回收数量。
     * @param $postData
     * @return bool|string
     */
    public function updateSuStatus($postData)
    {
        $this->startTrans();
        try {
            $su = $this->getSaleOrdersUnclaimedFind(['su_id' => $postData['su_id']], 'm_id,quantity');
            $flag[] = $this->updateSaleOrdersUnclaimed(['su_id' => $postData['su_id'], 'status' => $postData['status'], 'remark' => $postData['remark'] ?? ""]);
            $recycleStock = $this->getMachineValue(['m_id' => $su['m_id']], 'recycle_bin_stock');
            if ($recycleStock > 0) {
                if ($recycleStock < $su['quantity']) $su['quantity'] = $recycleStock;
                $flag[] = $this->setMachineDecField(['m_id' => $su['m_id']], 'recycle_bin_stock', $su['quantity']);
            }
            $result = $this->checkFlag($flag);
            return $this->checkTrans($result);
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e,1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 导出Excel表格
     * @param $where
     * @return array|string
     */
    public function export($where)
    {
        $machineIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], "machine_id");
        if ($machineIds) $where[] = ['machine_id', 'in', $machineIds];
        $field = "machine_id,machine_name,trade_no,
        FROM_UNIXTIME(transfer_time,'%Y-%d-%m %H:%i:%s') transfer_time,
        channel_code,sku,g_name,retail_price,
        (case is_match WHEN 1 THEN '是' ELSE '否' END) is_match,
        (case is_claim WHEN 1 THEN '是' ELSE '否' END) is_claim,
        (case is_out WHEN 1 THEN '是' ELSE '否' END) is_out,
        (case is_close WHEN 1 THEN '是' ELSE '否' END) is_close,
        (CASE status WHEN 1 THEN '未取' WHEN 2 THEN '已清除' WHEN 3 THEN '已取消' END)status,
        duration,
        remark";
        $list = $this->getSaleOrdersUnclaimedList($where,0,$field);
        if (!$list) return $this->r(100,$this->lang("VSaleOrdersUnclaimed.su_no_data"));
        $list = $list->toArray();
        $title = [
            "trade_no" => "订单编号",
            "machine_id" => "设备编号",
            "g_name" => "商品名称",
            "is_match" => "匹配",
            "is_claim" => "取货",
            "is_close" => "关门",
            "status" => "状态",
        ];
        $filename = "未取商品-" . date("YmdHis");
        return $this->sendToExport("事件日志-未取商品", $filename, $title, $list);
    }
}