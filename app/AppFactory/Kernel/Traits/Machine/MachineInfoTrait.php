<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:38
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Machine\MachineInfoModel;

trait MachineInfoTrait
{
    /**
     * 获取设备信息字段值
     * @param $where
     * @param $value
     * @return mixed
     */
    public function getMachineInfoValue($where,$value)
    {
        return MachineInfoModel::getFieldValue($where,$value);
    }

    /**
     * 获取一条设备信息
     * @param $where
     * @param string $field
     * @param string $order
     * @return MachineInfoModel|array|mixed|null|\think\Model
     */
    public function getMachineInfoFind($where,$field = "*",$order = "")
    {
        return MachineInfoModel::getFind($where,$field,$order);
    }

    /**
     * 获取设备信息列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @param string $eachFun
     * @return \app\AppFactory\Kernel\Model\BaseModel|\app\AppFactory\Kernel\Model\BaseModel[]|array|\think\Collection|\think\Paginator
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getMachineInfoList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "")
    {
        return MachineInfoModel::getList($where,$pageNum,$field,$order,$eachFun);
    }

    /**
     * 添加设备信息
     * @param $insert
     * @return mixed
     */
    public function addMachineInfo($insert)
    {
        $data = MachineInfoModel::create($insert);
        return $data->mi_id;
    }

    /**
     * 修改设备信息
     * @param $update
     * @param array $where
     * @param array $field
     * @return MachineInfoModel
     */
    public function updateMachineInfo($update,$where = [],$field = [])
    {
        return MachineInfoModel::update($update,$where,$field);
    }

    /**
     * 删除设备信息
     * @param $where
     * @return bool
     */
    public function delMachineInfo($where)
    {
        $result = MachineInfoModel::whereDel($where);
        return $result;
    }

    /**
     * 设备上报
     * @return MachineInfoModel
     */
    public function img()
    {
        $result = $this->updateMachineInfo([$this->message['field'] => $this->message['path']],['machine_id' => $this->machine['machine_id']]);
        actionLog($this->getLS(),'【SQL】写入图片路径');
        return $result;
    }

    /**
     * 终端上报machine_info的数据
     * @return MachineInfoModel|bool
     */
    public function uploadInfo()
    {
        $fieldList = $this->getFieldComment("machine_info");
        $fields = array_column($fieldList,'Field');
        $update = [];
        $messageKey = array_keys($this->message);
        foreach ($messageKey as $value) {
            if (in_array($value,$fields)) {
                $update[$value] = $this->message[$value];
            }
        }
        if ($update) {
            return $this->updateMachineInfo($update,['machine_id' => $this->machine['machine_id']]);
        }
        return false;
    }
}