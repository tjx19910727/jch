<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/8/6
 * Time: 8:50
 */

namespace WeChatPayV3\Kernel\Traits;


use WeChatPayV3\Kernel\Support\Formatter;
use WeChatPayV3\Kernel\Support\Rsa;

const AUTHORIZATION_TYPE = 'WECHATPAY2-SHA256-RSA2048';

const MAXIMUM_CLOCK_OFFSET = 300;

const WechatpayNonce = 'Wechatpay-Nonce';
const WechatpaySerial = 'Wechatpay-Serial';
const WechatpaySignature = 'Wechatpay-Signature';
const WechatpayTimestamp = 'Wechatpay-Timestamp';
const WechatpayStatementSha1 = 'Wechatpay-Statement-Sha1';

trait CurlTrait
{
    protected static $header;
    protected $defaultHeader = [
        'Accept:application/json, text/plain, application/x-gzip, application/pdf, image/png, image/*;q=0.5',
        'Content-Type:application/json; charset=utf-8',
    ];
    /**
     * @var array<string, string|array<string, string>> - The defaults configuration whose pased in `GuzzleHttp\TransferClient`.
     */
    protected static $defaults = [
        'base_uri' => 'https://api.mch.weixin.qq.com',
        'headers' => [],
    ];
    
    protected function getPrivateKey(){
        return openssl_get_privatekey(file_get_contents($this->config['privateKey']));
    }

    /**
     * 生成签名，返回签名头部
     * @param $url
     * @param $method
     * @param $params
     */
    protected function signer($url,$method,$params)
    {
        self::$defaults['headers'] = $this->defaultHeader;
        $nonce = Formatter::nonce();
        $timestamp = (string) Formatter::timestamp();
//        $signArr = [strtoupper($method), $url, $timestamp, $nonce,json_encode($params,JSON_UNESCAPED_UNICODE)];
        $message = Formatter::request(strtoupper($method), $url, $timestamp, $nonce,$params ? json_encode($params,JSON_UNESCAPED_UNICODE) : '');

        $signature = Rsa::sign($message, $this->getPrivateKey());
        $auth = [
            'mchid="' . $this->config['mchid'].'"',
            'nonce_str="' . $nonce.'"',
            'signature="'. $signature.'"',
            'timestamp="' . $timestamp.'"',
            'serial_no="' . $this->config['serial'].'"',
        ];
        $authorization = AUTHORIZATION_TYPE . ' ' . implode(",",$auth);
        self::$defaults['headers'][] = 'Authorization:' . $authorization;
        self::$defaults['headers'][] = 'User-Agent:' . $_SERVER['HTTP_USER_AGENT'];
    }


//
//    protected function validate($method,$url,$params)
//    {
//        $serialNo = $this->getHeader(WechatpaySerial);
//        $sign = $this->getHeader( WechatpaySignature);
//        $timestamp = $this->getHeader( WechatpayTimestamp);
//        $nonce = $this->getHeader( WechatpayNonce);
//
//        if (!isset($serialNo, $sign, $timestamp, $nonce)) {
//            return false;
//        }
//        if (!$this->checkTimestamp($timestamp)) {
//            return false;
//        }
//        $message = Formatter::request(strtoupper($method), $url, $timestamp, $nonce,$params ? json_encode($params,JSON_UNESCAPED_UNICODE) : '');
//        dump($message);
//        return $this->verify($message, $sign);
//    }
//
//    protected function verify($message,$sign)
//    {
//        $makeSign = Rsa::sign($message,$this->getPrivateKey());
//        if ($sign != $makeSign) return false;
//        return true;
//    }
//    protected function checkTimestamp($timestamp)
//    {
//        // reject responses beyond 5 minutes
//        return \abs((int)$timestamp - \time()) <= 300;
//    }
//
//    protected function getHeader($name)
//    {
//        dump(self::$header);
//        dump($name);
//        return self::$header[$name];
//    }

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
        $url = self::$defaults['base_uri'] . $url;
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
//        $curlError = curl_error($curl);

        list($content, $status) = [curl_exec($curl), curl_getinfo($curl), curl_close($curl)];
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
