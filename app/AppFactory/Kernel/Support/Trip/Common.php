<?php
/**
 * 携程——丽呈集团——小程序接口
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/2
 * Time: 10:32
 */

namespace app\AppFactory\Kernel\Support\Trip;


class Common
{
    public $baseUrl;
    public $token;
    public $appId;
    public $appSecret;


    public function __construct($config = [])
    {
        $this->baseUrl = env("Trip.baseUrl");
        $this->appId = $config['appId'] ?? env("Trip.appId");
        $this->appSecret = $config['appSecret'] ?? env("Trip.appSecret");
    }

    /**
     * 加签鉴权
     */
    public function getToken()
    {
        $tokenArr = cache("tripToken");
        if ($tokenArr && isset($tokenArr['expire_time']) && $tokenArr['expire_time'] >= time() + 60) {
            $this->token = $tokenArr['token'];
        }
        if (!$tokenArr || (isset($tokenArr['expire_time']) && $tokenArr['expire_time'] <= time() + 60)) {
            $url = $this->baseUrl . "/openservice/getToken?appId=$this->appId&appSecret=$this->appSecret";
//            $url = "http://yantest.dakemakeji.com/machine/test/testNotify";
            $response = $this->curl_request($url,"GET");
            if (is_string($response)) $response = json_decode($response,true);
            if ($response && isset($response['token'])) {
                $this->token = $response['token'];
                $response['expire_time'] = $response['expires'] + time();
                cache("tripToken",$response);
            }
        }
    }

    /**
     * POST请求
     * @param $url
     * @param $params
     * @return array|bool|string
     */
    public function requestPost($url,$params)
    {
        $this->getToken();
        if (!$this->token) return ["code" => 99,"message" => "查无Token"];
        $url = $this->baseUrl . $url;
        $params = json_encode($params,320);
        $header[] = "token:" . $this->token;
        $header[] = "Content-Type: application/json";
        return $this->curl_request($url,"POST",$params,$header);
    }

    /**
     * 请求
     * @param $url
     * @param string $method
     * @param array $data
     * @param bool $header
     * @return bool|string|array
     */
    public function curl_request($url, $method = 'get', $data = array(), $header = false)
    {
        dump($data);
        dump($url);
        dump($header);
        $curl = curl_init($url);
        $method = strtoupper($method);
        //请求方式
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        //post请求
        if ($method == 'POST' && $data){
            curl_setopt($curl,CURLOPT_POST,1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        if ($method == "PUT" && $data) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
//        if ($method == "DELETE" && $data) {
//            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
//        }
        //超时时间
//        curl_setopt($curl, CURLOPT_TIMEOUT, 120);
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
        echo "请求错误：";
        dump($curlError);
        curl_setopt($curl,CURLOPT_VERBOSE,1);
        list($content, $status) = [curl_exec($curl), curl_getinfo($curl), curl_close($curl)];
        dump($content);
        dump($status);
        $content = trim(substr($content, $status['header_size']));
        return  $content;
    }
}