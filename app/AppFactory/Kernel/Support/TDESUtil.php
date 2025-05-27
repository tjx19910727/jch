<?php

namespace app\AppFactory\Kernel\Support;


class TDESUtil
{
	
	// 加密算法
	public static function encrypt($str, $key) {
//		echo "加密前数据=".$str."<br/>";
		$key = base64_decode($key);
		$pad = 8 - (strlen($str) % 8);
		$str = $str . str_repeat(chr($pad), $pad);
		
		if (strlen($str) % 8) {
			$str = str_pad($str, strlen($str) + 8 - strlen($str) % 8, "\0");
		}
		$str = openssl_encrypt($str, 'DES-EDE3', $key, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING, '');
//		echo "加密后数据=".bin2hex($str)."<br/>";
		return bin2hex($str);
	}
	
    //解密方法
    public static function decrypt($str, $key)
    {
        $key = base64_decode($key);
        $str = pack("H*", $str);
        $str = openssl_decrypt($str, 'DES-EDE3', $key, OPENSSL_RAW_DATA, '');
    //	echo "解密后数据=".$str."<br/>";
        return $str;
    }

}
?>