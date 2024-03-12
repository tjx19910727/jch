<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/12/13
 * Time: 16:25
 */

namespace app\AppFactory\Kernel\Traits;


use app\AppFactory\Kernel\Util\SignUtil;
use think\facade\Lang;

trait CommonTrait
{

    /**
     * 计算两个坐标点距离，返回浮点型，单位：米
     * @param $start_lat
     * @param $start_lng
     * @param $end_lat
     * @param $end_lng
     * @return float|int
     */
    public function getDistanceByCoordinate($start_lat, $start_lng, $end_lat, $end_lng)
    {
        // 将角度转为狐度 deg2rad() 函数将角度转换为弧度
        $rad_start_lat = deg2rad($start_lat);
        $rad_end_lat = deg2rad($end_lat);
        $rad_start_lng = deg2rad($start_lng);
        $rad_end_lng = deg2rad($end_lng);
        $a_coordinate = $rad_start_lat - $rad_end_lat;
        $b_coordinate = $rad_start_lng - $rad_end_lng;
        $distance = 2 * asin(sqrt(pow(sin($a_coordinate / 2),2) + cos($rad_start_lat) * cos($rad_end_lat)
                * pow(sin($b_coordinate / 2),2))) * 6378.137 * 1000;
        return $distance;
    }

    public function lang($name)
    {
        return Lang::get($name);
    }

    public function makeSign($data)
    {
        return SignUtil::makeSign($data,$this->config['key']);
    }

    public function checkSign($data)
    {

        return SignUtil::checkSign($data,$this->config['key']);
    }

    /**
     * 左侧补零
     * @param $str
     * @param $len
     * @return string
     */
    public function leftHandZero($str,$len)
    {
        return covering($str,$len);
    }
}