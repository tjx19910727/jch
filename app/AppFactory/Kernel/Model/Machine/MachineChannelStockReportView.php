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
     * 每台设备的同一商品仅关联一条备用库存，兼容历史重复设备商品记录。
     *
     * @return string
     */
    protected static function standbyStockSubQuery()
    {
        return '(SELECT m_id stock_m_id,g_id stock_g_id,MAX(standby_stock) standby_stock'
            . ' FROM machine_goods GROUP BY m_id,g_id)';
    }

    public static function getList($where, $pageNum = null, $field = "*", $order = "", $eachFn = "", $group = "", $limit = 0)
    {
        if (isset($where['ao_id']) && $where['ao_id'] < 2) {
            unset($where['ao_id']);
        }
        $whereRaw = $where['raw'] ?? '';
        unset($where['raw']);
        $data = self::alias('a')
            ->leftJoin([self::standbyStockSubQuery() => 'mg_stock'], 'mg_stock.stock_m_id = a.m_id AND mg_stock.stock_g_id = a.g_id')
            ->where($where)
            ->field($field)
            ->order($order);
        if ($whereRaw !== '') $data = $data->whereRaw($whereRaw);
        if ($group) $data = $data->group($group);
        if ($limit) $data = $data->limit($limit);
        if ($pageNum) {
            $data = $data->paginate($pageNum, false, ['query' => request()->param()]);
            if ($eachFn && is_callable($eachFn)) $data = $data->each($eachFn);
            return $data;
        }
        return $data->select();
    }

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
            ->leftJoin([self::standbyStockSubQuery() => 'mg_stock'], 'mg_stock.stock_m_id = mcs.m_id AND mg_stock.stock_g_id = mcs.g_id')
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
