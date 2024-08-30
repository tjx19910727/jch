<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/3
 * Time: 9:06
 */

namespace app\AppFactory\Kernel\Support\Trip;


class Hotel extends Common
{
    /**
     * 获取酒店静态列表
     * @param $params
     * @return array|bool|string
     */
    public function getStaticHotelList($params)
    {
        $url = "/hotel/getStaticHotelList";
        return $this->requestPost($url,$params);
    }

    /**
     * 获取酒店静态物理房型列表-通过酒店ID查询
     * @param $hotelId
     * @return array|bool|string
     */
    public function getHotelStaticRoomType($hotelId)
    {
        $url = "/hotel/getHotelStaticRoomType";
        return $this->requestPost($url,['hotelId' => $hotelId]);
    }

    /**
     * 获取酒店列表
     * @param $params
     * @return array|bool|string
     */
    public function getList($params)
    {
        $url = "/hotel/getHotelList";
        return $this->requestPost($url,$params);
    }

    /**
     * 获取酒店详情列表
     * @param $params
     * @return array|bool|string
     */
    public function getDetailsList($params)
    {
        $url = "/hotel/getHotelDetails";
        return $this->requestPost($url,$params);
    }

    /**
     * 获取酒店房型列表
     * @param $params
     * @return array|bool|string
     */
    public function getRoomList($params)
    {
        $url = "/hotel/getRoomList";
        return $this->requestPost($url,$params);
    }
}