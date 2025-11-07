<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/4/9
 * Time: 14:05
 */

namespace app\AppFactory\Kernel\Model\Machine;


use app\AppFactory\Kernel\Model\BaseModel;

class MachineChannelStockReportView extends BaseModel
{
    protected $name = "machine_channel_stock_report";

    /**
     * 关联设备表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @param string $group
     * @return mixed
     */
    public static function joinMachineList($where,$pageNum = 0,$field = "*", $order = "",$group = "")
    {
        $data = self::alias("mcs")
            ->join("machine m",'mcs.m_id = m.m_id','left')
            ->where($where)
            ->field($field)
            ->order($order)
            ->group($group);
        if ($pageNum)
            $data = $data->paginate($pageNum,false,['query' => request()->param()]);
        else
            $data = $data->select();
        return $data;
    }
}