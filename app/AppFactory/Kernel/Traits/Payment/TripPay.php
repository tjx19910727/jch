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

    public function tripPay()
    {
        $func_name = $this->tripPaymentMethod[$this->order['pay_method']];
        return $this->$func_name();
    }

    protected function tripUrlLink()
    {
        $params = [
            "machineId" => $this->order['machine_id'],
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
        if ($mallOrderInfo) $params['mallOrderInfo'] = $mallOrderInfo;

        $hotel = $this->getSaleHotelFind(['order_id' => $this->order['order_id']],'hotelId,roomId,totalPrice,num,checkInDate,checkOutDate,guestNames,expectCheckInTime');
        if ($hotel) {
            $hotel = $hotel->toArray();
            if ($hotel) {
                $nightly = $this->getSaleHotelNightlyList(['hotelId' => $hotel['hotelId']],0,'stayDate,salePrice');
                if ($nightly) {
                    $nightly = $nightly->toArray();
                    $hotel['nightlyPrice'] = $nightly;
                }
                $params['nightlyPrice'] = $hotel;
            }
        }

        $result = Trip::order()->create($params);
        $result = json2arr($result);
        if ($result && isset($result['code']) && $result['code'] == 0){
            return $this->r(200, $this->lang("init_payment_success"), ['paymentUrlLink' => $result['result']['miniProgramCode'], 'order' => $this->order,'result' => $result]);
        }
        return $this->r(100,$this->lang("init_payment_fail"));
    }
}