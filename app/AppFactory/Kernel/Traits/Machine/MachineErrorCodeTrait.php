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
        $position = 1;
        if (isset($this->message['error_position']) && $this->message['error_position']) {
            $position = $this->message['error_position'];
        }  else {
            $len = strlen($this->message['errorCode']);
            if ($len == 9) $position = 2;
            if ($len == 7) $position = 3;
        }
        // 同一设备同一错误码30秒内不重复插入
        $recentEc = MachineErrorCodeModel::getCount([
            'm_id' => $this->machine['m_id'],
            'errorCode' => $this->message['errorCode'],
            ['create_time','>=',time() - 30]
        ]);
        if ($recentEc) return 1;

        // $lastEc = $this->getMachineErrorCodeFind([
        //     'm_id' => $this->machine['m_id'],
        //     'errorCode' => $this->message['errorCode'],
        //     ['create_time','>=',time() - env('errorCode.noticeTime') ?? 1800 ]
        // ],'me_id','me_id desc');
        $insert = [
            "m_id" => $this->machine['m_id'],
            "machine_id" => $this->machine['machine_id'],
            "machine_name" => $this->machine['machine_name'],
            "address" => $this->machine['address'] ?? "",
            "error_position" => $position,
            "errorCode" => $this->message['errorCode'],
            "remark" => $this->lang("deviceErrorCode." . $this->message['errorCode']),
            "msg" => $this->message['msg'] ?? "",
            "ao_id" => $this->machine['ao_id'] ?? 0,
        ];
        if (in_array($this->message['errorCode'], ['1200010', '1200020']) && !empty($this->message['creator_id'])) {
            $insert['creator_id'] = $this->message['creator_id'];
        }
        $result = $this->addMachineErrorCode($insert);
        if ($result && !in_array($this->message['errorCode'], ['1100000', '1000001'])) {
            //if (!$lastEc) {
                $machine = $this->machine;
                if (!is_array($this->machine)) $machine = $this->machine->toArray();
                $machine['machine_name'] = mb_substr($machine['machine_name'], 0, 20, 'UTF-8');
                $errorMsg = $this->lang("deviceErrorCode." . $this->message['errorCode']);
                $machine['errorCode'] = $errorMsg == "deviceErrorCode." . $this->message['errorCode'] ? $this->message['errorCode'] : $errorMsg;
                $machine['date'] = date("Y年m月d日");
                $machine['exceptionDeclaration'] = $errorMsg;
                $machine['error_code'] = mb_substr($errorMsg, 0, 20, 'UTF-8');
                $machine['error_time'] = date('Y-m-d H:i:s');
                $machine['error_info'] = $this->message['errorCode'];
                $this->noticeSendData = [
                    "ao_id" => $this->machine['ao_id'],
                    "m_id" => $this->machine['m_id'],
                    "me_id" => $result,
                    "templateType" => "mFault",
                    "replaceData" => $machine,
                ];
                actionLog($this->noticeSendData, '发送设备故障通知');
                @$this->noticeSend();
            //}
        }
        return 1;
    }

    public function delMachineErrorCode($where)
    {
        return MachineErrorCodeModel::whereDel($where);
    }
}