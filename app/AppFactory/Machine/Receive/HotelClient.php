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
use app\AppFactory\Kernel\Traits\Payment\TripPay;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleHotelNightlyTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleHotelTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersTrait;
use app\AppFactory\Kernel\Traits\Trip\TripCityTrait;
use app\machine\validate\VHotel;

class HotelClient extends ReceiveBaseClient
{
    use TripCityTrait,TripPay;
    use SaleOrdersTrait,
        SaleHotelTrait,SaleHotelNightlyTrait;

    protected $order;


    public function __construct(ServiceContainer $app)
    {
        parent::__construct($app);
        $this->dataRecord();
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
     * 获取携程城市列表
     * @return array|\think\response\Json
     */
    public function getCity()
    {
        $where = [];
        if (isset($this->data['cityName'])) $where[] = ['cityName','like',"%" . $this->data['cityName'] . "%"];
        $city = $this->getTripCityList($where,$this->data['pageNum'] ?? 0,"cityId,cityName");
        return $this->r(200,$this->lang('query_success'), ['city' => $city]);
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
            return $this->r(200,$this->lang('query_success'),['list' => $result['result'],'totalCount' => $result['totalCount']]);
        }
        return $this->r(100,$this->lang('query_fail') . isset($result['message']) ? ":" . $result['message'] : "");
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
            return $this->r(200,$this->lang('query_success'),$result['result']);
        }
        return $this->r(100,$this->lang('query_fail') . isset($result['message']) ? ":" . $result['message'] : "");
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
            return $this->r(200,$this->lang('query_success'),['result' => $result['result'],'logId' => $result['logId']]);
        }
        return $this->r(100,$this->lang('query_fail') . isset($result['message']) ? ":" . $result['message'] : "");
    }

    /**
     * 验证房间是否可订
     * @return array|\think\response\Json
     */
    public function availableCheck()
    {
        $params = [
            "machineId" => $this->data['machine_id'],
            "hotelId" => $this->data['hotelId'],
            "roomId" => $this->data['roomId'],
            "count" => $this->data['count'],
            "quantity" => $this->data['quantity'],
            "checkInDate" => $this->data['checkInDate'],
            "checkOutDate" => $this->data['checkOutDate'],
            "logId" => $this->data['logId'],
            "tripData" => $this->data['tripData'],
            "nightlyPrice" => $this->data['nightlyPrice'],
        ];
        $result = Trip::order()->availableCheck($params);
        $result = json2arr($result);
        actionLog($result,'验证可订房型结果');
        if (isset($result['code']) && $result['code'] == 0) {
            return $this->r(200,$this->lang("query_success"),$result['result']);
        }
        return $this->r(100,$this->lang("query_fail") . "：" . $result['message'] ?? "");
    }

    /**
     * 订单增加酒店信息
     * @return array|bool|string|\think\response\Json
     */
    public function subHotel()
    {
        $updateOrder = [];
        $this->order = $this->getSaleOrdersFind(['order_id' => $this->data['order_id']]);
        try {
            validate(VHotel::class)->scene("hotel")->check($this->data['hotelList']);
        } catch (\Exception $e) {
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
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
            "totalPrice" => bcmul($this->data['hotelList']['totalPrice'], 100),
            "mobile" => $this->order['mobile'],
            "pay_amount" => bcmul($this->data['hotelList']['pay_amount'], 100),
            "checkInDate" => $this->data['hotelList']['checkInDate'],
            "checkOutDate" => $this->data['hotelList']['checkOutDate'],
            "guestNames" => $this->data['hotelList']['guestNames'] ?? "",
        ];
        if ($this->data['hotelList']['hotelFrom'] == 1) {
            if (!isset($this->data['hotelList']['logId']) || !$this->data['hotelList']['logId']) {
                return $this->r(100,"logId不能为空");
            }
            if (!isset($this->data['hotelList']['tripData']) || !$this->data['hotelList']['tripData']) {
                return $this->r(100,'tripData不能为空');
            }
            $insertHotel['logId'] = $this->data['hotelList']['logId'];
            $insertHotel['tripData'] = $this->data['hotelList']['tripData'];
        }
        $this->startTrans();
        try {
            $sh_id = $this->addSaleHotel($insertHotel);
            if ($sh_id) {
                $flag[] = 1;
                if (isset($this->data['hotelList']['roomPriceList'])) {
                    foreach ($this->data['hotelList']['roomPriceList'] as $key => $value) {
                        $insert = [
                            "sh_id" => $sh_id,
                            "hotelId" => $this->data['hotelList']['hotelId'],
                            "roomId" => $this->data['hotelList']['roomId'],
                            "effectiveDate" => $value['effectiveDate'],
                            "amount" => $value['amount'],
                        ];
                        $flag[] = $this->addSaleHotelNightly($insert);
                    }
                }
            }
            $updateOrder['order_id'] = $this->order['order_id'];
            $updateOrder['has_hotel'] = 1;
            $updateOrder['goods_type'] = $this->data['hotelList']['hotelFrom'] == 1 ? 2 : 3;
            $updateOrder['total_price'] = bcadd($this->order['total_price'], $this->data['hotelList']['pay_amount'], 2);
            $flag[] = $this->updateSaleOrders($updateOrder);
            if ($this->data['hotelList']['hotelFrom'] == 1) {
                $result = $this->createHotelOrder();
                if ($result && isset($result['code']) && $result['code'] == 0){
                    $this->updateSaleOrders(['order_id' => $this->order['order_id'],'out_trade_no' => $result['result']['tradeNo']]);
                    $this->order['out_trade_no'] = $result['result']['tradeNo'];
                    $flag[] = 1;
                } else {
                    $this->rollbackTrans();
                    return $this->r(100,$this->lang(""));
                }
            }
            $result = $this->checkFlag($flag);
            return $this->checkTrans($result);
        } catch (\Exception $e) {
            actionException($e,1);
            $this->rollbackTrans();
            return $this->rTryCatch($e->getMessage());
        }
    }

}