<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/26
 * Time: 10:12
 */

namespace app\AppFactory\Management\Activity;


use app\AppFactory\Kernel\Support\Excel;
use app\AppFactory\Kernel\Traits\Activity\ActivityFdTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityFdUsedTrait;
use app\AppFactory\Management\ManagementClient;

class ActivityFdUsedClient extends ManagementClient
{
    use ActivityFdTrait,ActivityFdUsedTrait;

    /**
     * @param $fd_id
     * @return array|string
     */
    public function exportList($fd_id)
    {
        try {
            $whereFd['fd_id'] = $fd_id;
            $fd = $this->getActivityFdFind($whereFd,'fd_name');
            $whereUsed['fd_id'] = $fd_id;
            $usedList = $this->getActivityFdUsedList($whereUsed,0,
                'machine_id,machine_name,trade_no,
                (CASE fd_type WHEN 1 THEN "赠品" WHEN 2 THEN "立减金额" WHEN 3 THEN "惊喜礼品" WHEN 4 THEN "折扣" END ) fd_type,
                (CASE condition_type WHEN 0 THEN "不限额" WHEN 1 THEN "最低消费金额" WHEN 2 THEN "最少消费件数" WHEN 3 THEN "指定SKU" END) condition_type,
                condition_value,active_value,g_name,sku
                ');
            if ($usedList) {
                $usedList = $usedList->toArray();
                $title = [
                    "machine_id" => "设备编号",
                    "machine_name" => "设备名称",
                    "trade_no" => "订单编号",
                    "fd_type" => "活动类型",
                    "condition_type" => "条件类型",
                    "condition_value" => "条件数值",
                    "active_value" => "活动值",
                    "g_name" => "商品名称",
                    "sku" => "商品名称",
                ];
                $filename = "导出【" . $fd['fd_name'] . "】使用报表-" . date("Ymd");
                $result = Excel::exportExcel($usedList, $title, $filename);
                return $this->r(200, $this->lang("export_success"), $result);
            }
            return $this->rFail($this->lang("VActivity.usedList_no_data"));
        } catch (\PHPExcel_Writer_Exception $e) {
            actionException($e, 1);
            return $this->rValidate($e->getMessage());
        } catch (\PHPExcel_Exception $e) {
            actionException($e, 1);
            return $this->rValidate($e->getMessage());
        }
    }
}