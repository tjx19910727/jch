<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/30
 * Time: 10:15
 */

namespace app\AppFactory\Kernel\Model\SaleOrders;


use app\AppFactory\Kernel\Model\BaseModel;

class SaleOrdersDetailsModel extends BaseModel
{
    protected $pk = "sod_id";
    protected $name = "sale_orders_details";

//    protected $schema = [
//        "sod_id" => "int",
//        "order_id" => "int",
//        "ss_id" => "int",
//        "shelves_number" => "int",
//        "wg_id" => "int",
//        "goods_id" => "int",
//        "goods_name" => "string",
//        "goods_pic" => "string",
//        "gc_id" => "int",
//        "gc_name" => "string",
//        "cost_price" => "float",
//        "retail_price" => "float",
//        "total_sod_price" => "float",
//        "quantity" => "int",
//        "bar_code" => "string",
//        "batch_number" => "string",
//        "manufacture_time" => "int",
//        "sell_by_date" => "int",
//        "refund_quantity" => "int",
//    ];


    public function saleOrders()
    {
        return $this->belongsTo(SaleOrdersModel::class,'order_id');
    }

    /**
     * 商品销售排行榜
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @param string $group
     * @return \think\Paginator
     * @throws \think\db\exception\DbException
     */
    public static function goodsRankingList($where,$pageNum = 0, $field = '*',$order = '', $group = '')
    {
        $data = self::alias('sod')
            ->join("sale_orders so",'so.order_id = sod.order_id','left')
            ->where($where)
            ->field($field)
            ->order($order)
            ->group($group)
            ->paginate($pageNum);
        return $data;
    }
}