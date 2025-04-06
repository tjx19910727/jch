<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/8/6
 * Time: 8:50
 */

namespace app\AppFactory\Kernel\Traits;


trait CurlTrait
{
    /**
     * 请求
     * @param $url
     * @param string $method
     * @param array $data
     * @param bool $header
     * @param int $timeout
     * @return bool|string
     */
    public function curl_request($url, $method = 'get', $data = array(), $header = false)
    {
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
        actionLog($curlError,'请求错误',"CURL");
        list($content, $status) = [curl_exec($curl), curl_getinfo($curl), curl_close($curl)];
        actionLog($content,'请求结果内容',"CURL");
        actionLog($status,'请求结果status',"CURL");
        $content = trim(substr($content, $status['header_size']));
        return  $content;
    }


}
