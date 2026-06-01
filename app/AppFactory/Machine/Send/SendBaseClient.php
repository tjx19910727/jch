<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/2/22
 * Time: 11:53
 */

namespace app\AppFactory\Machine\Send;


use app\AppFactory\Kernel\ServiceContainer;
use app\AppFactory\Machine\MachineBaseClient;
use app\AppFactory\RabbitMq\MqProducer;

class SendBaseClient extends MachineBaseClient
{
    /**
     * 组装下发给设备的数据，保持 HTTP 直推与 MQ 下发格式一致。
     * @param array $data
     * @param int $from 1: API/HTTP, 2: MQ
     * @return array
     */
    protected function buildSendData($data, $from = 2)
    {
        $this->data = [
            "msg_id" => uniqid(),
            "timestamp" => time(),
            "machine_id" => $this->machine['machine_id'],
            "data" => json_encode($data),
        ];
        $this->config['machine_id'] = $this->machine['machine_id'];
        $this->data['sign'] = $this->makeSign($this->data);
        $this->dataRecord($from, 2);
        return $this->data;
    }

    /**
     * 发送设备数据至RabbitMQ服务器
     * @param array $data
     * @return array|string
     */
    public function dataSendRabbitMQ($data)
    {
//        if ($this->machine['online'] == 2) die(json(['state' => 100, "msg" => $this->lang("VMachine.machine_offline")])->send());

        if ($this->machine['online'] == 1) {
            $this->buildSendData($data, 2);
            actionLog($this->data, '发送至MQ服务器的数据');

            actionLog($this->mqQueue,'下发命令队列');
            actionLog($this->data,'下发的数据');
            $result = MqProducer::dataSend($this->data, $this->mqQueue);
            actionLog($result,'dataSendRabbitMq发送结果');
            if ($result !== true) {
                return $result;
            }
            return $this->rSuccess($result);
        }
        return $this->rFail($this->lang("offline"));
    }

    /**
     * 通过 HTTP 直接推送设备数据。
     * @param array $data
     * @param string $url
     * @return array|string
     */
    public function dataSendHttp($data, $url)
    {
        if (!$url) {
            return 'machine_http_push_url_require';
        }
        if (($this->machine['online'] ?? 2) != 1 && ($this->machine['http_online'] ?? 2) != 1) {
            return $this->lang("offline");
        }

        $sendData = $this->buildSendData($data, 1);
        actionLog(['url' => $url, 'data' => $sendData], 'HTTP直推设备数据', 'dataSendHttp');

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($sendData, JSON_UNESCAPED_UNICODE));
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'mac: ' . ($this->machine['mac_address'] ?? ''),
        ]);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        if (1 == strpos("$" . $url, "https://")) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        $response = curl_exec($curl);
        $error = curl_error($curl);
        $status = curl_getinfo($curl);
        curl_close($curl);

        actionLog(['response' => $response, 'error' => $error, 'status' => $status], 'HTTP直推设备结果', 'dataSendHttp');
        if ($error) {
            return $error;
        }
        if (isset($status['http_code']) && ($status['http_code'] < 200 || $status['http_code'] >= 300)) {
            return 'http_code_' . $status['http_code'];
        }

        return $this->rSuccess($response);
    }
}
