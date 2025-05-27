<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/3
 * Time: 12:02
 */

namespace app\AppFactory\Kernel\Model\SaleOrders;


use app\AppFactory\Kernel\Model\BaseModel;

class SaleHotelModel extends BaseModel
{
    protected $pk = "sh_id";
    protected $name = "sale_hotel";

    public static function joinSaleOrdersList($where,$pageNum = 0, $field = "*", $order = "")
    {
        $data = self::alias("sh")
            ->join("sale_orders so", "so.order_id = sh.order_id","left")
            ->where($where)
            ->field($field)
            ->order($order);
        if ($pageNum) {
            $data = $data->paginate($pageNum,false,['query' => request()->param()]);
            return $data;
        }
        $data = $data->select();
        return $data;
    }
}