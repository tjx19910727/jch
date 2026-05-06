<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/18
 * Time: 15:57
 */

namespace app\management\controller\earth;


use app\management\controller\Common;

class Country extends Common
{

    /**
     * 1. 获取全球大区数据
     * @return array|string
     */
    public function getContinentsList()
    {
        return returnData($this->app->earth->getEarthContinentsList([]));
    }

    /**
     * 2. 获取国家数据列表
     * @return array|string
     */
    public function getCountriesList()
    {
        $postData = input();
        $where = $this->getWhere($postData,false,["name" => "like","full_name" => "like","cname" => "like","full_cname" => "like"]);
        $pageNum = $postData['pageNum'] ?? 0;
        $field = "*,(case when a.code='CHN' then 0 else 1 end) chn_first";
        $order = "chn_first asc,a.id asc";
        $data = $this->app->earth->getEarthCountriesList($where,$pageNum,$field,$order);
        return returnData($data);
    }

    /**
     * 3. 获取国家州省数据列表
     * @return array|string
     */
    public function getStatesList()
    {
        $postData = input();
        $where = $this->getWhere($postData,false,["name" => "like","cname" => "like"]);
        $pageNum = $postData['pageNum'] ?? 0;
        return returnData($this->app->earth->getEarthStatesList($where,$pageNum,"*"));
    }

    /**
     * 4. 获取城市信息列表
     * @return array|string
     */
    public function getCitiesList()
    {
        $postData = input();
        $where = $this->getWhere($postData,false,["name" => "like","cname" => "like"]);
        $pageNum = $postData['pageNum'] ?? 0;
        return returnData($this->app->earth->getEarthCitiesList($where,$pageNum,"*"));

    }

    /**
     * 5. 获取县区信息列表
     * @return array|string
     */
    public function getRegionsList()
    {
        $postData = input();
        $where = $this->getWhere($postData,false,["name" => "like","cname" => "like"]);
        $pageNum = $postData['pageNum'] ?? 0;
        return returnState(200,"success",$this->app->earth->getEarthRegionsList($where,$pageNum,"*"));

    }

    /**
     * 6. 获取国内大区表
     * @return array|string
     */
    public function getAreaList()
    {
        $postData = input();
        $where = $this->getWhere($postData,false,["name" => "like","cname" => "like"]);
        $pageNum = $postData['pageNum'] ?? 0;
        return returnData($this->app->earth->getEarthAreaList($where,$pageNum,"*"));
    }

    /**
     * 通过坐标系获取地址
     * @return array|\think\response\Json
     */
    public function getAddressByLatLng()
    {
        $postData = input();
        return $this->app->earth->getAddress($postData);
    }

    /**
     * 坐标系转换为腾讯地图坐标系
     * @return array|\think\response\Json
     */
    public function changeLatLngToTencentMap()
    {
        $postData = input();
        return $this->app->earth->changeLatLngToTencentMap($postData);
    }

    /**
     * 地址获取坐标系
     * @return array|\think\response\Json
     */
    public function getLatLng()
    {
        $postData = input();
        return $this->app->earth->getLatLng($postData);
    }
}