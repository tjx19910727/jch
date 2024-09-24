<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/3
 * Time: 10:26
 */

namespace app\AppFactory\Machine\Receive;


use app\AppFactory\Kernel\ServiceContainer;
use app\AppFactory\Kernel\Support\Trip\Trip;
use app\AppFactory\Kernel\Traits\Goods\GoodsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineChannelTrait;
use app\AppFactory\Kernel\Traits\Payment\TripPay;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleHotelNightlyTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleHotelTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersTrait;
use app\AppFactory\Kernel\Traits\Trip\TripCityTrait;
use app\AppFactory\Kernel\Traits\Trip\TripMultipleGoodsTrait;
use app\AppFactory\Kernel\Traits\Trip\TripMultipleHotelTrait;
use app\AppFactory\Kernel\Traits\Trip\TripMultipleMachineTrait;
use app\AppFactory\Kernel\Traits\Trip\TripMultipleTrait;
use app\machine\validate\VHotel;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

class HotelClient extends ReceiveBaseClient
{
    use TripCityTrait, TripPay;
    use SaleOrdersTrait,
        SaleHotelTrait, SaleHotelNightlyTrait;
    use TripMultipleTrait, TripMultipleMachineTrait, TripMultipleHotelTrait, TripMultipleGoodsTrait;
    use MachineChannelTrait;
    use GoodsTrait;

    protected $order;


    public function __construct(ServiceContainer $app)
    {
        parent::__construct($app);
//        $this->dataRecord();
    }

    /**
     * 销毁实例时触发
     */
    public function __destruct()
    {
        // TODO: Implement __destruct() method.
        $result = $this->updateMachineMqRecord(['status' => 2, 'msg_id' => $this->data['msg_id']], ['msg_id' => $this->data['msg_id']]);
        actionLog($result, '处理完成时修改状态为已处理');
    }

    /**
     * 获取携程套餐列表
     * @return array|\think\response\Json
     */
    public function getTripMultiple()
    {
        try {
            $tm_id = $this->getTripMultipleMachineColumn(['m_id' => $this->machine['m_id']], 'tm_id');
            $data = $this->getTripMultipleList([['tm_id', "in", $tm_id]], 0,
                'tm_id,tm_name,pic,status,designated_hotel,designated_goods,rise_fall_ratio');
            if ($data) {
                $data = $data->toArray();
                foreach ($data as $key => $value) {
                    if ($value['designated_hotel'] > 1) {
                        $whereTmh['tm_id'] = $value['tm_id'];
                        $tmhField = "hotelId,rise_fall_ratio,is_require";
                        $hotelList = $this->getTripMultipleHotelList($whereTmh, 0, $tmhField);
                        if ($hotelList) {
                            $hotelList = $hotelList->toArray();
                        }
                        $value['hotelList'] = $hotelList;
                    }
                    if ($value['designated_goods'] > 1) {
                        $tmgField = "tmg_id,tmg.is_required,tmg.buy_lower,tmg.buy_upper,tmg.sale_amount,tmg.rise_fall_ratio,tmg.g_id,g.g_type";
                        $whereTmg['tm_id'] = $value['tm_id'];
                        $goodsList = $this->getTripMultipleGoodsJoinGoodsList($whereTmg, 0, $tmgField);
                        if ($goodsList) {
                            $goodsList = $goodsList->toArray();
                        }
                        $value['goodsList'] = $goodsList;
                    }
                    $data[$key] = $value;
                }
            }
            return $this->r(200, $this->lang("query_success"), $data);
        } catch (DataNotFoundException $e) {
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        } catch (ModelNotFoundException $e) {
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        } catch (DbException $e) {
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        } catch (\Exception $e) {
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 获取携程城市列表
     * @return array|\think\response\Json
     */
    public function getCity()
    {
        $where = [];
        if (isset($this->data['cityName'])) $where[] = ['cityName', 'like', "%" . $this->data['cityName'] . "%"];
        $city = $this->getTripCityList($where, $this->data['pageNum'] ?? 0, "cityId,cityName");
        return $this->r(200, $this->lang('query_success'), ['city' => $city]);
    }

    /**
     * 根据城市ID获取酒店列表
     * @return array|\think\response\Json
     */
    public function getList()
    {
        $params = [
            "cityId" => $this->data['cityId'],
            "adults" => $this->data['adults'],
            "quantity" => $this->data['quantity'],
            "checkInDate" => $this->data['checkInDate'],
            "checkOutDate" => $this->data['checkOutDate'],
            "pageNo" => $this->data['page'],
            "pageSize" => $this->data['pageNum'],
        ];
        $result = Trip::hotel()->getList($params);
        $result = json2arr($result);
        if ($result && isset($result['code']) && $result['code'] == 0) {
            return $this->r(200, $this->lang('query_success'), ['list' => $result['result'], 'totalCount' => $result['totalCount']]);
        }
        return $this->r(100, $this->lang('query_fail') . isset($result['message']) ? ":" . $result['message'] : "");
    }

    /**
     * 获取酒店详情
     * @return array|\think\response\Json
     */
    public function getDetails()
    {
        $result = Trip::hotel()->getDetailsList(['hotelId' => $this->data['hotelId']]);
        $result = json2arr($result);
        if ($result && $result['code'] == 0) {
            return $this->r(200, $this->lang('query_success'), $result['result']);
        }
        return $this->r(100, $this->lang('query_fail') . isset($result['message']) ? ":" . $result['message'] : "");
    }

    /**
     * 获取酒店房型
     * @return array|\think\response\Json
     */
    public function getRoomList()
    {
        $result = Trip::hotel()->getRoomList([
            'hotelId' => $this->data['hotelId'],
            'adults' => $this->data['adults'],
            'quantity' => $this->data['quantity'],
            'checkInDate' => $this->data['checkInDate'],
            'checkOutDate' => $this->data['checkOutDate']]);
        $result = json2arr($result);
        if ($result && $result['code'] == 0) {
            return $this->r(200, $this->lang('query_success'), ['result' => $result['result'], 'logId' => $result['logId']]);
        }
        return $this->r(100, $this->lang('query_fail') . isset($result['message']) ? ":" . $result['message'] : "");
    }

    /**
     * 验证房间是否可订
     * @return boolean|string
     */
    protected function availableCheck()
    {
        $nightlyPrice = [];
        foreach ($this->data['hotelList']['roomPriceList'] as $rk => $rv) {
            try {
                validate(VHotel::class)->scene("roomPriceList")->check($rv);
            } catch (\Exception $e) {
                $this->rollbackTrans();
                return $this->rTryCatch($e->getMessage());
            }
            $nightlyPrice[] = [
                "stayDate" => $rv['effectiveDate'],
                "salePrice" => $rv['amount'],
            ];
        }
        $params = [
            "machineId" => $this->data['machine_id'],
            "hotelId" => $this->data['hotelList']['hotelId'],
            "roomId" => $this->data['hotelList']['roomId'],
            "count" => $this->data['hotelList']['adults'],
            "quantity" => $this->data['hotelList']['num'],
            "checkInDate" => $this->data['hotelList']['checkInDate'],
            "checkOutDate" => $this->data['hotelList']['checkOutDate'],
            "logId" => $this->data['hotelList']['logId'],
            "tripData" => $this->data['hotelList']['tripData'],
            "nightlyPrice" => $nightlyPrice,
        ];
        $result = Trip::order()->availableCheck($params);
        $result = json2arr($result);
        actionLog($result, '验证可订房型结果');
        if (isset($result['code']) && $result['code'] == 0) {
            if ($result['result']['availabilityStatus'] != "available") {
                return $result['result']['reason'];
            }
            return true;
        }
        return $this->lang("query_fail") . "：" . $result['message'] ?? "";
    }

    /**
     * 提交携程套餐购物车
     * @return array|\think\response\Json
     */
    public function subHotel()
    {
        $this->data['hotelList'] = json2arr($this->data['hotelList']);
        if (!isset($this->data['hotelList']['roomPriceList'])) {
            return $this->r(100, $this->lang("VSubGoodsMultipleOrder.roomPriceList_required"));
        }
        if ($this->data['hotelList']['totalPrice'] != bcdiv(array_sum(array_column($this->data['hotelList']['roomPriceList'],'amount')),100,2)) {
            actionLog(['计算的订单总价' => bcdiv(array_sum(array_column($this->data['hotelList']['roomPriceList'],'amount')),100,2),'传入的订单总价' => $this->data['hotelList']['pay_amount']],'酒店与房间总价不相等');
            return $this->r(100,$this->lang("VSubGoodsMultipleOrder.hotel_amount_not_eq_total_room_price"));
        }
        // 验证房间是否可订
        $checkHotel = $this->availableCheck();
        if ($checkHotel !== true) {
            return $this->r(100,$checkHotel);
        }
        $updateOrder = [];
        $this->startTrans();
        try {
            $tripMultiple = $this->getTripMultipleFind(['tm_id' => $this->data['tm_id']]);
            if (!$tripMultiple) return $this->r(100, $this->lang("VHotel.tripMultipleNotData"));
            $tripMultiple['hotelList'] = $this->getTripMultipleHotelList(['tm_id' => $this->data['tm_id']]);
            if ($tripMultiple['hotelList']) $tripMultiple['hotelList'] = $tripMultiple['hotelList']->toArray();
            $tripMultiple['goodsList'] = $this->getTripMultipleGoodsJoinGoodsList(['tm_id' => $this->data['tm_id']]);
            if ($tripMultiple['goodsList']) $tripMultiple['goodsList'] = $tripMultiple['goodsList']->toArray();
            actionLog($tripMultiple, '这台设备的携程套餐数据');
            $trade_no = date("YmdHis") . $this->machine['m_id'] . $this->get_rand_string(6, "num");
            if (!isset($this->data['mobile']) || !$this->data['mobile']) return $this->r(100, $this->lang("VHotel.mobile_require"));
            $order = [
                "trade_no" => $trade_no,
                "m_id" => $this->machine['m_id'],
                "machine_name" => $this->machine['machine_name'],
                "machine_id" => $this->machine['machine_id'],
                "ao_id" => $this->machine['ao_id'],
                "pay_type" => 5,
                "pay_method" => 1,
                "mobile" => $this->data['mobile'] ?? "",
                "create_date" => strtotime(date("Y-m-d")),
            ];
            $order_id = $this->addSaleOrders($order);
            actionLog($this->getLS(), '添加订单SQL');
            if ($order_id) {
                $this->order = $this->getSaleOrdersFind(['order_id' => $order_id]);
                $updateOrder['order_id'] = $order_id;
                $updateOrder['cost_price'] = 0;
                $updateOrder['market_price'] = 0;
                $updateOrder['retail_price'] = 0;
                $updateOrder['quantity'] = 0;
                $updateOrder['total_price'] = 0;
                $updateOrder['total_quantity'] = 0;
                if (!isset($this->data['carList']) || !$this->data['carList']) {
                    $this->rollbackTrans();
                    return $this->rFail($this->lang("VSubGoodsMultipleOrder.carList"));
                }
                // 订单增加商品详情
                $this->data['carList'] = json2arr($this->data['carList']);
                $gIds = array_column($tripMultiple['goodsList'], 'g_id');
                foreach ($this->data['carList'] as $key => $value) {
                    try {
                        validate(VHotel::class)->scene("carList")->check($value);
                    } catch (\Exception $e) {
                        $this->rollbackTrans();
                        return $this->rTryCatch($e->getMessage());
                    }
                    // 指定商品列表，不在范围内
                    if ($tripMultiple['designated_goods'] == 2 && !in_array($value['g_id'], $gIds)) {
                        $this->rollbackTrans();
                        return $this->rFail($this->lang("VSubGoodsMultipleOrder.designated_goods_not_in"));
                    }
                    // 指定商品列表除外，在范围内
                    if ($tripMultiple['designated_goods'] == 3 && in_array($value['g_id'], $gIds)) {
                        $this->rollbackTrans();
                        return $this->rFail($this->lang("VSubGoodsMultipleOrder.designated_goods_except"));
                    }
                    $selling_price = 0;
                    if ($tripMultiple['designated_goods'] == 2) {
                        $tmg = $this->getTripMultipleGoodsFind(['tm_id' => $tripMultiple['tm_id'], 'g_id' => $value['g_id']]);
                        if ($tmg['buy_lower'] && $tmg['buy_lower'] > $value['quantity']) {
                            $this->rollbackTrans();
                            return $this->r(100,'商品数量低于可购买下限');
                        }
                        if ($tmg['buy_upper'] && $tmg['buy_upper'] < $value['quantity']) {
                            $this->rollbackTrans();
                            return $this->r(100,'商品数量超过可购买上限');
                        }
                        // 指定商品售价，按涨跌比例计算
                        $selling_price = number_format(bcmul($tmg['sale_amount'],bcadd(1,bcdiv($tmg['rise_fall_ratio'],100,2),2),3),2);
                    }


                    $where = [];
                    if (isset($value['g_id'])) $where['g_id'] = $value['g_id'];
                    if (isset($value['mc_id'])) {
                        $mc = $this->getMachineChannelFind(['mc_id' => $value['mc_id']]);
                        if (!$mc) {
                            $this->rollbackTrans();
                            return $this->r(100, $this->lang("VSubCar.channel_no_data"));
                        }
                        if (!$mc['mg_id']) {
                            $this->rollbackTrans();
                            return $this->r(100, $this->lang("VSubCar.mg_id_require"));
                        }
                        $where['g_id'] = $mc['g_id'];
                    }
                    $goods = $this->getGoodsFind($where, 'g_id,g_name,pic,sku,gc_id,gc_name,g_type,cost_price,market_price,retail_price,bar_code');
                    $cost_price = $mc['cost_price'] ?? $goods['cost_price'];
                    $market_price = $mc['market_price'] ?? $goods['market_price'];
                    $retail_price = $mc['retail_price'] ?? $goods['retail_price'];

                    // 指定全部商品或指定商品除外，按涨跌比例计算
                    if ($tripMultiple['designated_goods'] == 1 || $tripMultiple['designated_goods'] == 3) {
                        $selling_price = number_format(bcmul($retail_price,bcadd(1,bcdiv($tripMultiple['rise_fall_ratio'],100,2),2),3),2);
                    }

                    $sod_price = 0;
                    for ($i = 0; $i < $value['quantity']; $i++) {
                        $quantity = 1;
                        $details = [
                            "order_id" => $order_id,
                            "mc_id" => $mc['mc_id'] ?? 0,
                            "shelf_way" => $mc['shelf_way'] ?? "",
                            "channel_position" => $mc['channel_position'] ?? 0,
                            "channel_code" => $mc['channel_code'] ?? '',
                            "mg_id" => $mc['mg_id'] ?? 0,
                            "g_id" => $goods['g_id'],
                            "g_name" => $goods['g_name'],
                            "g_type" => $goods['g_type'],
                            "pic" => $goods['pic'],
                            "sku" => $goods['sku'],
                            "gc_id" => $goods['gc_id'],
                            "gc_name" => $goods['gc_name'],
                            "cost_price" => $cost_price,
                            "market_price" => $market_price,
                            "retail_price" => $retail_price,
                            "total_sod_price" => bcmul($selling_price, $quantity, 2),
                            "quantity" => $quantity,
                            "bar_code" => $goods['bar_code'],
                        ];
                        $sod_id = $this->addSaleOrdersDetails($details);
                        actionLog($this->getLS(), '添加订单详情SQL');
                        if ($sod_id) {
                            $sod_price = bcadd($sod_price,$details['total_sod_price'],2);
                            $updateOrder['cost_price'] = bcadd($updateOrder['cost_price'], $details['cost_price'], 2);
                            $updateOrder['market_price'] = bcadd($updateOrder['market_price'], $details['market_price'], 2);
                            $updateOrder['retail_price'] = bcadd($updateOrder['retail_price'], $details['retail_price'], 2);
                            $updateOrder['quantity'] = bcadd($updateOrder['quantity'], $quantity);
                            $updateOrder['total_price'] = bcadd($updateOrder['total_price'], $details['total_sod_price'], 2);
                            $updateOrder['total_quantity'] = bcadd($updateOrder['total_quantity'], $quantity);
                        } else {
                            $this->rollbackTrans();
                            return $this->r(100, $this->lang("VSubCar.make_order_details_fail"));
                        }
                    }
                    if ($value['sod_price'] != $sod_price) {
                        actionLog(['计算的商品总价' => $sod_price,'传入的商品总价' => $value['sod_price']],'商品总价不相等');
                        $this->rollbackTrans();
                        return $this->r(100,$this->lang("VSubGoodsMultipleOrder.sod_price_not_eq"));
                    }
                }

                // 订单增加酒店
                $this->data['hotelList'] = json2arr($this->data['hotelList']);
                try {
                    validate(VHotel::class)->scene("hotelList")->check($this->data['hotelList']);
                } catch (\Exception $e) {
                    actionException($e, 1);
                    return $this->rTryCatch($e->getMessage());
                }
                $hotelId = array_column($tripMultiple['hotelList'], 'hotelId');
                // 指定酒店列表，不在范围内
                if ($tripMultiple["designated_hotel"] == 2 && !in_array($this->data['hotelList']['hotelId'], $hotelId)) {
                    $this->rollbackTrans();
                    return $this->rFail($this->lang("VSubGoodsMultipleOrder.designated_hotel_except"));
                }
                // 指定酒店列表除外，在范围内
                if ($tripMultiple["designated_hotel"] == 3 && in_array($this->data['hotelList']['hotelId'], $hotelId)) {
                    $this->rollbackTrans();
                    return $this->rFail($this->lang("VSubGoodsMultipleOrder.designated_hotel_except"));
                }
                $insertHotel = [
                    "order_id" => $this->order['order_id'],
                    "m_id" => $this->order['m_id'],
                    "machine_id" => $this->order['machine_id'],
                    "machine_name" => $this->order['machine_name'],
                    "hotel_trade_no" => "",
                    "hotelId" => $this->data['hotelList']['hotelId'],
                    "hotelFrom" => $this->data['hotelList']['hotelFrom'],
                    "roomId" => $this->data['hotelList']['roomId'],
                    "num" => $this->data['hotelList']['num'],
                    "adults" => $this->data['hotelList']['adults'],
                    "totalPrice" => $this->data['hotelList']['totalPrice'],
                    "mobile" => $this->order['mobile'],
                    "pay_amount" => $this->data['hotelList']['pay_amount'],
                    "checkInDate" => $this->data['hotelList']['checkInDate'],
                    "checkOutDate" => $this->data['hotelList']['checkOutDate'],
                    "guestNames" => $this->data['hotelList']['guestNames'] ?? "",
                ];
                if ($this->data['hotelList']['hotelFrom'] == 1) {
                    if (!isset($this->data['hotelList']['logId']) || !$this->data['hotelList']['logId']) {
                        $this->rollbackTrans();
                        return $this->r(100, $this->lang("VSubGoodsMultipleOrder.logId_required"));
                    }
                    if (!isset($this->data['hotelList']['tripData']) || !$this->data['hotelList']['tripData']) {
                        $this->rollbackTrans();
                        return $this->r(100, $this->lang("VSubGoodsMultipleOrder.tripData_required"));
                    }
                    $insertHotel['logId'] = $this->data['hotelList']['logId'];
                    $insertHotel['tripData'] = $this->data['hotelList']['tripData'];
                }
                $sh_id = $this->addSaleHotel($insertHotel);
                actionLog($this->getLS(), '添加订单酒店SQL');
                if ($sh_id) {
                    $updateOrder['total_quantity'] = bcadd($updateOrder['total_quantity'], 1);
                    $flag[] = 1;
                    foreach ($this->data['hotelList']['roomPriceList'] as $key => $value) {
                        $insert = [
                            "sh_id" => $sh_id,
                            "hotelId" => $this->data['hotelList']['hotelId'],
                            "roomId" => $this->data['hotelList']['roomId'],
                            "effectiveDate" => $value['effectiveDate'],
                            "amount" => $value['amount'],
                        ];
                        $flag[] = $this->addSaleHotelNightly($insert);
                        actionLog($this->getLS(), '添加房间价格SQL');
                    }
                }
                $updateOrder['order_id'] = $this->order['order_id'];
                $updateOrder['has_hotel'] = 1;
                $updateOrder['goods_type'] = $this->data['hotelList']['hotelFrom'] == 1 ? 2 : 3;
                $updateOrder['total_price'] = bcadd($updateOrder['total_price'], $this->data['hotelList']['pay_amount'], 2);
                if ($updateOrder['total_price'] != $this->data['total_price']) {
                    actionLog(['计算的订单总价' => $updateOrder['total_price'],'传入的订单总价' => $this->data['total_price']],'商品总价不相等');
                    $this->rollbackTrans();
                    return $this->r(100,$this->lang("VSubGoodsMultipleOrder.total_price_not_eq"));
                }
//                $data = []
                if ($this->data['hotelList']['hotelFrom'] == 1) {
                    $result = $this->createHotelOrder();
                    if (!$result || !isset($result['code']) || $result['code'] != 0) {
                        $this->rollbackTrans();
                        return $this->r(100, $result['message']);
                    }
//                    $data['paymentUrlLink'] = $result['result']['miniProgramCode'];
                    $updateOrder['out_trade_no'] = $result['result']['tradeNo'];
                }
                $flag[] = $this->updateSaleOrders($updateOrder);
                actionLog($this->getLS(), '修改订单SQL');
                actionLog($flag, '事务结果');
                $check = $this->checkFlag($flag);
                if ($check) {
                    $this->commitTrans();
                    $field = "order_id,trade_no,out_trade_no,mobile,order_type,pay_status,pay_type,pay_method,cost_price,market_price,total_price,total_quantity,has_hotel,goods_type,create_time";
                    $this->order = $this->getSaleOrdersFind(['order_id' => $this->order['order_id']], $field);
                    $detailsField = "sod_id,order_id,mc_id,shelf_way,channel_position,channel_code,mg_id,
                    g_id,g_name,g_type,pic,sku,gc_name,cost_price,market_price,retail_price,discount_price,total_sod_price,quantity,bar_code,batch_number,is_gift";
                    $this->order['details'] = $this->getSaleOrdersDetailsList(['order_id' => $this->order['order_id']], 0, $detailsField);
                    if ($this->order['has_hotel'] == 1) {
                        $hotelField = "sh_id,order_id,hotelId,hotelFrom,roomId,logId,tripData,totalPrice,mobile,num,adults,checkInDate,checkOutDate,guestNames,expectCheckInTime,pay_amount,reservation_status,create_status,create_time";
                        $this->order['hotelList'] = $this->getSaleHotelFind(["order_id" => $this->order['order_id']], $hotelField);
                        if ($this->order['hotelList']) {
                            $this->order['hotelList']['nightList'] = $this->getSaleHotelNightlyList(['sh_id' => $this->order['hotelList']['sh_id']], 0, 'sn_id,sh_id,hotelId,roomId,effectiveDate,amount');
                        }
                    }
//                    $data['order'] = $this->order;
                    return $this->r(200, $this->lang("action_success"), $this->order);
                }
                $this->rollbackTrans();
                return $this->r(100, $this->lang("action_fail"));
            } else {
                $this->rollbackTrans();
                return $this->r(100, $this->lang("VSubCar.make_order_fail"));
            }
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

}