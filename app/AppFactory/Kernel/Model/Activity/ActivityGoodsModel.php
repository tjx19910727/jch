<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/5
 * Time: 17:39
 */

namespace app\AppFactory\Kernel\Model\Activity;


use app\AppFactory\Kernel\Model\BaseModel;

class ActivityGoodsModel extends BaseModel
{
    protected $pk = "ag_id";
    protected $name = "activity_goods";

    public function getListByMachine($where,$field = "*",$order = "")
    {
        $data = self::alias("ag")
            ->join("machine_channel mc","mc.g_id = ag.g_id","left")
            ->where($where)
            ->field($field)
            ->order($order)
            ->select();
        return ($data  ? $data->toArray() : $data);
    }
}