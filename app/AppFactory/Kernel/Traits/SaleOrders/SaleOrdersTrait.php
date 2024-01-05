<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/30
 * Time: 10:21
 */

namespace app\AppFactory\Kernel\Traits\SaleOrders;


use app\AppFactory\Kernel\Model\SaleOrders\SaleOrdersDetailsModel;
use app\AppFactory\Kernel\Model\SaleOrders\SaleOrdersModel;

trait SaleOrdersTrait
{
    /**
     * 字段求和
     * @param $where
     * @param $sum
     * @return float
     */
    public function getSaleOrdersSum($where,$sum)
    {
        return SaleOrdersModel::getSum($where,$sum);
    }

    /**
     * 获取一条订单数据
     * @param $where
     * @param string $field
     * @param string $order
     * @return SaleOrdersModel|array|mixed|null|\think\Model
     */
    public function getSaleOrdersFind($where,$field = "*",$order = "")
    {
        return SaleOrdersModel::getFind($where,$field,$order);
    }

    /**
     * 查询销售订单列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @param string $eachFn
     * @param string $group
     * @return SaleOrdersModel|SaleOrdersModel[]|array|\think\Collection|\think\Paginator
     * @throws \Exception
     */
    public function getSaleOrdersList($where,$pageNum = 0, $field = "*",$order = "",$eachFn = '',$group = '', $limit = 0)
    {
        $data = SaleOrdersModel::getList($where,$pageNum,$field,$order,$eachFn,$group,$limit)->each(function ($item) {
            $item['total_price'] = round($item['total_price'],2);
            return $item;
        });
        return $data;
    }

    /**
     * 生成订单
     * @param $insert
     * @return mixed
     */
    public function addSaleOrders($insert)
    {
        $order = SaleOrdersModel::create($insert);
        return $order->order_id;
    }

    /**
     * 修改订单
     * @param $update
     * @param array $where
     * @param array $field
     * @return SaleOrdersModel
     */
    public function updateSaleOrders($update,$where = [],$field = [])
    {
        return SaleOrdersModel::update($update,$where,$field);
    }

    /**
     * 获取一条订单详情
     * @param $where
     * @param string $field
     * @param string $order
     * @return SaleOrdersDetailsModel|array|mixed|null|\think\Model
     */
    public function getSaleOrdersDetailsFind($where,$field = "*",$order = "")
    {
        return SaleOrdersDetailsModel::getFind($where,$field,$order);
    }

    /**
     * 获取订单详情列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return SaleOrdersDetailsModel|SaleOrdersDetailsModel[]|array|\think\Collection|\think\Paginator
     * @throws \Exception
     */
    public function getSaleOrdersDetailsList($where,$pageNum = 0,$field = "*",$order = "")
    {
        $data = SaleOrdersDetailsModel::getList($where,$pageNum,$field,$order)->each(function ($item) {
            $item['cost_price'] = round($item['cost_price'],2);
            $item['retail_price'] = round($item['retail_price'],2);
            $item['total_sod_price'] = round($item['total_sod_price'],2);
            return $item;
        });
        return $data;
    }

    /**
     * 获取销售订单字段列
     * @param $where
     * @param $column
     * @return array
     */
    public function getSaleOrdersColumn($where,$column)
    {
        $data = SaleOrdersDetailsModel::getColumn($where,$column);
        return $data;
    }

    /**
     * 获取销售订单详情字段列
     * @param $where
     * @param $column
     * @return array
     */
    public function getSaleOrdersDetailsColumn($where,$column)
    {
        $data = SaleOrdersDetailsModel::getColumn($where,$column);
        return $data;
    }

    /**
     * 生成订单详情
     * @param $insert
     * @return mixed
     */
    public function addSaleOrdersDetails($insert)
    {
        $sod = SaleOrdersDetailsModel::create($insert);
        return $sod->sod_id;
    }

    /**
     * 修改订单详情
     * @param $update
     * @param array $where
     * @param array $field
     * @return SaleOrdersDetailsModel
     */
    public function updateSaleOrdersDetails($update ,$where = [],$field = [])
    {
        return SaleOrdersDetailsModel::update($update,$where,$field);
    }

    public function incSaleOrdersDetails($where,$field,$inc = 1)
    {
        return SaleOrdersDetailsModel::setInc($where,$field,$inc);
    }
    /**
     * 获取订单编号
     * @param string $msg
     * @return string
     */
    public function getSaleOrdersTradeNo($msg = "")
    {
        while(1){
            $trade_no = date("YmdHis") . ($msg ? $msg : $this->get_rand_string(6));
            if (!SaleOrdersModel::be(['trade_no' => $trade_no])) {
                return $trade_no;
            }
        }
    }


    public function getSaleGoodsRankingList($where,$pageNum,$field = '*',$order = '',$group = '')
    {
        return SaleOrdersDetailsModel::goodsRankingList($where,$pageNum,$field,$order,$group);
    }

}