<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:26
 */

namespace app\AppFactory\Kernel\Model\Machine;


use app\AppFactory\Kernel\Model\BaseModel;

class MachineChannelModel extends BaseModel
{
    protected $pk = "mc_id";
    protected $name = "machine_channel";

    public static function joinGoodsList($where,$field = "*", $order = "",$group = "")
    {
        $data = self::alias("mc")
            ->join("goods g","g.g_id = mc.g_id","left")
            ->join("machine m","m.m_id = mc.m_id","left")
            ->where($where)
            ->field($field)
            ->order($order)
            ->group($group)
            ->select();
        return $data;
    }
}