<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/8/6
 * Time: 8:50
 */

namespace InConnect\Kernel\Traits;


const MAXIMUM_CLOCK_OFFSET = 300;
trait CurlTrait
{
    protected static $header;
    protected $url = 'https://openapi.duolabao.com';

    /**
     * 生成签名，返回签名
     * @param $url
     * @param $method
     * @param $params
     * @return string
     */
    protected function signer($url,$method,$params)
    {
        $signArr = [
            'secretKey'  => $this->config['secretKey'],
            'timestamp' => time(),
            'path' => $url,
        ];
        if (strtoupper($method) == "POST") {
            if (is_array($params)) $params = json_encode($params,JSON_UNESCAPED_UNICODE);
            $signArr['body'] = $params;
        }
        foreach ($signArr as $key => $value) {
            $tempArr[] = $key . '=' . $value;
        }

        $signStr = join('&', $tempArr);
        return strtoupper(sha1($signStr));
    }

    /**
     * 验证签名，返回true/false
     * @param $message
     * @return bool
     */
    protected function validate($message)
    {
        if (is_array($message['body'])) $message['body'] = json_encode($message['body'],JSON_UNESCAPED_UNICODE);
        $signArr = [
            'secretKey' => $this->config['secretKey'],
            'timestamp' => $message['header']['timestamp'],
            'body' => $message['body'],
        ];
        foreach ($signArr as $key => $value) {
            $tempArr[] = $key . '=' . $value;
        }

        $signStr = join('&', $tempArr);
        actionLog(strtoupper(sha1($signStr)),'系统计算签名sha1Sign');
        actionLog($message['header']['token'],'接收的签名');
        return strtoupper(sha1($signStr)) == $message['header']['token'];
    }

    /**
     * 请求
     * @param $url
     * @param string $method
     * @param array $data
     * @param bool $header
     * @param int $timeout
     * @return bool|string
     */
    public function curl_request($url, $method = 'get', $data = array(), $header = false, $timeout = MAXIMUM_CLOCK_OFFSET)
    {
        $url = $this->url . $url;
        $curl = curl_init($url);
        $method = strtoupper($method);
        //请求方式
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        //post请求
        if ($method == 'POST'){
            curl_setopt($curl,CURLOPT_POST,1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data,JSON_UNESCAPED_UNICODE));
        }
        //超时时间
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        //设置header头
        if ($header !== false) curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        //返回抓取数据
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        //输出header头信息
        curl_setopt($curl, CURLOPT_HEADER, true);
        //TRUE 时追踪句柄的请求字符串，从 PHP 5.1.3 开始可用。这个很关键，就是允许你查看请求header
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        //https请求
        if (1 == strpos("$" . $url, "https://")) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        $curlError = curl_error($curl);

        list($content, $status) = [curl_exec($curl), curl_getinfo($curl), curl_close($curl)];
//        dump($content);
//        dump($status);
        self::$header = explode("\r\n",substr($content, 0, $status['header_size'])); // 根据头大小获取头信息
        $this->headerToArr();
        $content = trim(substr($content, $status['header_size']));
        $content = json_decode($content,true);
        return  $content;
    }

    protected function headerToArr()
    {
        $temp = [];
        foreach (self::$header as $key => $value) {
            if (strpos($value,': ') === false && $value) {
                $temp[] = $value;
            }
            if (strpos($value,':') !== false) {
                $arr = explode(': ',$value);
                $temp[$arr[0]] = $arr[1];
            }
        }
        self::$header = $temp;
    }

}
