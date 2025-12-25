<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:34
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\AppFactory;
use app\AppFactory\Kernel\Model\Machine\MachineLangModel;
use app\AppFactory\Kernel\Model\Machine\MachineModel;
use app\AppFactory\Kernel\Model\Auth\AuthManagerLogModel;
use app\AppFactory\Kernel\Model\Auth\AuthManagerModel;
use app\AppFactory\Kernel\Model\Wx\WxOfficialLoginModel;
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
     * @param int|array $pageNum
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
    public function getMachineList($where,$pageNum = null,$field = "*", $order = "",$eachFun = "",$group = '', $limit = '')
    {
        $result = MachineModel::getList($where,$pageNum,$field,$order,$eachFun,$group,$limit);

        if ($result) {
            if ($pageNum) {
                $result = $result->each(function ($item) {
                    $item['lang'] = MachineLangModel::getList(['m_id' => $item['m_id']]);
                    return $item;
                });
            } else {
                $result = $result->toArray();
                foreach ($result as $key => $value) {
                    $result[$key]['lang'] = MachineLangModel::getList(['m_id' => $value['m_id']]);
                }
            }
        }
        return $result;
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
        $update['m_id'] = $this->machine['m_id'];
        $update['last_online_time'] = time();
        $update['online'] = 1;
        if (isset($this->data["version"]) && $this->data['version']) $update['version'] = $this->data['version'];
        $result = $this->updateMachine($update);
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
     * 设备当前状态值上报
     * @return MachineModel|bool
     */
    public function currentStatus()
    {
        if ($this->message['current_status'] != $this->machine['current_status']) {
            $result = $this->updateMachine(['m_id' => $this->machine['m_id'],'current_status' => $this->message['current_status']]);
            actionLog($this->getLS(),'【SQL】修改设备当前状态值');
            return $result;
        }
        return true;
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
     * @var array
     * 休眠：sleep,
     * 唤醒：wakeUp,
     * 重启：reboot,
     * 关机：shutdown,
     * 软件升级：update，
     * 取货头回初始位：pickUpHeadInit，
     * 取货箱传送带开：conveyorBeltOpen，
     * 取货箱传送带关：conveyorBeltClose，
     * 取货箱开门：boxDoorOpen，
     * 取货箱关门：boxDoorClose，
     * 回收箱伸出：recycleOut，
     * 回收箱缩进：recycleIntro，
     * 重推第二货道出货口：reOutPort
     * 断电重启：powerWakeUp，
     * 远程初始化：initialization
     * 当前命令下发前需要检查一下current_status
     */
    protected $checkCurrentStatus = [
        // "sleep",
        // "wakeUp",
        "reboot",
        "shutdown",
        "update",
        "pickUpHeadInit",
        "conveyorBeltOpen",
        "conveyorBeltClose",
        "boxDoorOpen",
        "boxDoorClose",
        "recycleOut",
        "recycleIntro",
        "powerWakeUp",
        "initialization",
    ];

    /**
     * 发送触发数据
     * @param $machine
     * @param $msgType
     * @param array $otherData
     * @return array|bool|string
     */
    public function sendToMachine($machine,$msgType,$otherData = [])
    {
        $m = $this->getMachineFind(['machine_id' => $machine['machine_id']],"mac_address,signKey,online,current_status");
        if ($m) {
            $m = $m->toArray();
            if (in_array($msgType,$this->checkCurrentStatus) && $m['current_status'] != "normal")
                return $this->lang("current_status_not_normal");
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
                    $result =  $app->sendMq->sendMq($msgType, $otherData);
                    actionLog(@obj2arr($result),'sendToMachine结果');
                    return $result;
                }
                return $this->lang("VReceive.signKey_require");
            } else {
                return $this->lang("machine_offline");
            }
        }
        return false;
    }

    /**
     * 发送多设备触发数据
     * @param $machine
     * @param $msgType
     * @param array $otherData
     * @return array|bool|string
     */
    public function sendToArrMachine($machine,$msgType,$otherData = [])
    {

        $m = MachineModel::whereIn('machine_id',$machine['machine_id'])->field("machine_id,mac_address,signKey,online,current_status")->select();

        if ($m) {
            $m = $m->toArray();
            // 判断不在线且不处于空闲状态的设备并下发通知
            $unqualified = array_filter($m,function($v , $k){
                return $v['online']==2||$v['current_status']=!'normal';
            },ARRAY_FILTER_USE_BOTH);
            if(!empty($unqualified)){
                $unmachine = '';
                foreach($unqualified as $key=>$item){
                    if (count($unqualified) == $key+1)
                        $unmachine = $unmachine.$item['machine_id'];
                    $unmachine = $unmachine.$item['machine_id'].',';
                }
                return $this->rFail("设备 $unmachine 不处于空闲或在线状态，请确认设备状态后重新下发");
            }
            try {
                $result = '';
                foreach($m as $item){
                    $key = $item['signKey'] ?? "";
                    if (!$key) $key = env("api.md5Key");
                    if ($key) {
                        $config = [
                            "machine_id" => $item['machine_id'],
                            "key" => $key,
                            "mac" => $item['mac_address'] ?? "",
                        ];
                        actionLog($config, '下发命令配置');
                        $app = AppFactory::machine($config);
                        $result =  $app->sendMq->sendMq($msgType, $otherData);
                        actionLog(@obj2arr($result),'sendToMachine结果');
                    }
                }
                return $result;
            } catch (\Throwable $e) {
                return $this->lang("VReceive.signKey_require");
            }
        }
        return false;
    }


    /**
     * 获取设备开锁密码
     * @param $machine
     * @return string|bool
     */
    public function getPass($machine){
        try {
            $login = AuthManagerLogModel::where(['path'=>'/machine/receive/login'])->where('params','like','{"machine_id":"'.$machine.'%')->order(['create_time'=>'desc'])->field('manager_id,create_time')->find();
            $wxLogin = WxOfficialLoginModel::where(['machine_id'=>$machine])->order(['create_time'=>'desc'])->field('manager_id,create_time')->find();
            $login = $login ? $login->toArray() : [];
            $wxLogin = $wxLogin ? $wxLogin->toArray() : [];
            switch (empty($wxLogin)||is_null($wxLogin['manager_id'])){
                case false:
                        if($login['create_time']>$wxLogin['create_time']){
                            $manager_id = $login['manager_id'];
                        }else{
                            $manager_id = $wxLogin['manager_id'];
                        }
                    break;
                
                default:
                    $manager_id = $login['manager_id'];
                    break;
            }
            return AuthManagerModel::getFind(['manager_id'=>$manager_id],'account');
        } catch (\Throwable $th) {; 
            return false;
        }
    }

    /**
     * 设备正常、暂停营业状态记录日志
     * @return MachineModel
     */
    public function machineCkcOnOff()
    {
        $result = $this->updateMachine(['m_id' => $this->machine['m_id'],'ckc_status' => $this->message['ckc_status']]);
        actionLog($this->getLS(),'【SQL】修改设备营业状态','DataUpload');
        return $result;
    }
}