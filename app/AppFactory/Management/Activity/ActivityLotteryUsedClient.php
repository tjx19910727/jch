<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/26
 * Time: 8:49
 */

namespace app\AppFactory\Management\Activity;


use app\AppFactory\Kernel\Support\Excel;
use app\AppFactory\Kernel\Traits\Activity\ActivityLotteryContentTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityLotteryTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityLotteryUsedGoodsTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityLotteryUsedTrait;
use app\AppFactory\Management\ManagementClient;

class ActivityLotteryUsedClient extends ManagementClient
{
    use ActivityLotteryTrait, ActivityLotteryContentTrait, ActivityLotteryUsedTrait, ActivityLotteryUsedGoodsTrait;

    /**
     * 获取付费抽奖使用列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return array|\think\response\Json
     */
    public function getUsedList($where,$pageNum = 0,$field = "*",$order = "")
    {
        $list = $this->getActivityLotteryUsedList($where,$pageNum,$field,$order);
        if ($list) {
            $list = $list->each(function ($item) {
                $content = $this->getActivityLotteryContentFind(['c_id' => $item['alc_id']], "content_name");
                $usedGoods = $this->getActivityLotteryUsedGoodsList(['alu_id' => $item['alu_id']],0,'*,("' . $content['content_name'] . '") content_name');
                $item['Children'] = $usedGoods;
                return $item;
            });
        }
        return $this->rQ($list);
    }

    /**
     * 获取使用商品列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @return array|\think\response\Json
     */
    public function getUsedGoodsList($where,$pageNum = 0,$field = "*")
    {

        $list = $this->getActivityLotteryUsedGoodsList($where,$pageNum,$field);
        if ($list) {
            $list = $list->each(function ($item) {
                $alu = $this->getActivityLotteryUsedFind(['alu_id' => $item['alu_id']],"alc_id");
                $content = $this->getActivityLotteryContentFind(['c_id' => $alu['alc_id']],"content_name");
                $item['content_name'] = $content['content_name'];
                return $item;
            });
        }
        return $this->rQ($list);
    }

    /**
     * @param $al_id
     * @return array|string
     */
    public function exportList($al_id)
    {

        $whereAl['al_id'] = $al_id;
        $lottery = $this->getActivityLotteryFind($whereAl);
        $whereUsed['ug.al_id'] = $al_id;
        $list = $this->getActivityLotteryUsedGoodsExportList($whereUsed,
            "ug.g_name,ug.sku,ug.channel_code,ug.quantity,ug.probability,ug.out_success,ug.out_fail,
                alu.trade_no,alu.machine_id,alu.machine_name,alu.price,alu.quantity total_quantity,alu.total_price,
                (SELECT content_name FROM activity_lottery_content WHERE c_id = alu.alc_id limit 1) content_name,
                (CASE alu.active_type WHEN 1 THEN '单抽' ELSE '连抽' END) active_type,
                FROM_UNIXTIME(used_date,'%Y-%m-%d') used_date,
                (CASE alu.status WHEN 1 THEN '待抽奖' WHEN 2 THEN '已使用' WHEN 3 THEN '已过期' WHEN 4 THEN '已作废' END) status,
                FROM_UNIXTIME(alu.create_time,'%Y-%m-%d %H:%i:%s') create_time");
        if ($list) {
            $list = $list->toArray();
            if ($list) {
                $title = [
                    "trade_no" => "订单编号",
                    "machine_name" => "设备名称",
                    "machine_id" => "设备编号",
                    "g_name" => "商品名称",
                    "content_name" => "奖项",
                    "price" => "抽奖单价",
                    "total_quantity" => "抽奖次数",
                    "total_price" => "实际支付总金额",
                    "active_type" => "抽奖类型",
                    "status" => "状态",
                    "create_time" => "创建时间",
                ];
                $filename = "【" . $lottery['lottery_name'] . "】使用报表-" . date("Ymd");
                return $this->sendToExport("营销活动-付费抽奖", $filename, $title, $list);
            }
        }
        return $this->rFail($this->lang("VActivity.usedList_no_data"));
    }
}