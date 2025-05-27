<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/4/9
 * Time: 9:02
 */

namespace app\management\controller\machine;


use app\management\controller\Common;

class MachineChannelStock extends Common
{

    protected $field = "sku,g_name,bar_code,model,
    SUM(mc_stock) mc_stock,
    SUM(pre_stock) pre_stock,
    SUM(standby_stock) standby_stock,
    SUM(bad_stock) bad_stock,
    SUM(total_stock) total_stock,retail_price";

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ["machine_id" => "like","sku" => "like"]);
        $order = "sku desc";
        $group = "";
        if (!isset($postData['machine_id'])) $group = "g_id";
        return $this->app->machineChannelStock->getMcsList($where,$pageNum,$this->field,$order,$group);
    }
}