<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/4/9
 * Time: 10:00
 */

namespace app\AppFactory\TimeTask\Machine;


use app\AppFactory\Kernel\Traits\Machine\MachineChannelStockTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineChannelTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\TimeTask\TimeTaskBase;

class MachineChannelStockClient extends TimeTaskBase
{
    use MachineTrait,MachineChannelTrait,MachineChannelStockTrait;

    /**
     * 定时任务-分时段记录库存信息(暂时废弃,改实时获取)
     * @return string
     */
    public function countMcStock()
    {
        $field = "mc.m_id,mc.machine_id,m.machine_name,
        g.g_id,g.g_name,g.sku,g.bar_code,g.model,
        sum(CASE mc.status  WHEN 1 THEN mc.stock ELSE 0 END) mc_stock,
        sum(mc.frozen_stock) pre_stock,
        sum(CASE WHEN mc.mg_id > 0 THEN IFNULL((SELECT mg.standby_stock FROM machine_goods mg WHERE mg.mg_id = mc.mg_id),0) ELSE 0 END) standby_stock,
        sum(CASE mc.status  WHEN 3 THEN mc.stock ELSE 0 END) bad_stock,
        sum(mc.stock) total_stock,
        g.retail_price,m.ao_id
        ";
        $data = $this->getMachineChannelJoinGoodsList([['mc.g_id',">",0]],$field,'','m.ao_id,mc.m_id,mc.g_id');
        if ($data) {
            $data = $data->toArray();
            foreach ($data as $key => $value) {
                $insert = $value;
                $insert['create_date'] = strtotime(date("Y-m-d"));
                $insert['create_time'] = time();
                $insertAll[] = $insert;
            }
            $this->addMachineChannelStockMore($insertAll);
        }
        return "处理成功";
    }
}