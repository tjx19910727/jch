<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:34
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Machine\MachineModel;

trait MachineTrait
{
    /**
     * 获取设备字段数值
     * @param $where
     * @param $value
     * @return mixed
     */
    public function getMachineValue($where,$value)
    {
        return MachineModel::getFieldValue($where,$value);
    }

    public function getMachineCount($where)
    {
        return MachineModel::getCount($where);
    }

    /**
     * 增加设备某字段的值
     * @param $where
     * @param $field
     * @param int $inc
     * @return mixed
     */
    public function setMachineIncField($where,$field,$inc = 1)
    {
        return MachineModel::setInc($where,$field,$inc);
    }

    /**
     * 减少设备某字段的值
     * @param $where
     * @param $field
     * @param int $dec
     * @return mixed
     */
    public function setMachineDecField($where,$field,$dec = 1)
    {
        return MachineModel::setDec($where,$field,$dec);
    }

    /**
     * 获取一条设备信息
     * @param $where
     * @param string $field
     * @param string $order
     * @return MachineModel|array|mixed|null|\think\Model
     */
    public function getMachineFind($where,$field = "*",$order = "")
    {
        return MachineModel::getFind($where,$field,$order);
    }

    /**
     * 获取设备列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @param string $eachFun
     * @param string $group
     * @param string $limit
     * @return \app\AppFactory\Kernel\Model\BaseModel|\app\AppFactory\Kernel\Model\BaseModel[]|array|\think\Collection|\think\Paginator
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getMachineList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "",$group = '', $limit = '')
    {
        return MachineModel::getList($where,$pageNum,$field,$order,$eachFun,$group,$limit);
    }

    /**
     * 添加设备信息
     * @param $insert
     * @return mixed
     */
    public function addMachine($insert)
    {
        !isset($this->manager['manager_id']) ? :$insert['creator'] = $this->manager['manager_id'];
        $data = MachineModel::create($insert);
        return $data->m_id;
    }

    /**
     * 修改设备信息
     * @param $update
     * @param array $where
     * @param array $field
     * @return MachineModel
     */
    public function updateMachine($update,$where = [],$field = [])
    {
        !isset($this->manager['manager_id']) ? : $update['update_id'] = $this->manager['manager_id'];
        return MachineModel::update($update,$where,$field);
    }

    /**
     * 删除设备信息
     * @param $where
     * @return bool
     */
    public function delMachine($where)
    {
        $result = MachineModel::whereDel($where);
        return $result;
    }

    /**
     * RabbitMQ 设备心跳
     * @return MachineModel
     */
    public function heartbeat()
    {
        $result = $this->updateMachine(['m_id' => $this->machine['m_id'],'last_online_time' => time(),'online' => 1]);
//        actionLog($this->getLS(),'【SQL】修改设备上线时间','DataUpload');
        return $result;
    }

    /**
     * 设备灯光亮度上报
     * @return MachineModel
     */
    public function light()
    {
        $result = $this->updateMachine(['m_id' => $this->machine['m_id'],'light' => $this->message['value']]);
        actionLog($this->getLS(),'【SQL】修改设备灯光亮度','DataUpload');
        return $result;
    }

    /**
     * 设备音量上报
     * @return MachineModel
     */
    public function volume()
    {
        $result = $this->updateMachine(['m_id' => $this->machine['m_id'],'volume' => $this->message['value']]);
        actionLog($this->getLS(),'【SQL】修改设备音量','DataUpload');
        return $result;
    }
}