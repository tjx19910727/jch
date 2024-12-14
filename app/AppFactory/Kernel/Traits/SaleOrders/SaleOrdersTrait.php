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
use app\AppFactory\Kernel\Support\Validate\Api\VV2;

trait SaleOrdersTrait
{
    public function getSaleOrdersValue($where, $value)
    {
        return SaleOrdersModel::getFieldValue($where, $value);
    }

    /**
     * 订单主表字段求和
     * @param $where
     * @param $sum
     * @return float
     */
    public function getSaleOrdersSum($where, $sum)
    {
        return SaleOrdersModel::getSum($where, $sum);
    }

    /**
     * 获取一条订单数据
     * @param $where
     * @param string $field
     * @param string $order
     * @return SaleOrdersModel|array|mixed|null|\think\Model
     */
    public function getSaleOrdersFind($where, $field = "*", $order = "", $group = "")
    {
        return SaleOrdersModel::getFind($where, $field, $order, $group);
    }

    /**
     * 定时统计商品日销售数据
     * @param $where
     * @param string $field
     * @param string $group
     * @return mixed
     */
    public function getSaleOrdersDetailsData($where, $field = "*", $group = "")
    {
        return SaleOrdersModel::collectDetailsData($where, $field, $group);
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
    public function getSaleOrdersList($where, $pageNum = 0, $field = "*", $order = "", $eachFn = '', $group = '', $limit = 0)
    {
        $data = SaleOrdersModel::getList($where, $pageNum, $field, $order, $eachFn, $group, $limit);
        if ($pageNum)
            $data = $data->each(function ($item) {
                $item['details'] = $this->getSaleOrdersDetailsList(['order_id' => $item['order_id']], 0);
                if ($item['has_hotel'] == 1) {
                    $item['hotel'] = $this->getSaleHotelFind(['order_id' => $item['order_id']]);
                    $item['hotel']['nightly'] = $this->getSaleHotelNightlyList(['sh_id' => $item['hotel']['sh_id']]);
                }
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
        actionLog($this->getLS(), '生成订单SQL');
        actionLog($order, '生成订单结果');
        return $order->order_id;
    }

    /**
     * 修改订单
     * @param $update
     * @param array $where
     * @param array $field
     * @return SaleOrdersModel
     */
    public function updateSaleOrders($update, $where = [], $field = [])
    {
        return SaleOrdersModel::update($update, $where, $field);
    }

    public function joinSoSodColumn($where, $column, $group = "")
    {
        return SaleOrdersModel::joinSodColumn($where, $column, $group);
    }

    public function getSaleOrdersDetailsJoinOrderList($where, $pageNum = 0, $field = "*", $order = "", $group = "")
    {
        return SaleOrdersDetailsModel::joinOrderList($where, $pageNum, $field, $order, $group);
    }

    public function getSaleOrdersDetailsJoinOrderFind($where,$field = "*")
    {
        return SaleOrdersDetailsModel::joinOrderFind($where,$field);
    }

    /**
     * 获取一条订单详情
     * @param $where
     * @param string $field
     * @param string $order
     * @return SaleOrdersDetailsModel|array|mixed|null|\think\Model
     */
    public function getSaleOrdersDetailsFind($where, $field = "*", $order = "")
    {
        return SaleOrdersDetailsModel::getFind($where, $field, $order);
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
    public function getSaleOrdersDetailsList($where, $pageNum = 0, $field = "*", $order = "")
    {
        $data = SaleOrdersDetailsModel::getList($where, $pageNum, $field, $order)->each(function ($item) {
            $item['cost_price'] = round($item['cost_price'], 2);
            $item['retail_price'] = round($item['retail_price'], 2);
            $item['total_sod_price'] = round($item['total_sod_price'], 2);
            return $item;
        });
        return $data;
    }

    /**
     * 获取门票核销码
     * @return mixed
     * @throws \think\db\exception\DbException
     */
    public function getDetailsCheckOffCode()
    {
        while(1){
            $code = $this->get_rand_string(8,'num');
            if (!SaleOrdersDetailsModel::getCount(['checkOff_code' => $code,'checkOff_status' => 1])) {
                break;
            }
        }
        return $code;
    }

    /**
     * 获取销售订单字段列
     * @param $where
     * @param $column
     * @return array
     */
    public function getSaleOrdersColumn($where, $column)
    {
        $data = SaleOrdersDetailsModel::getColumn($where, $column);
        return $data;
    }

    /**
     * 获取销售订单详情字段列
     * @param $where
     * @param $column
     * @return array
     */
    public function getSaleOrdersDetailsColumn($where, $column)
    {
        $data = SaleOrdersDetailsModel::getColumn($where, $column);
        return $data;
    }

    /**
     * 订单副表字段求和
     * @param $where
     * @param $sum
     * @return float
     */
    public function getSaleOrdersDetailsSum($where, $sum)
    {
        return SaleOrdersDetailsModel::getSum($where, $sum);
    }

    public function joinSaleOrdersSum($where, $sum)
    {
        return SaleOrdersDetailsModel::joinOrderSum($where, $sum);
    }

    /**
     * 统计订单副表条数
     * @param $where
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function getSaleOrdersDetailsCount($where)
    {
        return SaleOrdersDetailsModel::getCount($where);
    }

    /**
     * 生成订单详情
     * @param $insert
     * @return mixed
     */
    public function addSaleOrdersDetails($insert)
    {
        $sod = SaleOrdersDetailsModel::create($insert);
        actionLog($this->getLS(), '生成订单详情SQL');
        actionLog($sod, '生成订单详情结果');
        return $sod->sod_id;
    }

    /**
     * 对外API预订商品生成订单
     */
    protected function createSo()
    {
        $pay_type = 0;
        $pay_method = 1;
        $this->order = [
            "trade_no" => $this->config['params']['order_no'],
            "mch_no" => $this->config['params']['order_no'],
            "user_name" => $this->config['params']['customer_name'] ?? "",
            "m_id" => $this->machine['m_id'],
            "machine_name" => $this->machine['machine_name'],
            "machine_id" => $this->machine['machine_id'],
            "ao_id" => $this->machine['ao_id'],
            "pay_type" => $pay_type,
            "pay_method" => $pay_method,
            "pay_status" => 3,
            "pay_time" => strtotime($this->config['params']['charge_time']) ?? "",
            "pay_code" => $this->params['pick_code'],
            "goods_type" => $this->params['goods_type'] ?? 1,
            "cost_price" => 0,
            "market_price" => 0,
            "retail_price" => 0,
            "total_quantity" => 0,
            "total_price" => 0,
            "discount_price" => 0,
            "create_date" => strtotime(date("Y-m-d")),
        ];
        $this->order['order_id'] = $this->addSaleOrders($this->order);
    }

    /**
     * 对外API预订商品生成订单数据
     * 创建订单副表数据，增加货架冻结库存，减少货架售卖库存
     * @return array|int|\think\response\Json
     */
    protected function createSod()
    {
        $flag = [];
        $details = json2arr($this->config['params']['order_detail']);
        actionLog($details, '商品数据');
        foreach ($details as $dk => $dv) {
            try {
                validate(VV2::class)->scene("order_detail")->check($dv);
            } catch (\Exception $e) {
                actionException($e, 1);
                return $this->returnData(6, $this->lang("msg." . 6) . "：" . $e->getMessage());
            }
            $whereMc = [];
            $whereMc['m_id'] = $this->machine['m_id'];
            $whereMc['g_id'] = $dk;
            $whereMc['status'] = 1;
            $mc = $this->getMachineChannelList($whereMc, 0,
                'mc_id,channel_code,frozen_stock,stock,shelf_way,channel_position,manufacture_time,sell_by_date,
                        mg_id,g_id,g_name,gc_id,gc_name,pic,sku,bar_code,batch_number,
                        cost_price,market_price',
                "stock desc");
            actionLog($this->getLS(),'【SQL】查询设备货架');
            if (!$mc) return $this->returnData(10, $this->lang("msg." . 10));
            if (is_string($mc)) return $this->returnData(10, $this->lang("msg." . 10) . "：" . $mc);
            $mc = $mc->toArray();
            if (!$mc) return $this->returnData(10, $dk . $this->lang("msg." . 10));
            actionLog($mc, "该设备下货架列表数据");
            // 总库存不足
            $totalStock = array_sum(array_column($mc, "stock"));
            if ($totalStock < $dv['quantity']) {
                $this->returnData = ["success" => false, "order_no" => $this->config['params']['order_no'], "error_msg" => [$dk => $dv['quantity']]];
                return $this->returnData(14, $this->lang("msg." . 14) . "：" . $this->lang("reserve_order.under_stock"), $this->returnData);
            }

            $insertSod = [];
            $insertSod['order_id'] = $this->order['order_id'];
            $insertSod['quantity'] = 0;
            $insertSod['discount_price'] = 0;
            $insertSod['retail_price'] = bcdiv($dv['item_price'], 100, 3);
            $insertDetails = [];
            $dvDiscountPrice = bcdiv($dv['discount_amount'], 100, 3);
            $dvQuantity = $dv['quantity'];
            foreach ($mc as $mck => $mcv) {
                if (!$insertDetails) {
                    $insertDetails = array_merge($mcv, $insertSod);
                    unset($insertDetails['frozen_stock'], $insertDetails['stock']);
                }
                // 一条货道库存不够
                if ($dv['quantity'] > $mcv['stock']) {
                    $insertDetails['quantity'] = $mcv['stock'];
                }
                if ($dv['quantity'] <= $mcv['stock']) {
                    $insertDetails['quantity'] = $dv['quantity'];
                }
                // 销售
                if ($dv['type'] == "sale") {
                    $insertDetails['total_sod_price'] = bcmul($insertDetails['retail_price'], $insertDetails['quantity'], 3);
                    $insertDetails['discount_price'] = bcmul($dvDiscountPrice, bcdiv($insertDetails['quantity'], $dvQuantity, 2), 3);
                }
                // 赠品
                if ($dv['type'] == "gift") {
                    $insertDetails['total_sod_price'] = 0;
                    $insertDetails['discount_price'] = 0;
                    $insertDetails['is_gift'] = 1;
                }
                $dv['quantity'] = bcsub($dv['quantity'], $insertDetails['quantity']);
                $updateMc = [
                    'frozen_stock' => bcadd($mcv['frozen_stock'], $insertDetails['quantity']),
                    'stock' => bcsub($mcv['stock'], $insertDetails['quantity']),
                    "mc_id" => $mcv['mc_id'],
                ];
                $flag[] = $this->addSaleOrdersDetails($insertDetails);
                actionLog($this->getLS(), '生成订单详情');
                $flag[] = $this->updateMachineChannel($updateMc);
                actionLog($this->getLS(), '修改货架库存');
                $this->order['cost_price'] = bcadd($this->order['cost_price'], $insertDetails['cost_price'], 3);
                $this->order['market_price'] = bcadd($this->order['market_price'], $insertDetails['market_price'], 3);
                $this->order['retail_price'] = bcadd($this->order['retail_price'], $insertDetails['retail_price'], 3);
                $this->order['total_quantity'] = bcadd($this->order['total_quantity'], $insertDetails['quantity'], 3);
                $insertDetails = [];
                if ($dv['quantity'] == 0)
                    break;
            }
            $this->order['total_price'] = bcadd($this->order['total_price'], bcdiv($dv['charge_amount'], 100, 3), 3);
            $this->order['discount_price'] = bcadd($this->order['discount_price'], bcdiv($dv['discount_amount'], 100, 3), 3);
        }
        actionLog($flag, '生成订单详情结果集');
        $result = flag_check($flag);
        if (!$result) return $this->returnData(14, $this->lang("msg." . 14));
        return $result;
    }


    /**
     * 修改订单详情
     * @param $update
     * @param array $where
     * @param array $field
     * @return SaleOrdersDetailsModel
     */
    public function updateSaleOrdersDetails($update, $where = [], $field = [])
    {
        return SaleOrdersDetailsModel::update($update, $where, $field);
    }

    /**
     * 增加订单副表字段值
     * @param $where
     * @param $field
     * @param int $inc
     * @return mixed
     */
    public function incSaleOrdersDetails($where, $field, $inc = 1)
    {
        return SaleOrdersDetailsModel::setInc($where, $field, $inc);
    }

    /**
     * 获取订单编号
     * @param string $msg
     * @return string
     */
    public function getSaleOrdersTradeNo($msg = "")
    {
        while (1) {
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
    public function getSaleGoodsRankingList($where, $pageNum, $field = '*', $order = '', $group = '')
    {
        return SaleOrdersDetailsModel::goodsRankingList($where, $pageNum, $field, $order, $group);
    }

    /**
     * 上传订单交易视频路径
     * @return SaleOrdersModel
     */
    public function transactionVideo()
    {
        return $this->updateSaleOrders(['transaction_video' => $this->message['transaction_video']], ['trade_no' => $this->message['trade_no']]);
    }

}