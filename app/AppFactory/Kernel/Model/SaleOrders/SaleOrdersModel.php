<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/30
 * Time: 9:50
 */

namespace app\AppFactory\Kernel\Model\SaleOrders;


use app\AppFactory\Kernel\Model\BaseModel;
use app\AppFactory\Kernel\Model\Machine\MachineMqRecordModel;

class SaleOrdersModel extends BaseModel
{
    protected $pk = "order_id";
    protected $name = "sale_orders";
    protected $createTime = "create_time";

//    protected $schema = [
//        "order_id" => "int",
//        "trade_no" => "string",
//        "mch_no" => "string",
//        "user_id" => "int",
//        "user_name" => "string",
//        "store_id" => "int",
//        "store_name" => "string",
//        "store_manager" => "int",
//        "terminal_no" => "string",
//        "order_type" => "int",
//        "supplementary_payment" => "int",
//        "payment_status" => "int",
//        "payment_type" => "int",
//        "payment_method" => "int",
//        "payment_time" => "int",
//        "payment_code" => "string",
//        "refund_status" => "int",
//        "cost_price" => "float",
//        "total_price" => "float",
//        "total_quantity" => "int",
//        "pickup_time" => "int",
//        "remark" => "string",
//        "create_time" => "int",
//        "sp_id" => "int",
//    ];

    public function saleOrdersDetails()
    {
        return $this->hasMany(SaleOrdersDetailsModel::class,'order_id');
    }

    public static function collectDetailsData($where,$field,$group = "")
    {
        $data = self::alias("so")
            ->join("sale_orders_details sod","sod.order_id = so.order_id","left")
            ->where($where)
            ->field($field)
            ->group($group)
            ->find();
        return $data;
    }

    public static function joinSodColumn($where,$column,$group = "")
    {
        $data = self::alias("so")
            ->join("sale_orders_details sod","sod.order_id = so.order_id","left")
            ->where($where)
            ->group($group)
            ->column($column);
        return $data;
    }

    /**
     * 修复11月份已支付订单，但订单信息不完整的问题
     * @param $postData
     */

    public static function fixOrdersInfo($postData){
        $trade_nos = explode(",", $postData['trade_no']);
        $where[] = ['trade_no','in', $trade_nos];
        $orders = self::where($where)->select();
        foreach ($orders as $order) {
            $order->pay_time = $order->create_time + 15;
            $order->pay_status = 3;
            $order->manager_id = $postData['manager_id'];
            $order->save();
        }
        return "修复完成";
    }

}