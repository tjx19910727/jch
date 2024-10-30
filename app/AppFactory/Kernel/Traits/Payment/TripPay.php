<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/3
 * Time: 9:25
 */

namespace app\AppFactory\Kernel\Traits\Payment;

// 丽呈小程序支付
use app\AppFactory\Kernel\Support\Trip\Trip;

trait TripPay
{
    protected $tripPaymentMethod = [
        "1" => "tripUrlLink",
    ];

    /**
     * 会员支付
     * @return mixed
     */
    public function tripPay()
    {
        $func_name = $this->tripPaymentMethod[$this->order['pay_method']];
        return $this->$func_name();
    }

    /**
     * 获取丽呈小程序支付二维码数据
     * @return mixed
     */
    protected function tripUrlLink()
    {
        if (!$this->order['mobile']) $this->order['mobile'] = $this->data['mobile'] ?? "";
        $result = $this->createHotelOrder();
        if ($result && isset($result['code']) && $result['code'] == 0){
            $this->updateSaleOrders(['order_id' => $this->order['order_id'],'out_trade_no' => $result['result']['tradeNo']]);
            $this->order = $this->getSaleOrdersFind(['order_id' => $this->order['order_id']]);
            $this->order['details'] = $this->getSaleOrdersDetailsList(['order_id' => $this->order['order_id']]);
            if ($this->order['has_hotel'] == 1) {
                $this->order['hotelList'] = $this->getSaleHotelFind(["order_id" => $this->order['order_id']]);
                if ($this->order['hotelList']) {
                    $this->order['hotelList']['nightList'] = $this->getSaleHotelNightlyList(['sh_id' => $this->order['hotelList']['sh_id']]);
                }
            }
            $this->returnData['order'] = $this->order;
            $qrCodeLink = "";
            if (isset($result['result']['miniProgramCode']) && $result['result']['miniProgramCode']) $qrCodeLink = $result['result']['miniProgramCode'];
            $this->returnData['qrCodeLink'] = $qrCodeLink;
            $this->returnData['result'] = $result;
            return $this->r(200, $this->lang("init_payment_success"),$this->returnData);
        }
        return $this->r(100,$this->lang("init_payment_fail") . "：" . $result['message'] ?? "",$result);
    }

    /**
     * 向丽呈小程序下单商品与酒店信息
     * @return array|bool|string
     */
    public function createHotelOrder()
    {
        $params = [
            "machineId" => $this->order['machine_id'],
            "machineOrderNo" => $this->order['trade_no'],
            "mobile" => $this->order['mobile'],
        ];
        $mallOrderInfo = [];
        $this->order['details'] = $this->getSaleOrdersDetailsList(['order_id' => $this->order['order_id']]);
        if ($this->order['details']) {
            $this->order['details'] = $this->order['details']->toArray();
            $d = [];
            foreach ($this->order['details'] as $key => $value) {
                if (isset($d[$value['g_id']])) {
                    $d[$value['g_id']]['quantity']++;
                    $d[$value['g_id']]['total_sod_price'] += $value['total_sod_price'];
                    continue;
                }
                $d[$value['g_id']] = $value;
            }
            foreach ($d as $key => $value) {
                $productInfo = [
                    "productId" => $value['g_id'],
                    "productName" => $value['g_name'],
                    "num" => $value['quantity'],
                    "originalPrice" => intval(bcmul(bcmul($value['retail_price'], $value['quantity'], 3), 100)),
                    "salePrice" => intval(bcmul($value['total_sod_price'], 100)),
                ];
                $mallOrderInfo[] = $productInfo;
            }
        }
        if ($mallOrderInfo) $params['mallOrderInfoList'] = $mallOrderInfo;
        $field = "sh_id,hotelId,roomId,totalPrice originalPrice,pay_amount totalPrice,num,adults,checkInDate,checkOutDate,guestNames,expectCheckInTime,logId,tripData";
//        $field = "sh_id,hotelId,roomId,totalPrice,num,adults,checkInDate,checkOutDate,guestNames,expectCheckInTime,logId,tripData";
        $hotel = $this->getSaleHotelFind(['order_id' => $this->order['order_id']],$field);
        if ($hotel) {
            $hotel = $hotel->toArray();
            if ($hotel) {
                $hotel['guestNames'] = $this->order['mobile'] ?? "张三";
                $hotel['expectCheckInTime'] = $hotel['checkInDate'] . " 18:00";
                $nightly = $this->getSaleHotelNightlyList(['sh_id' => $hotel['sh_id']],0,'effectiveDate stayDate,amount salePrice');
                unset($hotel['sh_id']);
                actionLog($nightly,'每晚价格数据');
                if ($nightly) {
                    $nightly = $nightly->toArray();
                    $hotel['nightlyPrice'] = $nightly;
                }
                actionLog($hotel,'下单酒店数据');
                $params['roomOrderInfo'] = $hotel;
            }
        }
        actionLog($params,"请求下单会员支付参数");
        $result = Trip::order()->create($params);
        $result = json2arr($result);
        actionLog($result,'请求结果');
        return $result;
    }
}