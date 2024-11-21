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

class MachineBaseClient extends BaseClient
{
    use MachineTrait,MachineMqRecordTrait;
    use ToManagerTrait;

    public $machine;
    public $data;
    // 下发队列名称
    public $mqQueue;

    public function __construct(ServiceContainer $app)
    {
        parent::__construct($app);

        actionLog($this->config,'接收数据2');
        $this->machine = $this->getMachineFind(['machine_id' => $this->config['machine_id']]);
        if (!$this->machine) die(json_encode(['state' => 100, "msg" => $this->lang("query_machine_no_data")],320));
        $this->getMqQueue();
        // 设备离线状态下收到数据，认为已上线，触发发送上线通知
        $this->sendOnline();
    }

    /**
     * 检查Mac地址
     * @param string $mac
     * @return array|bool|\think\response\Json
     */
    public function checkMac($mac = "")
    {
        if (isset($this->machine['version']) && $this->machine['version']) {
            $versionArr = explode(".", $this->machine['version']);
            // 版本号小于0.2.12时，采用旧的MQ队列，大于等于0.2.12时，MQ队列名称增加MAC地址标示
            if (isset($versionArr[0]) && $versionArr[0] == 0 && isset($versionArr[1]) && $versionArr[1] >= 2 && isset($versionArr[2]) &&  $versionArr[2] >= 12) {
                if ($mac != $this->machine['mac_address']) {
                    actionLog($versionArr,'设备版本',"mac_check");
                    actionLog(["mac" => $mac,"mac_address" => $this->machine['mac_address']],"Mac地址匹配失败","mac_check");
                    return $this->r(300, $this->lang("mac_not_match"));
                }
            }
        }
        return true;
    }

    /**
     * 获取0.2.12版本的下发MQ队列名
     */
    public function getMqQueue()
    {
        $this->mqQueue = $this->machine['machine_id'];
        if (isset($this->machine['version']) && $this->machine['version']) {
            $versionArr = explode(".", $this->machine['version']);
            // 版本号小于0.2.12时，采用旧的MQ队列，大于等于0.2.12时，MQ队列名称增加MAC地址标示
            if (isset($versionArr[0]) && $versionArr[0] == 0 && isset($versionArr[1]) && $versionArr[1] >= 2 && isset($versionArr[2]) &&  $versionArr[2] >= 12) {
                $this->mqQueue = $this->machine['machine_id'] . "_" . str_replace(":","_",$this->machine['mac_address']);
            }
        }
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
                $result = $this->noticeSend();
                actionLog($result,'发送结果');
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
        if ($controller) $path = $controller;
        if ($action) $path = "/" . $action;
        if (!$path) {
            if (isset($this->message['data']) && $this->message['data']) {
                $path = json2arr($this->message['data'])['msgType'] ?? "";
            }
        }
        if ($path != "heartbeat") {
            $msg = $this->getMachineMqRecordFind(['msg_id' => $this->data['msg_id']]);
            if (!$msg) {
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

    }
}