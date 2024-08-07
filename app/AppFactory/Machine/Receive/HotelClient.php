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
use app\AppFactory\Kernel\Traits\Trip\TripCityTrait;

class HotelClient extends ReceiveBaseClient
{
    use TripCityTrait;

    public function __construct(ServiceContainer $app)
    {
        parent::__construct($app);
//        $this->dataRecord();
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
            "checkInDate" => $this->data['checkInDate'],
            "checkOutDate" => $this->data['checkOutDate'],
            "pageNo" => $this->data['pageNo'],
            "pageSize" => $this->data['pageSize'],
        ];
        $result = Trip::hotel()->getList($params);
        $result = json2arr($result);
        if ($result && isset($result['code']) && $result['code'] == 0) {
            return $this->r(200,$this->lang('query_success'),$result['result']);
        }
        return $this->r(100,$this->lang('query_fail') . isset($result['message']) ? ":" . $result['message'] : "");
    }

    /**
     * 获取酒店详情
     * @return array|\think\response\Json
     */
    public function getDetailsList()
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
        $result = Trip::hotel()->getRoomList(['hotelId' => $this->data['hotelId'],'checkInDate' => $this->data['checkInDate'],'checkOutDate' => $this->data['checkOutDate']]);
        $result = json2arr($result);
        if ($result && $result['code'] == 0) {
            return $this->r(200,$this->lang('query_success'),$result['result']);
        }
        return $this->r(100,$this->lang('query_fail') . isset($result['message']) ? ":" . $result['message'] : "");
    }
}