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

use ZipArchive;

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


    /**
     * 打包文件
     * @param $file_name * Zip文件全路径
     * @param $file_list * 打包的文件路径列表 path文件全路径，压缩包内显示的文件名
     */
    public function makeZip($file_name, $file_list)
    {
        if (file_exists($file_name)){
            unlink($file_name);
        }

        $zip = new ZipArchive();
        if ($zip->open($file_name,ZIPARCHIVE::CREATE) !== true){
            exit("无法打开文件，或者文件创建失败");
        }
        foreach ($file_list as $val){
            if (file_exists($val['path'])){
                $zip->addFile($val['path']);
                $zip->renameName($val['path'],$val['name']);
            }
        }
        $zip->close();
        if (!file_exists($file_name)){
            exit("无法找到文件");
        }
    }

    /**
     * 下载文件  下载内容为文件夹路径 + 文件名
     * @param $file * 文件夹路径
     * @param $file_name * 文件名
     */
    public function download($file,$file_name){
        if (file_exists($file)){
            header("Content-Description: File Transfer");
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename='.$file_name);
            header('Content-Transfer-Encoding: binary');
            header("Expires: 0");
            header("Cache-Control: must-revalidate");
            header("Pragma: public");
            header("Content-Length: ". filesize($file.$file_name));
            ob_clean();
            flush();
            readfile($file.$file_name);
            exit;
        }
    }

    /**
     * 获取随机字符串
     * @param int $length
     * @param string $type
     * @return string
     */
    public function get_rand_string($length=0,$type='chars_num')
    {
        $num = '0123456789';
        $upperChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowerChars = 'abcdefghijklmnopqrstuvwxyz';
        $hex = 'ABCDEF';
        $string = '';
        $key = '';
        switch($type){
            case 'all':
                $string = $num.$upperChars.$lowerChars.$hex;
                break;
            case 'chars':
                $string = $upperChars.$lowerChars;
                break;
            case 'upper_chars':
                $string = $upperChars;
                break;
            case 'lower_chars':
                $string = $lowerChars;
                break;
            case 'chars_num':
                $string = $num.$upperChars.$lowerChars;
                break;
            case 'lower_num':
                $string = $num.$lowerChars;
                break;
            case 'upper_num':
                $string = $num.$upperChars;
                break;
            case 'num':
                $string = $num;
                break;
            case 'hex':
                $string = $num.$hex;
                break;
        }
        for($i=0;$i<$length;$i++){
            $key .= $string[mt_rand(0,strlen($string)-1)];
        }
        return $key;
    }
}