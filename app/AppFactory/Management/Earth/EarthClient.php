<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/18
 * Time: 15:43
 */

namespace app\AppFactory\Management\Earth;


use app\AppFactory\Kernel\ServiceContainer;
use app\AppFactory\Kernel\Traits\Earth\EarthAreaTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthCitiesTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthContinentsTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthCountriesTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthRegionsTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthStatesTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthTimezoneTrait;
use app\AppFactory\Management\ManagementClient;

class EarthClient extends ManagementClient
{
    use EarthAreaTrait,EarthCitiesTrait,EarthContinentsTrait,EarthCountriesTrait,EarthRegionsTrait,EarthStatesTrait,EarthTimezoneTrait;

    protected $key;

    public function __construct(ServiceContainer $app)
    {
        parent::__construct($app);
        $this->key = env("mapQQKey.key");
    }

    /**
     * 坐标系转换地址：https://lbs.qq.com/service/webService/webServiceGuide/address/Gcoder
     * @param $postData
     *      string       location           经纬度（GCJ02坐标系），格式：location=lat<纬度>,lng<经度>
     * @return array|\think\response\Json
     */
    public function getAddress($postData)
    {
        $postData['key'] = $this->key;
        $params = http_build_query($postData);
        $url = "https://apis.map.qq.com/ws/geocoder/v1/?" . $params;
        $result = $this->curl_request($url);
        if (is_string($result)) $result = json_decode($result,true);
        return $this->r(200,$this->lang("query_success"),$result);
    }

    /**
     * 转换坐标系至腾讯地图坐标系：https://lbs.qq.com/service/webService/webServiceGuide/webServiceTranslate
     * @param $postData
     *      string        locations
     *                              预转换的坐标，支持批量转换，
                                    格式：纬度前，经度后，纬度和经度之间用",“分隔，每组坐标之间使用”;"分隔；
                                    locations参数字符符总长度不可超过2048个，经度和纬度小数点后不可超过16位
     * @return array|\think\response\Json
     */
    public function changeLatLngToTencentMap($postData)
    {
        $postData['key'] = $this->key;
        $params = http_build_query($postData);
        $url = "https://apis.map.qq.com/ws/coord/v1/translate?" . $params;
        $result = $this->curl_request($url);
        if (is_string($result)) $result = json_decode($result,true);
        return $this->r(200,$this->lang("query_success"),$result);
    }

    /**
     * 地址转坐标系：https://lbs.qq.com/service/webService/webServiceGuide/address/Geocoder
     * @param $postData
     *      string    address
     *                          要解析获取坐标及相关信息的 输入地址，参数要求：
                                1. 为提升解析准确率，地址中请至少包含城市名称，否则将视为参数错误，同时地址请尽量完整、具体（包括省市区乡镇/街道门牌及详细地点信息）
                                2. 需要对地址进行URL编码，否则若包含"#"等一些功能字符将引起错误
     * @return array|\think\response\Json
     */
    public function getLatLng($postData)
    {
        $postData['key'] = $this->key;
        $params = urlencode(http_build_query($postData));
        $url = "https://apis.map.qq.com/ws/geocoder/v1/?" . $params;
        $result = $this->curl_request($url);
        if (is_string($result)) $result = json_decode($result,true);
        return $this->r(200,$this->lang("query_success"),$result);
    }
}