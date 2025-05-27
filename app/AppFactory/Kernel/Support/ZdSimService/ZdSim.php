<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021/2/25
 * Time: 15:37
 */

namespace app\AppFactory\Kernel\Support\ZdSimService;


define("QUERY_CARD","http://cmp.zdm2m.com/smsApi.do?querycard");
define("BATCH_QUERY","http://cmp.zdm2m.com/smsApi.do?batchquerycard");
define("GET_BALANCE","http://cmp.zdm2m.com/smsApi.do?getBalance");
define("QUERY_ORDER_INFO","http://cmp.zdm2m.com/smsApi.do?queryOrderInfo");
define("RENEW","http://cmp.zdm2m.com/smsApi.do?packageRenew");

/**
 * Class ZdSim
 * @method static queryCard($cardNo)  查询卡信息
 * @package app\AppFactory\Kernel\Support\ZdSimService
 */
class ZdSim
{
    /**
     * 错误信息
     * @var string
     */
    public $curlError;

    /**
     * header头信息
     * @var string
     */
    public $headerStr;

    /**
     * @var mixed 中点物联平台clientId
     */
    public $clientId;

    /**
     * 请求状态
     * @var int
     */
    public $status;

    /**
     * 初始化
     * ZdSim constructor.
     */
    public function __construct()
    {
        $this->clientId = env("ZdSim.clientId");
    }

    /**
     * 静态化调用
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        // TODO: Implement __callStatic() method.
        $app = new self();
        $name = "_" . $name;
        return $app->$name(...$arguments);
    }

    /**
     * 1.5  1.7  查询卡信息  1.7 批量查询
     * @param $cardNo
     * @return bool|string
     */
    public function _queryCard($cardNo)
    {
        if(!$cardNo) $cardNo = input("cardNo");
        $data = [
            "clientid" => $this->clientId,
            "cardno" => $cardNo,
        ];
        $data['sign'] = $this->make_get_sign($data);
        strpos($cardNo,',') ? $url = BATCH_QUERY : $url = QUERY_CARD;
        $url .= "&".http_build_query($data);
        $result = $this->request($url);
        $result = json_decode($result,true);
        return $result;
    }

    /**
     * 1.6	卡片续费、补交欠费 需联系客户经理在平台添加授权IP白名单后才能使用该接口
     * @param $cardNo
     * @param $amount
     * @param int $genre
     * @return bool|string
     */
    public function packageRenew($cardNo,$amount,$genre = 1)
    {
        $data = [
            "clientid" => $this->clientId,
            "cardno" => $cardNo,
            "amount" => $amount,
            "genre"  => $genre,
        ];
        $data['sign'] = $this->make_get_sign($data);
        $url = RENEW . "&" . http_build_query($data);
        $result = $this->request($url);
        $result = json_decode($result,true);
        return $result;
    }

    /**
     * 1.8	查询卡片开卡价格及套餐时长  	需联系客户经理在平台添加授权IP白名单后才能使用该接口
     * @param $cardNo
     * @return bool|string
     */
    public function queryOrderInfo($cardNo)
    {
        $data = [
            "clientid" => $this->clientId,
            "cardno" => $cardNo,
        ];
        $data['sign'] = $this->make_get_sign($data);
        $url = QUERY_ORDER_INFO .  "&".http_build_query($data);
        $result = $this->request($url);
        $result = json_decode($result,true);
        return $result;
    }

    /**
     * 1.9 查询账户余额 	需联系客户经理在平台添加授权IP白名单后才能使用该接口
     * @return bool|string
     */
    public function getBalance()
    {
        $data['clientid'] = $this->clientId;
        $data['sign'] = $this->make_get_sign($data);
        $url = GET_BALANCE ."&" . http_build_query($data);
        $result = $this->request($url);
        $result = json_decode($result,true);
        return $result;
    }

    /**
     * 生成签名
     * @param $data
     * @return string
     */
    public function make_get_sign($data)
    {
        return strtoupper(md5(urldecode(http_build_query($data))));
    }

    /**
     * curl 请求
     * @param string $url 请求地址
     * @param string $method 请求方式
     * @param array $data 请求数据
     * @param bool $header 请求header头
     * @param int $timeout 超时秒数
     * @return bool|string
     */
    public function request($url, $method = 'get', $data = array(), $header = false, $timeout = 15)
    {
        $this->status = null;
        $this->curlError = null;
        $this->headerStr = null;

        $curl = curl_init($url);
        $method = strtoupper($method);
        //请求方式
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        //post请求
        if ($method == 'POST') curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
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
        $this->curlError = curl_error($curl);

        list($content, $status) = [curl_exec($curl), curl_getinfo($curl), curl_close($curl)];
        $this->status = $status;
        $this->headerStr = trim(substr($content, 0, $status['header_size']));
        $content = trim(substr($content, $status['header_size']));
        return (intval($status["http_code"]) === 200) ? $content : false;
    }
}