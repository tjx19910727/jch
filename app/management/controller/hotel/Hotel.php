<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/21
 * Time: 9:38
 */

namespace app\management\controller\hotel;


use app\management\controller\Common;
use app\management\validate\VHotel;

class Hotel extends Common
{
    protected $validatePath = VHotel::class;

    /**
     * 获取携程城市列表
     * @return array|\think\response\Json
     */
    public function getCityList()
    {
        $postData = input();
        $where = $this->getWhere($postData,false,['cityName' => "like"]);
        return $this->app->hotel->getCity($where);
    }

    /**
     * 获取携程酒店列表
     * @return array|\think\response\Json
     */
    public function getHotelList()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.getHotelList');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->hotel->getHotelList($postData);
    }

    /**
     * 获取指定携程酒店的房型列表
     * @return array|\think\response\Json
     */
    public function getRoomList()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.getRoomList');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->hotel->getRoomList($postData);
    }

}