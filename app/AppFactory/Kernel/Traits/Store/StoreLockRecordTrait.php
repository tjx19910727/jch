<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/9/21
 * Time: 9:16
 */

namespace app\AppFactory\Kernel\Traits\Store;


use app\AppFactory\Kernel\Model\Store\StoreLockRecordModel;

trait StoreLockRecordTrait
{
    public function getStoreLockRecordFind($where,$field = "*", $order = "")
    {
        return StoreLockRecordModel::getFind($where,$field,$order);
    }

    public function getStoreLockRecordList($where,$pageNum = 0, $field = "*", $order = "")
    {
        return StoreLockRecordModel::getList($where,$pageNum,$field,$order);
    }

    public function addStoreLockRecord($insert)
    {
        $slr = StoreLockRecordModel::create($insert);
        return $slr->sl_id;
    }

    public function updateStoreLockRecord($update,$where = [], $field = [])
    {
        return StoreLockRecordModel::update($update,$where,$field);
    }

    /**
     * 下发添加发送开关锁记录
     * @param $data
     * @return mixed
     */
    public function addSendLockRecord($data)
    {
        $store = $this->getStoreFind(['terminal_no' => $data['terminal_no']]);
        $store = obj2arr($store);
        if (!$store) return $this->r(100,'查无门店');
        $sh = $this->getStoreHardwareFind(['store_id' => $store['store_id'],'hardware_type' => 1,"status" => 1],'*','sh_id desc');
        $sh = obj2arr($sh);
        if (!$sh) return $this->r(100,'查无硬件信息');
        $insert = [
            "store_id" => $store['store_id'],
            "store_name" => $store['store_name'],
            "terminal_no" => $store['terminal_no'],
            "sh_id" => $sh['sh_id'],
            "hardware_number" => $sh['hardware_number'],
            "send_type" => $data['send_type'] ?? 1,
            "type" => !isset($data["cmd"]) ? 1 : ($data['cmd'] == "unlock" ? 1 : 2),
            "uid" => $data['uid'] ?? 0,
        ];
        $result = $this->addStoreLockRecord($insert);
        if ($result) {

            return $this->r(200,'生成开锁记录成功',$result);
        }
        return $this->r(100,'生成开锁记录失败');
    }

    /**
     * 锁状态上报
     * message = [
     *      "terminal_no" => "",  设备编号
     *      "msgType" => "lockStatusReport",      消息类型
     *      "data" => [
     *          "type" => 1,           状态，1：开锁，2：上锁
     *          "status" => 1,           状态，1：成功，2：失败
     *      ],
     * ]
     * @return mixed
     */
    public function lockStatusReport()
    {
        actionLog($this->message,'接收上报数据');
        $store = $this->getStoreFind(['terminal_no' => $this->message['terminal_no']]);
        $store = obj2arr($store);
        if (!$store) return $this->r(100,"查无门店信息");
        $updateStore['store_id'] = $store['store_id'];
        $updateStore['lock_status'] = 1;

        $where['terminal_no'] = $this->message['terminal_no'];
        $where['status'] = 3;
        $record = $this->getStoreLockRecordFind($where,'*','sl_id asc');
        // 查询未确定锁记录，有记录则修改记录锁状态
        if ($record) {
            $record = obj2arr($record);
            if ($record['type'] == 2) $updateStore['lock_status'] = 2;
            $update['sl_id'] = $record['sl_id'];
            $update['status'] = $this->message['data']['status'];
            $result = $this->updateStoreLockRecord($update);
            $return = $this->rAction($result);
        } else {
            // 无未确定锁记录时，查上报的硬件是否存在
            $sh = $this->getStoreHardwareFind(['store_id' => $store['store_id'],'hardware_type' => 1,'status' => 1],'*','sh_id desc');
            if ($sh) {
                $sh = obj2arr($sh);
                $insert = [
                    "store_id" => $store['store_id'],
                    "store_name" => $store['store_name'],
                    "terminal_no" => $store['terminal_no'],
                    "sh_id" => $sh['sh_id'],
                    "hardware_number" => $sh['hardware_number'],
                    "type" => $this->message['data']['type'],
                    "status" => $this->message['data']['status'],
                ];
                actionLog($insert,'生成门店锁记录数据');
                $sl_id = $this->addStoreLockRecord($insert);
                if ($this->message['data']['status'] == 1) $updateStore['lock_status'] = $this->message['data']['type'];
                $return = $sl_id ? $this->r(200,'锁状态记录成功',['sl_id' => $sl_id]) : $this->r(100,'生成记录失败');
            } else {
                $return = $this->r(100, '查无硬件信息');
            }
        }
        actionLog($updateStore,'门店修改锁状态');
        $this->updateStore($updateStore);
        return $return;
    }



    /**
     * 添加未确定门锁记录
     * @param $postData
     * @return mixed
     */
    protected function addLockRecord($postData)
    {
        $lockRecord['terminal_no'] = $postData['terminal_no'];
        $lockRecord['send_type'] = $postData['send_type'];
        $lockRecord['cmd'] = $postData['cmd'];
        $lockRecord['uid'] = $postData['uid'];
        $record = $this->addSendLockRecord($lockRecord);
        return $record;
    }

}