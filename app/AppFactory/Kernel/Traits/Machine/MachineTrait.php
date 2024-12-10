<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:34
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\AppFactory;
use app\AppFactory\Kernel\Model\Machine\MachineModel;

trait MachineTrait
{
    /**
     * 获取设备指定列数据
     * @param $where
     * @param $column
     * @return array
     */
    public function getMachineColumn($where,$column)
    {
        return MachineModel::getColumn($where,$column);
    }

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
        !isset($this->manager['ao_id']) ? : $insert['ao_id'] = $this->manager['ao_id'];
        $data = MachineModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
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

    /**
     * 整理设备国家、州/省、城市、区域、街道、楼层等位置信息
     */
    public function getMachineAddress()
    {
        $item = $this->machine;
        $item['country'] = "";
        $item['state'] = "";
        $item['city'] = "";
        $item['regions'] = "";
        if (isset($item['country_id']) && $item['country_id']) $item['country'] = $this->getEarthCountriesFind(['id' => $item['country_id']],'code,name,cname');
        if (isset($item['state_id']) && $item['state_id']) $item['state'] = $this->getEarthStatesFind(['id' => $item['state_id']],'code,name,cname');
        if (isset($item['city_id']) && $item['city_id']) $item['city'] = $this->getEarthCitiesFind(['id' => $item['city_id']],'code,name,cname');
        if (isset($item['regions_id']) && $item['regions_id']) $item['regions'] = $this->getEarthRegionsFind(['id' => $item['regions_id']],'code,name,cname');
        $address = [$item['country'],$item['state'] , $item['city'], $item['regions'] , ($this->machine['street'] ?? "无街道"), ($this->machine['floor'] ?? "无楼层")];
        $this->machine = $item;
        $this->machine['address'] = implode(",",$address) ?? "";
    }

    /**
     * 获取设备的加签密钥
     * @param $machine
     * @return mixed
     */
    public function getMachineSignKey($machine)
    {
        $signKey = cache($machine['machine_id'] . ".signKey");
        $signKey ? : $signKey = (MachineModel::getFieldValue(['machine_id' => $machine['machine_id']],"signKey") ?? env("api.md5Key"));
        return $signKey;
    }

    /**
     * 发送触发数据
     * @param $machine
     * @param $msgType
     * @param array $otherData
     * @return array|bool|string
     */
    public function sendToMachine($machine,$msgType,$otherData = [])
    {
        $m = $this->getMachineFind(['machine_id' => $machine['machine_id']],"mac_address,signKey,online")->toArray();
        if ($m['online'] == 1) {
            $key = $m['signKey'] ?? "";
            if (!$key) $key = env("api.md5Key");
            if ($key) {
                $config = [
                    "machine_id" => $machine['machine_id'],
                    "key" => $key,
                    "mac" => $m['mac_address'] ?? "",
                ];
                actionLog($config, '下发命令配置');
                $app = AppFactory::machine($config);
                return $app->sendMq->sendMq($msgType, $otherData);
            }
        }
        return false;
    }
}