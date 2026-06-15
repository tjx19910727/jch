<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/5/4
 * Time: 10:01
 */

namespace app\AppFactory\Kernel\Traits\Machine;

use app\AppFactory\Kernel\Model\Machine\MachineServiceLogModel;

/**
 * @property array $machine
 */
trait MachineServiceLogTrait
{
    public function getMachineServiceLogValue($where, $value, $order = "")
    {
        return MachineServiceLogModel::getFieldValue($where, $value, $order);
    }

    public function getMachineServiceLogCount($where)
    {
        return MachineServiceLogModel::getCount($where);
    }

    public function getMachineServiceLogFind($where, $field = "*", $order = "")
    {
        return MachineServiceLogModel::getFind($where, $field, $order);
    }

    public function getMachineServiceLogList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "")
    {
        return MachineServiceLogModel::getList($where, $pageNum, $field, $order, $eachFun);
    }

    public function addMachineServiceLog($insert)
    {
        if (!isset($insert['create_time'])) {
            $insert['create_time'] = time();
        }
        $data = MachineServiceLogModel::create($insert);
        return $data->id;
    }

    public function updateMachineServiceLog($update, $where = [], $field = [])
    {
        return MachineServiceLogModel::update($update, $where, $field);
    }

    public function delMachineServiceLog($where)
    {
        return MachineServiceLogModel::whereDel($where);
    }

    /**
     * 设备上报运行日志文件
     * msgType=machineServiceLog
     */
    public function machineServiceLog()
    {
        $path = $this->message['path'] ?? '';
        $date = $this->message['date'] ?? date('Y-m-d');
        $name = $this->message['name'] ?? ($path ? basename($path) : '');

        if (!$name) {
            $name = $this->machine['machine_id'] . '_' . date('YmdHis') . '.log';
        }

        $insert = [
            'm_id' => $this->machine['m_id'],
            'machine_id' => $this->machine['machine_id'],
            'name' => $name,
            'path' => $path,
            'date' => $date,
            'remark' => $this->message['remark'] ?? '',
            'create_time' => time(),
        ];

        $exists = $this->getMachineServiceLogFind([
            'm_id' => $this->machine['m_id'],
            'date' => $date,
        ], 'id','id desc');

        if ($exists) {
            $this->updateMachineServiceLog($insert,['id' => $exists['id']]);
            return 1;
        }

        $this->addMachineServiceLog($insert);
        return 1;
    }
}
