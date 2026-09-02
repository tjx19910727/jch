<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/3/26
 * Time: 10:00
 */

namespace app\AppFactory\Kernel\Support\SimiotService;

use app\AppFactory\Kernel\Service\FaultNotice\FaultReportService;

define("SIMIOT_QUERY_CARD", "https://iot.simiot.com/api/client/v1");

/**
 * Class Simiot
 * @method static queryCard($iccid, $cycles = 12)  查询卡信息
 * @method static queryCardBatch($iccids = [], $batchSize = 90) 批量查询卡信息
 * @method static queryPool()  查询流量池信息
 * @method static checkWarning()  查询是否需要预警
 * @method static queryDayUsage($iccid, $dayBegin, $dayEnd)  查询单卡每日用量
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

	public $defaultWarningValue;
	public $defaultWarningAoId;

	/**
	 * 初始化
	 * Simiot constructor.
	 */
	public function __construct()
	{
		$this->appId = '8130053573632192';
		$this->secret = 'W9U3pCVkLpOeEgELZJk9NxtdZid73HDm';
		$this->defaultWarningValue = 5; //默认预警值,单位%
		$this->defaultWarningAoId = 1;
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
	 * 批量查询卡信息（单次最多100，这里默认90）
	 * @param array|string $iccids
	 * @param int $batchSize
	 * @return array
	 */
	public function _queryCardBatch($iccids = [], $batchSize = 90)
	{
		if (is_string($iccids)) {
			$iccids = explode(',', $iccids);
		}
		if (!$iccids) {
			return ['code' => 0, 'message' => 'ok', 'result' => [], 'failed' => []];
		}

		$batchSize = intval($batchSize);
		if ($batchSize <= 0 || $batchSize > 100) {
			$batchSize = 90;
		}

		$all = [];
		$failed = [];
		$chunks = array_chunk($iccids, $batchSize);
		foreach ($chunks as $chunk) {
			$res = $this->_queryCard(implode(',', $chunk));
			if (!is_array($res) || empty($res['result'])) {
				$failed[] = [
					'iccids' => $chunk,
					'res' => $res,
				];
				continue;
			}
			$list = $res['result'] ?? [];
			if (!is_array($list)) {
				$list = [];
			}
			$all = array_merge($all, $list);
		}

		return [
			'code' => 0,
			'message' => 'ok',
			'result' => $all,
			'failed' => $failed,
		];
	}

	/**
	 * 查询流量池信息
	 * @return array
	 */
	public function _queryPool()
	{
		if (!$this->appId || !$this->secret) {
			return ['code' => -1, 'message' => 'simiot config missing'];
		}

		$data = [
			'appid' => $this->appId,
			'timestamp' => time(),
		];
		$data['sign'] = $this->makePostSign($data);
        actionLog($data, "查询新物联流量池接口参数");
		$result = $this->request(SIMIOT_QUERY_CARD.'/traffic-pool/pool-list', 'POST', http_build_query($data), [
			'Content-Type: application/x-www-form-urlencoded;charset=utf-8'
		]);
        actionLog($result, "查询新物联流量池接口返回结果");
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
	 * 查询是否需要预警
	 * @return bool
	 */
	public function _checkWarning()
	{
		$result = $this->_queryPool();
		if (isset($result['code']) && $result['code'] == 0) {
			$res = $result['result'][0] ?? [];
			if (isset($res['traffic_left']) && !empty($res['traffic_total'])) {
				$rate = bcdiv((string)$res['traffic_left'], (string)$res['traffic_total'], 4) * 100;
				if ($rate <= $this->defaultWarningValue) {
					// 每天开始，触发后，4小时发送一次
					$cacheKey = 'simiot.pool.warning.sent.' . date('Ymd');
					if (!cache($cacheKey)) {
						$sendResult = $this->sendTrafficWarningNotice($rate, $res);
						if ($sendResult !== false) {
							cache($cacheKey, 1, 14400);
						}
					}else{
						actionLog([], '已发送，无需重复发送');
					}
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * 发送流量预警模板消息
	 * @param float $rate
	 */
	protected function sendTrafficWarningNotice($rate)
	{
		try {
			$machine = [
				'm_id' => 0,
				'ao_id' => $this->defaultWarningAoId,
				'machine_id' => 'LLYJ',
				'machine_name' => '流量预警',
			];
			$message = [
				'errorCode' => '120333311',
				'msg' => '剩余流量仅' . $rate . '%，请及时充值',
				'error_position' => 2,
				'organization_notice' => true,
			];
			$meId = (new FaultReportService())->report($machine, $message);
			actionLog([
				'me_id' => intval($meId),
				'error_code' => '120333311',
				'rate' => $rate,
			], '发送新物联流量预警故障通知结果', 'simiotTrafficWarning');
			return intval($meId) > 0;
		} catch (\Throwable $e) {
			actionException($e, 1);
			return false;
		}
	}

	/**
	 * 查询卡在起止日期内的每日用量
	 * @param string $iccid 单个iccid
	 * @param string $dayBegin 开始日期，格式YYYYMMDD
	 * @param string $dayEnd 截止日期，格式YYYYMMDD
	 * @return array result里的day:日期，usage:用量，单位MB
	 */
	public function _queryDayUsage($iccid, $dayBegin, $dayEnd)
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
			'iccid' => $iccid,
			'day_begin' => $dayBegin,
			'day_end' => $dayEnd,
		];
		$data['sign'] = $this->makePostSign($data);
		actionLog($data, "查询新物联单卡日用量接口参数");
		$result = $this->request(SIMIOT_QUERY_CARD . '/sim-card/query-day-usage', 'POST', http_build_query($data), [
			'Content-Type: application/x-www-form-urlencoded;charset=utf-8'
		]);
		actionLog($result, "查询新物联单卡日用量接口返回结果");
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

