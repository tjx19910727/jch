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
use app\AppFactory\Kernel\Traits\Send\ToManagerTrait;
use Mqtt\Mqtt;

class MachineBaseClient extends BaseClient
{
    use MachineTrait,MachineMqRecordTrait;
    use ToManagerTrait;

    public $machine;
    public $data;

    public function __construct(ServiceContainer $app)
    {
        parent::__construct($app);

//        actionLog($this->config,'接收数据');
        $this->machine = $this->getMachineFind(['machine_id' => $this->config['machine_id']]);
        if (!$this->machine) die(json_encode(['state' => 100, "msg" => $this->lang("query_machine_no_data")],320));
        // 设备离线状态下收到数据，认为已上线，触发发送上线通知
        $this->sendOnline();
    }

    /**
     * 发送上线通知
     */
    public function sendOnline()
    {
        if ($this->machine['online'] == 2) {
            try {
                $this->noticeSendData = [
                    "ao_id" => $this->machine['ao_id'],
                    "m_id" => $this->machine['m_id'],
                    "templateType" => "online",
                    "replaceData" => [
                        "online" => "online",
                        "machine_id" => $this->machine['machine_id'],
                        "machine_name" => $this->machine['machine_name'],
                    ]
                ];
                $this->noticeSend();
            } catch (\Exception $e) {
                actionLog("发送在线通知抛出异常");
                actionException($e,1);
            }
        }
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
        $this->delMachineMqRecord([['create_time','<',strtotime("-7 days")]]);
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
}