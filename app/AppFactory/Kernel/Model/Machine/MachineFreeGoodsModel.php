<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/26
 * Time: 15:33
 */

namespace app\AppFactory\Kernel\Model\Machine;


use app\AppFactory\Kernel\Model\BaseModel;

class MachineFreeGoodsModel extends BaseModel
{
    protected $pk = "mfg_id";
    protected $name = "machine_free_goods";

    public static function getJoinGoodsList($where,$pageNum = 0, $field = "*", $order = "")
    {
        $data = self::alias("mfg")
            ->join("goods g","g.g_id = mfg.g_id","left")
            ->where($where)
            ->field($field)
            ->order($order);
        if ($pageNum) {
            return $data->paginate($pageNum,false,['query' => request()->param()]);
        }
        return $data->select();
    }
}