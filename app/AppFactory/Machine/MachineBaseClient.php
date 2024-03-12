<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/22
 * Time: 20:12
 */

namespace app\AppFactory\Machine;


use app\AppFactory\Kernel\BaseClient;
use app\AppFactory\Kernel\ServiceContainer;
use app\AppFactory\Kernel\Traits\Machine\MachineMqRecordTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use Mqtt\Mqtt;

class MachineBaseClient extends BaseClient
{
    use MachineTrait,MachineMqRecordTrait;

    public $machine;
    public $data;

    public function __construct(ServiceContainer $app)
    {
        parent::__construct($app);

//        actionLog($this->config,'接收数据');
        $this->machine = $this->getMachineFind(['machine_id' => $this->config['machine_id']]);
        if (!$this->machine) die(json_encode(['state' => 100, "msg" => $this->lang("query_machine_no_data")],320));
    }


    /**
     * 记录接收到的上报数据
     * @param int $from  数据来源，1：API，2：MQ
     * @param int $type  消息类型，1：接收，2：发送
     */
    public function dataRecord($from = 1,$type = 1)
    {
        $controller = request()->controller();
        $action = request()->action();
        $path = "";
        if ($controller) $path = $controller;
        if ($action) $path = "/" . $action;
        if (!$path) $path = ($this->message['msgType'] ?? "");
        $insertMqRecord = [
            "m_id" => $this->machine['m_id'],
            "machine_id" => $this->machine['machine_id'],
            "machine_name" => $this->machine['machine_name'],
            "msg_id" => $this->data['msg_id'],
            "path" => $path,
            "content" => json_encode($this->data),
            "from" => $from,
            "type" => $type,
        ];
        $this->addMachineMqRecord($insertMqRecord);
    }

    /**
     * 发布Mqtt，暂停使用
     * @param $content
     * @return bool
     */
    public function sendMqtt2Machine($content)
    {
        $server = "broker.emqx.io";     // 服务代理地址(mqtt服务端地址)
        $port = 1883;                     // 通信端口
        $username = "dkm";                   // thing的Key
        $password = "dkm123456";                   // Token
        $client_id = $this->machine['machine_id'] . "_000002";           // APPID
        $mqtt = new Mqtt($server, $port, $client_id); //实例化MQTT类
        if ($mqtt->connect(true, NULL, $username, $password)) {
            actionLog($content,'发布MQTT');
            $mqtt->publish("dataSend/" . $this->machine['machine_id'], $content);
            $mqtt->close();
            return true;
        } else {
            return false;
        }
    }
}