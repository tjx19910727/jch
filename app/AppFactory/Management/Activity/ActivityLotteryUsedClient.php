<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/26
 * Time: 8:49
 */

namespace app\AppFactory\Management\Activity;


use app\AppFactory\Kernel\Support\Excel;
use app\AppFactory\Kernel\Traits\Activity\ActivityLotteryTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityLotteryUsedGoodsTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityLotteryUsedTrait;
use app\AppFactory\Management\ManagementClient;

class ActivityLotteryUsedClient extends ManagementClient
{
    use ActivityLotteryTrait,ActivityLotteryUsedTrait,ActivityLotteryUsedGoodsTrait;

    /**
     * @param $al_id
     * @return array|string
     */
    public function exportList($al_id)
    {
        try {
            $whereAl['al_id'] = $al_id;
            $lottery = $this->getActivityLotteryFind($whereAl);
            $whereUsed['ug.al_id'] = $al_id;
            $usedList = $this->getActivityLotteryUsedGoodsExportList($whereUsed,
                "ug.g_name,ug.sku,ug.channel_code,ug.quantity,ug.probability,ug.out_success,ug.out_fail,
                alu.trade_no,alu.machine_id,alu.machine_name,alu.price,alu.quantity total_quantity,alu.total_price,(CASE alu.active_type WHEN 1 THEN '单抽' ELSE '连抽' END) active_type,
                FROM_UNIXTIME(used_date,'%Y-%m-%d') used_date,
                (CASE alu.status WHEN 1 THEN '待抽奖' WHEN 2 THEN '已使用' WHEN 3 THEN '已过期' WHEN 4 THEN '已作废' END) status,
                FROM_UNIXTIME(alu.create_time,'%Y-%m-%d %H:%i:%s') create_time");
            if ($usedList) {
                $usedList = $usedList->toArray();
                $title = [
                    "machine_id" => "设备编号",
                    "machine_name" => "设备名称",
                    "trade_no" => "订单编号",
                    "price" => "抽奖单价",
                    "total_quantity" => "抽奖次数",
                    "total_price" => "总价",
                    "active_type" => "抽奖方式",
                    "g_name" => "商品名称",
                    "sku" => "SKU",
                    "channel_code" => "货架编号",
                    "quantity" => "商品数量",
                    "probability" => "中奖概率",
                    "out_success" => "出货成功",
                    "out_fail" => "出货失败",
                    "used_date" => "日期",
                    "status" => "状态",
                    "create_time" => "创建时间",
                ];
                $filename = "导出【" . $lottery['lottery_name'] . "】使用报表-" . date("Ymd");
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