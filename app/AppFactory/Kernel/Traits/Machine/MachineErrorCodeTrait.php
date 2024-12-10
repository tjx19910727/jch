<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/4/30
 * Time: 11:05
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Machine\MachineErrorCodeModel;

trait MachineErrorCodeTrait
{
    public function getMachineErrorCodeList($where,$pageNum = 0,$field = "*", $order = "",$eachFunc = "", $group = "")
    {
        return MachineErrorCodeModel::getList($where,$pageNum,$field,$order,$eachFunc,$group);
    }

    public function getMachineErrorCodeFind($where,$field = "*", $order = "")
    {
        return MachineErrorCodeModel::getFind($where,$field,$order);
    }

    public function addMachineErrorCode($insert)
    {
        $me = MachineErrorCodeModel::create($insert);
        return $me->me_id;
    }

    public function updateMachineErrorCode($update,$where = [],$field = [])
    {
        return MachineErrorCodeModel::update($update,$where,$field);
    }

    public function errorCode()
    {
        actionLog($this->message,'故障码上报');
        $this->getMachineAddress();
        $insert = [
            "m_id" => $this->machine['m_id'],
            "machine_id" => $this->machine['machine_id'],
            "machine_name" => $this->machine['machine_name'],
            "address" => $this->machine['address'] ?? "",
            "error_position" => $this->message['error_position'] ?? 1,
            "errorCode" => $this->message['errorCode'],
            "remark" => $this->lang("deviceErrorCode." . $this->message['errorCode']),
            "msg" => $this->message['msg'] ?? "",
            "ao_id" => $this->machine['ao_id'] ?? 0,
        ];
        $result = $this->addMachineErrorCode($insert);
        if ($result) {
            $machine = $this->machine;
            if (!is_array($this->machine)) $machine = $this->machine->toArray();
            $errorMsg = $this->lang("deviceErrorCode." . $this->message['errorCode']);
            $machine['errorCode'] =  $errorMsg == "deviceErrorCode." . $this->message['errorCode'] ? $this->message['errorCode'] : $errorMsg;
            $machine['date'] = date("Y年m月d日");
            $machine['exceptionDeclaration'] = $errorMsg;
            $this->noticeSendData = [
                "ao_id" => $this->machine['ao_id'],
                "m_id" => $this->machine['m_id'],
                "templateType" => "mFault",
                "replaceData" => $machine,
            ];
            actionLog($this->noticeSendData,'发送设备故障通知');
            @$this->noticeSend();
        }
        return 1;
    }

    public function delMachineErrorCode($where)
    {
        return MachineErrorCodeModel::whereDel($where);
    }
}