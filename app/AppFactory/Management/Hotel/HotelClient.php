<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/20
 * Time: 14:44
 */

namespace app\AppFactory\Management\Hotel;


use app\AppFactory\Kernel\Support\Trip\Trip;
use app\AppFactory\Kernel\Traits\Trip\TripCityTrait;
use app\AppFactory\Management\ManagementClient;

class HotelClient extends ManagementClient
{
    use TripCityTrait;

    /**
     * 获取携程城市列表
     * @param $postData
     * @return array|\think\response\Json
     */
    public function getCity($postData)
    {
        $where = [];
        if (isset($this->data['cityName'])) $where[] = ['cityName','like',"%" . $postData['cityName'] . "%"];
        $city = $this->getTripCityList($where,$this->data['pageNum'] ?? 0,"tc_id,cityId,cityName");
        return $this->r(200,$this->lang('query_success'), ['city' => $city]);
    }


    /**
     * 根据城市ID获取酒店列表
     * @param $postData
     * @return array|\think\response\Json
     */
    public function getHotelList($postData)
    {
        $params = [
            "pageNo" => $postData['page'],
            "pageSize" => $postData['pageNum'],
        ];
        if (isset($postData['hotelName']))
            $params['hotelName'] = $postData['hotelName'];
        if (isset($postData['cityId']))
            $params["cityId"] = $postData['cityId'];
        $result = Trip::hotel()->getStaticHotelList($params);
        $result = json2arr($result);
        if ($result && isset($result['code']) && $result['code'] == 0) {
            return $this->r(200,$this->lang('query_success'),['list' => $result['result'],'totalCount' => $result['totalCount']]);
        }
        return $this->r(100,$this->lang('query_fail') . isset($result['message']) ? ":" . $result['message'] : "");
    }

    /**
     * 获取酒店房型
     * @param $postData
     * @return array|\think\response\Json
     */
    public function getRoomList($postData)
    {
        $result = Trip::hotel()->getHotelStaticRoomType($postData['hotelId']);
        $result = json2arr($result);
        if ($result && $result['code'] == 0) {
            return $this->r(200,$this->lang('query_success'),$result['result']);
        }
        return $this->r(100,$this->lang('query_fail') . isset($result['message']) ? ":" . $result['message'] : "");
    }
}