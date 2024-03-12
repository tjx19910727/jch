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
    public function __construct(ServiceContainer $app)
    {
        parent::__construct($app);
    }

    /**
     * 发送设备数据至RabbitMQ服务器
     * @param array $data
     * @return array|string
     */
    public function dataSendRabbitMQ($data)
    {
        if ($this->machine['online'] == 2) die(json(['state' => 100, "msg" => $this->lang("VMachine.machine_offline")])->send());
        $this->data = [
            "msg_id" => uniqid(),
            "timestamp" => time(),
            "machine_id" => $this->machine['machine_id'],
            "data" => json_encode($data),
        ];
        $this->data['sign'] = $this->makeSign($this->data);
        $this->dataRecord(2,2);
        $result = MqProducer::dataSend($this->data,$this->machine['machine_id']);
        if ($result != "OK") {
            return $this->rFail($result);
        }
        return $this->rSuccess($result);
    }
}