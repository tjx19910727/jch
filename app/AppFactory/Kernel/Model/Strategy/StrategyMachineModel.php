<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/30
 * Time: 17:35
 */

namespace app\AppFactory\Kernel\Model\Strategy;


use app\AppFactory\Kernel\Model\BaseModel;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

class StrategyMachineModel extends BaseModel
{
    protected $pk = "sm_id";
    protected $name = "strategy_machine";

    protected $schema = [
        "sm_id" => "int",
        "s_id" => "int",
        "m_id" => "int",
        "s_type" => "int",
        "sort" => "int",
    ];

    protected static $join = [
        1 => "strategy_payee sp",
//        2 => "strategy_charge sc",
//        4 => "open_platform_wx wx",
//        5 => "open_platform_wx wx",
//        6 => "strategy_hosting sh",
        7 => "strategy_agreement sa",
    ];

    protected static $condition = [
        1 => "sm.s_id = sp.sp_id",
        2 => "sm.s_id = sc.sc_id",
        4 => "sm.s_id = wx.wx_id",
        5 => "sm.s_id = wx.wx_id",
        6 => "sm.s_id = sh.st_id",
        7 => "sm.s_id = sa.sa_id",
    ];

    /**
     * 获取一条策略
     * @param $where
     * @param $sType
     * @param string $field
     * @param string $order
     * @return StrategyMachineModel|array|mixed|null|string|\think\Model
     */
    public static function getStrategyFind($where, $field = "*", $order = "sm.sort asc")
    {
        try {
            $data = self::alias("sm")
                ->join(self::$join[$where['sm.s_type']], self::$condition[$where['sm.s_type']], "left")
                ->where($where)
                ->field($field)
                ->order($order)->find();
            if ($data) return $data->toArray();
            return $data;
        } catch (DataNotFoundException $e) {
            actionException($e,1);
            return $e->getMessage();
        } catch (ModelNotFoundException $e) {
            actionException($e,1);
            return $e->getMessage();
        } catch (DbException $e) {
            actionException($e,1);
            return $e->getMessage();
        }
    }
}