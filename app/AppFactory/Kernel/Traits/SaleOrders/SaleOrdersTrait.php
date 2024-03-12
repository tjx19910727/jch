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
    public function getSaleOrdersValue($where,$value)
    {
        return SaleOrdersModel::getFieldValue($where,$value);
    }
    /**
     * 订单主表字段求和
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
    public function getSaleOrdersFind($where,$field = "*",$order = "",$group = "")
    {
        return SaleOrdersModel::getFind($where,$field,$order,$group);
    }

    /**
     * 定时统计商品日销售数据
     * @param $where
     * @param string $field
     * @param string $group
     * @return mixed
     */
    public function getSaleOrdersDetailsData($where,$field = "*", $group = "")
    {
        return SaleOrdersModel::collectDetailsData($where,$field,$group);
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
            $item['details'] = $this->getSaleOrdersDetailsList(['order_id' => $item['order_id']],0);
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
        actionLog($this->getLS(),'生成订单SQL');
        actionLog($order,'生成订单结果');
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
     * 订单副表字段求和
     * @param $where
     * @param $sum
     * @return float
     */
    public function getSaleOrdersDetailsSum($where,$sum)
    {
        return SaleOrdersDetailsModel::getSum($where,$sum);
    }

    /**
     * 生成订单详情
     * @param $insert
     * @return mixed
     */
    public function addSaleOrdersDetails($insert)
    {
        $sod = SaleOrdersDetailsModel::create($insert);
        actionLog($this->getLS(),'生成订单详情SQL');
        actionLog($sod,'生成订单详情结果');
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

    /**
     * 增加订单副表字段值
     * @param $where
     * @param $field
     * @param int $inc
     * @return mixed
     */
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

    /**
     * 获取商品排行榜
     * @param $where
     * @param $pageNum
     * @param string $field
     * @param string $order
     * @param string $group
     * @return \think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function getSaleGoodsRankingList($where,$pageNum,$field = '*',$order = '',$group = '')
    {
        return SaleOrdersDetailsModel::goodsRankingList($where,$pageNum,$field,$order,$group);
    }

    /**
     * 上传订单交易视频路径
     * @return SaleOrdersModel
     */
    public function transactionVideo()
    {
        return $this->updateSaleOrders(['transaction_video' => $this->message['transaction_video']],['trade_no' => $this->message['trade_no']]);
    }

}