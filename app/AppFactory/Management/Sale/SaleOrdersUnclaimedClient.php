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
        $su = $this->getSaleOrdersUnclaimedFind(['su_id' => $postData['su_id']],'m_id,quantity');
        $flag[] = $this->updateSaleOrdersUnclaimed(['su_id' => $postData['su_id'],'status' => $postData['status'],'remark' => $postData['remark'] ?? ""]);
        $recycleStock = $this->getMachineValue(['m_id' => $su['m_id']],'recycle_bin_stock');
        if ($recycleStock > 0) {
            if ($recycleStock < $su['quantity']) $su['quantity'] = $recycleStock;
            $flag[] = $this->setMachineDecField(['m_id' => $su['m_id']], 'recycle_bin_stock', $su['quantity']);
        }
        $result = $this->checkFlag($flag);
        return $this->checkTrans($result);
    }

    /**
     * 导出Excel表格
     * @param $where
     * @return array|string
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public function export($where)
    {
        $field = "machine_id,machine_name,trade_no,
        FROM_UNIXTIME(transfer_time,'%Y-%d-%m %H:%i:%s') transfer_time,
        channel_code,sku,g_name,retail_price,
        (case is_out WHEN 1 THEN 'done' ELSE 'none' END) is_out,
        (CASE status WHEN 1 THEN '未取' WHEN 2 THEN '已清除' WHEN 3 THEN '已取消' END)status,
        duration,
        remark";
        $list = $this->getSaleOrdersUnclaimedList($where,0,$field);
        if (!$list) return $this->r(100,$this->lang("VSaleOrdersUnclaimed.su_no_data"));
        $list = $list->toArray();
        $title = [
            "machine_id" => "设备编号",
            "machine_name" => "设备名称",
            "trade_no" => "交易号",
            "transfer_time" => "交易时间",
            "channel_code" => "槽位",
            "sku" => "SKU",
            "g_name" => "商品名称",
            "retail_price" => "单价",
            "is_out" => "开门",
            "status" => "状态",
            "duration" => "用时（支付至关门）秒",
            "remark" => "备注",
        ];
        $filename = "未取商品-" . date("YmdHis");
        $result = Excel::exportExcel($list,$title,$filename);
        return $this->rAction($result);
    }
}