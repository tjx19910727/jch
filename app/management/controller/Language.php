<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2025/5/21
 * Time: 20:16
 */

namespace app\management\controller;


use app\BaseController;

class Language extends BaseController
{
    public function translate()
    {
        $filePaths[] = root_path("app/lang/en") . "app.php";
        $filePaths[] = root_path("app/machine/lang") . "en.php";
        $filePaths[] = root_path("app/mobile/lang") . "en.php";
        $filePaths[] = root_path("app/pay/lang") . "en.php";
        $filePaths[] = root_path("app/wx/lang") . "en.php";
        dump($filePaths);
        foreach ($filePaths as $filePath) {
            $data = include $filePath;
            $this->translateArr($data);
            $content = $data;
            $content = "<?php\n\nreturn " . var_export($content, true);
            $content = str_replace("array (", "[", $content);
            $content = str_replace(")", "]", $content) . ";";
            dump($content);
            file_put_contents($filePath, $content);
        }
    }

    public function translateArr(&$arr)
    {
        foreach ($arr as $key => $value) {
            if (is_array($value)) {
                $this->translateArr($value);
                $arr[$key] = $value;
            } else {
                $translateData = $this->translateStr($value);
                $newResult = $this->handleTranslateResult($translateData);
                foreach ($newResult as $nk => $nw) {
                    if ($nk == $value) {
                        $arr[$key] = $nw;
                    }
                }
                sleep(1);
            }
        }
        return $arr;
    }


    /**
     * 5. 调用百度通用翻译接口，翻译中文，频率Max：1次/秒
     * @param array $strArr 可以字符串或数组
     * @param string $toLang 可以字符串或数组
     * @return array|mixed
     */
    public function translateStr($strArr = [],$toLang = "en")
    {
        $baseUrl = "https://fanyi-api.baidu.com/api/trans/vip/translate";
        $app_id = "20220518001220498";
        $app_key = "IgELJAOQhggjMtLWsixy";
        $data = [
            "appid" => $app_id,
            "q" => is_array($strArr) ? implode(" / ", $strArr) : $strArr,
            "salt" => get_rand_string(6),
        ];
        $sign = make_sign($data, $app_key);
        $data['from'] = "auto";
        $data['to'] = $toLang;
        $data['sign'] = $sign;
        $str = http_build_query($data);
        $url = $baseUrl . "?" . $str;
        $result = translate($url);
        $result = json2arr($result);
        dump($result);
//        die();
        return $result;
    }


    /**
     * 处理翻译结果
     * @param $result
     * @return array
     */
    public function handleTranslateResult($result)
    {
        $tResult = [];
        if (isset($result['trans_result'])) {
            foreach ($result['trans_result'] as $key => $value) {
                $srcArr = explode("/", $value['src']);
                $dstArr = explode("/", $value['dst']);
                foreach ($srcArr as $sk => $sv) {
                    $tResult[$sv] = rtrim(ltrim($dstArr[$sk]));
                }
            }
        }
        return $tResult;
    }
}


/**
 * 生成签名
 */
function make_sign($data, $signKey)
{
    $str = "";
    foreach ($data as $key => $value) {
        $str .= $value;
    }
    $str .= $signKey;
    $sign = md5($str);  // MD5加密并字母转大写生成SIGN
    return $sign;
}


function translate($url) {
    set_time_limit(0);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS,20);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 40);
    curl_setopt($ch, CURLOPT_URL, $url);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}


/**
 * 生成随机数
 * @param int $length
 * @param string $type
 * @return string
 */
function get_rand_string($length=0,$type='chars_num'){
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
