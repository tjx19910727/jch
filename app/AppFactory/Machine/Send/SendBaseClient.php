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
//        if ($this->machine['online'] == 2) die(json(['state' => 100, "msg" => $this->lang("VMachine.machine_offline")])->send());

        if ($this->machine['online'] == 1) {
            $this->data = [
                "msg_id" => uniqid(),
                "timestamp" => time(),
                "machine_id" => $this->machine['machine_id'],
                "data" => json_encode($data),
            ];
            $this->config['machine_id'] = $this->machine['machine_id'];
            $this->data['sign'] = $this->makeSign($this->data);
            actionLog($this->data, '发送至MQ服务器的数据');
            $this->dataRecord(2, 2);

            actionLog($this->mqQueue,'下发命令队列');
            $result = MqProducer::dataSend($this->data, $this->mqQueue);
            actionLog($result,'发送结果');
            if ($result !== true) {
                return $result;
            }
            return $this->rSuccess($result);
        }
        return $this->rFail($this->lang("offline"));
    }
}