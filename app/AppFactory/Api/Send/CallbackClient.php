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

class CallbackClient extends ApiBaseClient
{
    use ApiCallbackTrait,ApiAdvanceTrait;

    public $callbackData;

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
    ];
    public $frequency = 0;

    public function initCallback()
    {
        $this->callbackData = cache("callback" . $this->frequency);
        if (!$this->callbackData) {
            $callback = $this->getApiCallbackList(['callback_status' => "send",'callback_frequency' => $this->frequency],0,
                'ac_id,aa_id,uuid,notify_url,callback_type,callback_time,callback_frequency,callback_status,message,create_time');
            if ($callback) {
                $this->callbackData = $callback->toArray();
            }
        }
    }

    public function push()
    {
        if ($this->callbackData) {
            $nextCallbackData = cache("callback" . ($this->frequency+1));
            $noNext = 0;
            foreach ($this->callbackData as $key => $value) {
                if (!isset($this->intervalTime[$value['callback_type']][($value['callback_frequency']+ 1)])) $noNext = 1;
                $time = $this->intervalTime[$value['callback_type']][$value['callback_frequency']];

                // 达到间隔时间，推送数据
                if (time() - $value['create_time'] >= $time) {
                    if (!$value['uuid']) $value['uuid'] = uniqid();
                    // 发起当前推送
                    $curl = $this->curl_request($value['notify_url'], "POST", $value['message']);
                    actionLog($value['message'],'推送数据');
                    actionLog($curl,'推送结果');
                    $value['callback_time'] = date("Y-m-d H:i:s");
                    $value['result'] = is_string($curl) ? $curl : json_encode($curl,320);
                    // 返回成功
                    if ($curl == "success") {
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
                        $nextCallbackData[] = $value;
                    }
                    if (!$noNext)
                        cache("callback" . ($this->frequency+1),$nextCallbackData,7600);
                }
            }
            cache("callback" . $this->frequency,$this->callbackData);
        }
    }
}