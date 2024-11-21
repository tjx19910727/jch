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
        $signKey = $this->config['key'] ?? "";
        $machine_id = $data['machine_id'] ?? "";
        if (!$machine_id) $machine_id = $this->config['machine_id'] ?? "";
        if (!$signKey) $signKey = cache($machine_id . ".signKey");
        if (!$signKey) $signKey = $this->getMachineValue(['machine_id' => $machine_id],'signKey');
        if (!$signKey) $signKey = env("api.md5Key");
        return SignUtil::makeSign($data,$signKey);
    }

    public function checkSign($data)
    {
        $signKey = $this->config['key'] ?? "";
        $machine_id = $data['machine_id'] ?? ($this->config['machine_id'] ?? "");
        if (!$signKey && $machine_id) $signKey = cache($machine_id . ".signKey");
        if (!$signKey) {
//            $signKey = $this->getMachineFind(['machine_id' => $machine_id],'signKey,signKeyTime');
            // 3600秒内的设备SignKey
            if ( $this->machine['signKey'] && $this->machine['signKeyTime'] < time() - 3600) {
                actionLog(["开始时间" => date("Y-m-d H:i:s",$this->machine['signKeyTime']),$this->machine['signKey']],'SignKey超时');
            }
            $signKey = $this->machine['signKey'];
        }
        if (!$signKey) $signKey = env("api.md5Key");
        $checkSign = SignUtil::checkSign($data,$signKey);
        if ($checkSign == true) {
            // 验签通过，自动续签时间
            $this->updateMachine(['m_id' => $this->machine['m_id'],"signKeyTime" => time()]);
        }
        return $checkSign;
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

    public function int2HourMinuteSec($time,$length = "")
    {
        return Int2HourMinuteSec($time,$length);
    }
}