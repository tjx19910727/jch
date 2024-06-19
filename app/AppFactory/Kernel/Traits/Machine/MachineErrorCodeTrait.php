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
    public function getMachineErrorCodeList($where,$pageNum = 0,$field = "*", $order = "")
    {
        return MachineErrorCodeModel::getList($where,$pageNum,$field,$order);
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
        $this->addMachineErrorCode($insert);
        return 1;
    }
}