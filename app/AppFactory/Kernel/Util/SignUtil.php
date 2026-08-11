<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/23
 * Time: 9:06
 */

namespace app\AppFactory\Kernel\Util;


class SignUtil
{
    /**
     * 生成签名
     * @param $data
     * @param $key
     * @return string
     */
    public static function makeSign($data,$key,$jsonOptions = 0)
    {
        ksort($data);
        $signArr = [];
        foreach ($data as $k => $value) {
            if (is_array($value)) $value = json_encode($value, $jsonOptions);
            $signArr[] = $k . "=" . $value;
        }
        $signArr[] = "key=" . $key;
        $signStr = implode("&",$signArr);
        actionLog($signStr,'生成签名字符串');
        return strtolower(md5($signStr));
    }

    /**
     * 校验签名
     * @param $data
     * @param $key
     * @return bool
     */
    public static function checkSign($data,$key,$jsonOptions = 0)
    {
        actionLog($data,$key);
        $sign = $data['sign'];
        unset($data['sign']);
        $makeSign = self::makeSign($data,$key,$jsonOptions);
        if ($makeSign == $sign) return true;
        return false;
    }
}
