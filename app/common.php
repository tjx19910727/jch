<?php
// 应用公共文件

/**
 * 状态检查
 * @param $flag
 * @return int  1|0
 */
function flag_check($flag){
    $check = 1;
    foreach ($flag as $v){
        if(!$v){
            $check = 0;
            break;
        }
    }
    return $check;
}


function returnData($data, $text = "")
{
    $success = "success";
    $fail = "fail";
    $text = explode("|", $text);
    if ($text && count($text) == 2) {
        $success = $text[0];
        $fail = $text[1];
    }
    $data = obj2arr($data);
    if ($data) return returnState(200, $success, $data);
    return returnState(100, $fail);
}

/**
 * 返回验证器数据格式
 * @param $check
 * @return array|string
 */
function returnValidate($check)
{
    return returnState(300, $check);
}

/**
 * 返回TryCatch数据格式
 * @param $check
 * @return array|string
 */
function returnTryCatch($msg)
{
    return returnState(3301, $msg);
}

/**
 * 返回数据格式
 * @param $state
 * @param string $msg
 * @param array $data
 * @param bool $isJson
 * @return array|string
 */
function returnState($state, $msg = "", $data = [], $isJson = true)
{
    $return = ["state" => $state, "msg" => $msg];
    if ($data) $return['data'] = $data;
    if ($isJson) {
        $return = json($return);
    }
    return $return;
}


/**
 * emoji表情转换字符
 * @param $str
 * @return string
 */
function emoji2str($str){
    $strEncode = '';
    $length = mb_strlen($str,'utf-8');
    for ($i=0; $i < $length; $i++) {
        $_tmpStr = mb_substr($str,$i,1,'utf-8');
        if(strlen($_tmpStr) >= 4){
            $strEncode .= '[[EMOJI:'.rawurlencode($_tmpStr);
        }else{
            $strEncode .= $_tmpStr;
        }
    }
    return $strEncode;
}

/**
 * 字符转换成emoji表情
 * @param $str
 * @return string
 */
function str2emoji($str){
    $str_arr = explode("[[EMOJI:", $str);
    $new_str = "";
    foreach ($str_arr as $key => $val){
        $new_str .= rawurldecode($val);
    }
    return $new_str;
}

/**
 * 对象转数组
 * @param $obj
 * @return mixed
 */
function obj2arr($obj)
{
    if (is_object($obj)) {
        if (method_exists($obj,"getData")) {
            $obj = $obj->getData();
        } else {
            $obj = json_decode(json_encode($obj), true);
        }
    }
    return $obj;
}


/**
 * json转数组
 * @param $json
 * @return mixed
 */
function json2obj($json)
{
    if (is_string($json)) {
        return json_decode(json_encode($json));
    }
    return $json;
}

/**
 * 数组转JSON
 * @param $arr
 * @return string
 */
function arr2json($arr)
{
    if (is_array($arr)) {
        return json($arr);
    }
    return $arr;
}

/**
 * JSON转数组
 * @param $arr
 * @return array
 */
function json2arr($json)
{
    if (is_string($json)) {
        return json_decode($json, true);
    }
    return $json;
}

/**
 * 将xml转为array
 * @param $xml
 * @return mixed|string
 */
function xml2arr($xml, $isFile = false)
{
    if (!$xml) {
        return "xml数据异常！";
    }
    //将XML转为array
    //禁止引用外部xml实体
    libxml_disable_entity_loader(true);
    if ($isFile) {
        if (!file_exists($xml)) return false;
        $xmlStr = file_get_contents($xml);
    } else {
        $xmlStr = $xml;
    }
    $values = json_decode(json_encode(simplexml_load_string($xmlStr, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    return $values;
}

/**
 * array转xml
 * @param $arr
 * @return string
 */
function arr2xml($arr)
{
    $xml = "<xml>";
    foreach ($arr as $key => $value) {
        if (is_array($value)) {
            $xml .= "<" . $key . ">" . arr2xml($value) . "</" . $key . ">";
        } else {
            $xml .= "<" . $key . ">" . $value . "</" . $key . ">";
        }
    }
    $xml .= "</xml>";
    return $xml;
}

/**
 * 小时分钟转秒数
 * @param $time
 * @return float|int
 */
function HourMinuteSec2int($time) {
    $timeInt = 0;
    $time = explode(":",$time);
    if (isset($time[0]) && $time[0]) { // 小时
        $timeInt += intval($time[0]) * 3600;
    }
    if (isset($time[1]) && $time[1]) { // 分钟
        $timeInt += intval($time[1]) * 60;
    }
    if (isset($time[2]) && $time[2]) { // 秒数
        $timeInt += intval($time[2]);
    }
    return $timeInt;
}

/**
 * 秒数转小时:分钟:秒
 * @param int $int
 * @param int $length
 * @return string
 */
function Int2HourMinuteSec($int,$length = '') {
    $minuteInt = $int % 3600;
    $hours = ($int - $minuteInt) / 3600;
    if ($length == 1) return $hours;
    $minute = intval($minuteInt / 60);
    if ($length == 2) return covering($hours) . ":" . covering($minute);
    $second = $int % 60;
    return covering($hours) . ":" . covering($minute) . ":" . covering($second);
}

/**
 * 左侧补零，长度2
 * @param string $str
 * @param int $length
 * @return string
 */
function covering($str,$length = 2){
    if(strlen($str) < $length){
        $str = str_pad($str,$length,"0",STR_PAD_LEFT);
    }
    return $str;
}


/**
 * 记录程序异常信息
 * @param Exception $e
 * @param int $trace
 */
function actionException($e,$trace = 0)
{
    actionLog($e->getFile() . "_" . $e->getLine() . "_" . $e->getMessage(),'tryCatchMessage');
    if ($trace) {
        actionLog($e->getTrace(), 'tryCatchTrace');
    }
}

/**
 * 方法日志
 */
function actionLog($data,$remark = '',$logName = "")
{
    $controller = request()->controller();
    $logName ? $action = $logName : $action = request()->action();
    if (is_object($data)) $data = json_decode(json_encode($data),true);
    if(is_array($data)) $data = json_encode($data,JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES);
    if($remark) $data = $remark . ":".$data;
    $day = date("Ymd");
    $folderPath = root_path() . "runtime/log/" . $day;
    if (!is_dir($folderPath)) {
        @mkdir($folderPath);
        @chmod($folderPath,0777);
    }

    $filePath = $folderPath . "/" . $controller . "_" . $action;
    $newPath = $filePath . '_' . date('His') ;
    $type = '.log';
    $max = \think\facade\Config::get('app.log_max_size') ?? 1048576;
    if (file_exists($filePath.$type)) {
        $fileSize = abs(filesize($filePath.$type));
        if ($fileSize > $max) {
            rename($filePath . $type,$newPath . $type);
        }
    }
    @chmod($filePath . $type,0755);
    file_put_contents($filePath . $type, '[' . date('Y-m-d H:i:s', time()) . ']' . $data . "\r\n", FILE_APPEND);
}

/**
 * 多维数组去重复
 * @param $array
 * @param $key
 * @return array
 */
function super_unique($array,$key)
{
    $temp = [];
    foreach ($array as $v) {
        if (!isset($temp[$v[$key]]))
            $temp[$v[$key]] = $v;
    }
    $array = array_values($temp);
    return $array;
}

/**
 * 检查访问频率
 * @param $session
 * @param int $second
 * @return array|bool|string
 */
function checkFrequency($session, $second = 2)
{
    $time = \think\facade\Session::get($session);
    if ($time && time() - intval($time) < $second) {
        return "请间隔".intval($second - (time() - intval($time)))."秒后再操作";
    }
    $time = time();
    \think\facade\Session::set($session,$time);
    return true;
}


/**
 * 判断一个字符串是否为时间格式
 * @param $date
 * @param string $format
 * @return bool
 */
function validateDate($date, $format = 'Y-m-d H:i:s')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
}