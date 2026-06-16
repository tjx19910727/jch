<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/6/22
 * Time: 9:17
 */

namespace app\AppFactory\Api\Send;


use app\AppFactory\Api\ApiBaseClient;
use app\AppFactory\Kernel\Traits\Api\ApiAdvanceTrait;
use app\AppFactory\Kernel\Traits\Api\ApiCallbackTrait;

define("LOG_NAME","callback");
define("TEST",0);
class CallbackClient extends ApiBaseClient
{
    use ApiCallbackTrait,ApiAdvanceTrait;

       public $callbackData;
    protected $httpRetryTimes = 3;
    protected $httpRetryInterval = 5;

    /**
     * @var array 各类型数据推送时间间隔，最多8次，最大7560秒
     */
    protected $intervalTime = [
        "1" => [0,60,300,900,900,1800,3600,7200],
        "2" => [0,60,300,900,900,1800,3600,7200],
        "3" => [0,60,300,900,900,1800,3600,7200],
        "4" => [0,60,300,900,900,1800,3600,7200],
        "5" => [0,60,300],
        "6" => [0,60,300],
        "7" => [0,60,300,900,900,1800,3600,7200],
        "8" => [0,60,300,900,900,1800,3600,7200],
        "9" => [0,60],
        "10" => [0,60,300,900,900,1800,3600,7200],
    ];
    public $frequency = 0;

    /**
     * 触发推送通知
     */
    public function trigger_send()
    {
        for ($i = 0; $i < 8; $i++) {
            if ($i > 0) {
                $start = cache("start");
                if (!$start) break;
            }
            $this->frequency = $i;
            $this->initCallback();
            if ($i == 0  && $this->callbackData) cache("start",1,7600);
            $this->push();
        }
        sleep(1);
        return "处理完毕";
    }

    /**
     * 初始货每次推送数据
     */
    protected function initCallback()
    {
        $this->callbackData = cache("callback" . $this->frequency);
        if ($this->callbackData) actionLog($this->callbackData,'推送数据',LOG_NAME);
        if (!$this->callbackData) {
            $callback = $this->getApiCallbackList(['callback_status' => "send",'callback_frequency' => $this->frequency],0,
                'ac_id,aa_id,uuid,notify_url,callback_type,callback_time,callback_frequency,callback_status,message,create_time');
            if ($callback) {
                $this->callbackData = $callback->toArray();
            }
        }
    }

    /**
     * 推送主程序
     */
    protected function push()
    {
        if ($this->callbackData) {
            $nextCallbackData = cache("callback" . ($this->frequency+1));
            $noNext = 0;
            foreach ($this->callbackData as $key => $value) {
                // 没有这个类型的时间设定，初始化默认值
                if (!isset($this->intervalTime[$value['callback_type']])) $this->intervalTime[$value['callback_type']] = [0,60,300,900,900,1800,3600,7200];
                if (!isset($this->intervalTime[$value['callback_type']][($value['callback_frequency']+ 1)])) $noNext = 1;
                $time = $this->intervalTime[$value['callback_type']][$value['callback_frequency']];
                if (TEST == 1) $time = 0;

                // 达到间隔时间，推送数据
                if (time() - $value['create_time'] >= $time) {
                    if (!$value['uuid']) $value['uuid'] = uniqid();
                    // 发起当前推送，HTTP 状态码非 200 时 5 秒间隔重试 3 次
                    $curlResult = $this->sendCallbackWithHttpRetry($value['notify_url'], $value['message']);
                    $curl = $curlResult['content'];
                    actionLog($value['message'],'推送数据',LOG_NAME);
                    actionLog($curlResult,'推送结果',LOG_NAME);
                    $value['callback_time'] = date("Y-m-d H:i:s");
                    $value['result'] = json_encode($curlResult,320);
                    // 返回成功
                    if ($curlResult['http_code'] == 200 && $curl == "success") {
                        $value['callback_status'] = "success";
                        $this->updateApiCallback($value);
                    } else {
                        $value['callback_status'] = "fail";
                        // 达到推送总次数，停止推送
                        if ($value['callback_frequency'] == count($this->intervalTime[$value['callback_type']]) - 1) $value['callback_status'] = "cancel";
                        $this->updateApiCallback($value);
                    }
                    unset($value['ac_id'], $value["callback_time"], $value['result'], $this->callbackData[$key]);
                    // 不是成功与终止，新增发送记录
                    if ($value['callback_status'] != "cancel" && $value['callback_status'] != "success") {
                        $value['callback_status'] = "send";
                        $value['callback_frequency']++;
                        $value['create_time'] = time();
                        $value['ac_id'] = $this->addApiCallback($value);
                        actionLog($this->getLS(),'新增发送记录',LOG_NAME);
                        $nextCallbackData[] = $value;
                    }
                    if (!$noNext)
                        cache("callback" . ($this->frequency+1),$nextCallbackData,7600);
                }
            }
            cache("callback" . $this->frequency,$this->callbackData);
        }
    }

    /**
     * HTTP 状态码非 200 时进行短间隔重试。
     */
    protected function sendCallbackWithHttpRetry($url, $message): array
    {
        $result = [];
        $maxAttempts = $this->httpRetryTimes + 1;
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $result = $this->sendCallbackHttpRequest($url, $message, $attempt);
            if (intval($result['http_code']) === 200) {
                break;
            }
            if ($attempt <= $this->httpRetryTimes) {
                sleep($this->httpRetryInterval);
            }
        }
        return $result;
    }

    protected function sendCallbackHttpRequest($url, $message, $attempt): array
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $message);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        if (1 == strpos("$" . $url, "https://")) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }

        $response = curl_exec($curl);
        $error = curl_error($curl);
        $status = curl_getinfo($curl);
        curl_close($curl);

        $content = is_string($response) ? trim(substr($response, $status['header_size'] ?? 0)) : '';
        return [
            'attempt' => $attempt,
            'http_code' => intval($status['http_code'] ?? 0),
            'content' => $content,
            'error' => $error,
            'url' => $url,
        ];
    }
}
