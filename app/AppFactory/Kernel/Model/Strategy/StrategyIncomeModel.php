<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/10
 * Time: 14:28
 */

namespace app\AppFactory\Kernel\Model\Strategy;


use app\AppFactory\Kernel\Model\BaseModel;

class StrategyIncomeModel extends BaseModel
{
    protected $pk = "si_id";
    protected $name = "strategy_income";

    protected $schema = [
        "si_id" => "int",
        "income_name" => "string",
        "income_value" => "int",
        "desc" => "string",
        "transfer_method" => "int",
        "status" => "int",
        "creator" => "int",
        "create_time" => "int",
        "update_id" => "int",
        "update_time" => "int",
    ];

    /**
     * 通过设备获取分润策略
     * @param $where
     * @param string $field
     * @param string $order
     * @return StrategyIncomeModel[]|array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getJoinStrategyMachineList($where,$field = "*",$order = "")
    {
        $data = self::alias("si")
            ->join("machine m","m.m_id = si.m_id",'left')
            ->where($where)
            ->field($field)
            ->order($order)
            ->select();
        return $data;
    }
}