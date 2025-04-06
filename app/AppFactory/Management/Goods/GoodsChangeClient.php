<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/18
 * Time: 14:10
 */

namespace app\AppFactory\Management\Goods;


use app\AppFactory\Kernel\Support\Excel;
use app\AppFactory\Kernel\Traits\Goods\GoodsChangeTrait;
use app\AppFactory\Management\ManagementClient;

class GoodsChangeClient extends ManagementClient
{
    use GoodsChangeTrait;

    /**
     * 导出商品变化Excel
     * @param $where
     * @return array|\think\response\Json
     */
    public function exportGoodsChange($where)
    {
        $list = $this->getGoodsChangeList($where,0,'machine_id,machine_name,channel_code,sku,g_name,change_value,
        (CASE position WHEN 1 THEN "货架" WHEN 2 THEN "设备商品库" END) position,
        (CASE type 
            WHEN 1 THEN "未知" 
            WHEN 2 THEN "上货" 
            WHEN 3 THEN "下货" 
            WHEN 4 THEN "库存盘盈" 
            WHEN 5 THEN "库存盘亏" 
            WHEN 6 THEN "后台上货（货架）" 
            WHEN 7 THEN "后台下货（货架）" 
            WHEN 8 THEN "后台手动BAD（货架）" 
            WHEN 9 THEN "后台手动恢复BAD（货架）" 
            WHEN 10 THEN "终端上报BAD（货架）" 
            WHEN 11 THEN "终端恢复BAD（货架）" 
            ELSE "未知"
        END) type
        ,`desc`,creator,FROM_UNIXTIME(create_time) create_time','create_time desc');
        if (is_string($list)) return $this->rFail($list);
        if ($list) {
            $list = $list->toArray();
            $title = [
                "machine_id" => "设备编号",
                "machine_name" => "设备名称",
                "channel_code" => "货架编号",
                "change_value" => "变化数量",
                "type" => "变化类型",
                "position" => "位置",
                "creator_nickname" => "操作人",
                "create_time" => "操作时间",
            ];
            $filename = "商品变化-" . date("Ymd");
            return $this->sendToExport("事件日志-商品变化", $filename, $title, $list);
        }
        return $this->rFail($this->lang("action_fail"));
    }
}