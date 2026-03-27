<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/3/26
 * Time: 10:00
 */

namespace app\AppFactory\Kernel\Support\SimiotService;

define("SIMIOT_QUERY_CARD", "https://iot.simiot.com/api/client/v1");

/**
 * Class Simiot
 * @method static queryCard($iccid, $cycles = 12)  查询卡信息
 * @package app\AppFactory\Kernel\Support\SimiotService
 */
class Simiot
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
	 * @var mixed appid
	 */
	public $appId;

	/**
	 * @var mixed secret
	 */
	public $secret;

	/**
	 * 请求状态
	 * @var int
	 */
	public $status;

	/**
	 * 初始化
	 * Simiot constructor.
	 */
	public function __construct()
	{
		$this->appId = '8130053573632192';
		$this->secret = 'W9U3pCVkLpOeEgELZJk9NxtdZid73HDm';
	}

	/**
	 * 静态化调用
	 * @param $name
	 * @param $arguments
	 * @return mixed
	 */
	public static function __callStatic($name, $arguments)
	{
		$app = new self();
		$name = "_" . $name;
		return $app->$name(...$arguments);
	}

	/**
	 * 查询卡信息
	 * @param string $iccid
	 * @param int $cycles
	 * @return array
	 */
	public function _queryCard($iccid, $cycles = 12)
	{
		if (!$iccid) {
			return ['code' => -1, 'message' => 'iccid empty'];
		}
		if (!$this->appId || !$this->secret) {
			return ['code' => -1, 'message' => 'simiot config missing'];
		}

		$data = [
			'appid' => $this->appId,
			'timestamp' => time(),
			'iccids' => $iccid,
		];
		$data['sign'] = $this->makePostSign($data);
        actionLog($data, "查询新物联接口参数");
		$result = $this->request(SIMIOT_QUERY_CARD.'/sim-card/base-info', 'POST', http_build_query($data), [
			'Content-Type: application/x-www-form-urlencoded;charset=utf-8'
		]);
        actionLog($result, "查询新物联接口返回结果");
		if ($result === false) {
			return [
				'code' => -1,
				'message' => 'request new sim api failed',
				'http_code' => intval($this->status['http_code'] ?? 0),
				'curl_error' => $this->curlError,
				'response' => $this->status['response_body'] ?? ''
			];
		}

		$result = json_decode($result, true);
		if (!is_array($result)) {
			return [
				'code' => -1,
				'message' => 'new sim api response parse failed',
				'http_code' => intval($this->status['http_code'] ?? 0),
				'response' => $this->status['response_body'] ?? ''
			];
		}
		return $result;
	}

	/**
	 * 生成签名
	 * 所有参数按ASCII升序，key=value&key=value...拼接后直接拼接secret，
	 * 然后base64，再sha256
	 * @param array $data
	 * @return string
	 */
	public function makePostSign($data)
	{
		ksort($data);
		$pairList = [];
		foreach ($data as $key => $value) {
			$pairList[] = $key . "=" . $value;
		}
		$str = implode("&", $pairList) . $this->secret;
		return hash('sha256', base64_encode($str));
	}

	/**
	 * curl 请求
	 * @param string $url 请求地址
	 * @param string $method 请求方式
	 * @param array $data 请求数据
	 * @param bool|array $header 请求header头
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

