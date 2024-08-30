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
        $this->order['mobile'] = $this->data['mobile'] ?? "";
        $result = $this->createHotelOrder();
        if ($result && isset($result['code']) && $result['code'] == 0){
            $this->updateSaleOrders(['order_id' => $this->order['order_id'],'out_trade_no' => $result['result']['tradeNo']]);
            $this->order['out_trade_no'] = $result['result']['tradeNo'];
            return $this->r(200, $this->lang("init_payment_success"), ['paymentUrlLink' => $result['result']['miniProgramCode'], 'order' => $this->order,'result' => $result]);
        }
        return $this->r(100,$this->lang("init_payment_fail"));
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
            foreach ($this->order['details'] as $key => $value) {
                $productInfo = [
                    "productId" => $value['g_id'],
                    "productName" => $value['g_name'],
                    "num" => $value['quantity'],
                    "originalPrice" => bcmul(bcmul($value['retail_price'], $value['quantity'], 3), 100),
                    "salePrice" => bcmul($value['total_sod_price'], 100),
                ];
                $mallOrderInfo[] = $productInfo;
            }
        }
        if ($mallOrderInfo) $params['mallOrderInfoList'] = $mallOrderInfo;
        $hotel = $this->getSaleHotelFind(['order_id' => $this->order['order_id']],'hotelId,roomId,totalPrice,num,adults,checkInDate,checkOutDate,guestNames,expectCheckInTime,logId,tripData');
        if ($hotel) {
            $hotel = $hotel->toArray();
            if ($hotel) {
                $nightly = $this->getSaleHotelNightlyList(['hotelId' => $hotel['hotelId']],0,'effectiveDate stayDate,amount salePrice');
                if ($nightly) {
                    $nightly = $nightly->toArray();
                    $hotel['nightlyPrice'] = $nightly;
                }
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