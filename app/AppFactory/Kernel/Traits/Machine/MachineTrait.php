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
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersTrait;
use app\AppFactory\Kernel\Model\RemoteActionLog\RemoteActionLogModel;
use app\AppFactory\Kernel\Traits\RemoteActionLog\RemoteActionLogTrait;
use think\facade\Db;
trait MachineTrait
{
    use SaleOrdersTrait, RemoteActionLogTrait;
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
        //$result = MachineModel::getList($where,$pageNum,$field,$order,$eachFun,$group,$limit);
        $result = MachineModel::getListAndWith($where,$pageNum,$field,$order,$eachFun,$group,$limit,['machineLevelData']);
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
     * 获取设备列表(关联货道)
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
    public function getMachineJoinChannelList($where,$pageNum = null,$field = "*", $order = "",$eachFun = "",$group = '', $limit = '',$with = [],$join = [])
    {
        $result = MachineModel::getListAndWith($where,$pageNum,$field,$order,$eachFun,$group,$limit,$with,$join);
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
     * 添加边柜信息
     * @param $insert
     * @return mixed
     */
    public function addSubMachine($insert)
    {
        !isset($this->manager['manager_id']) ? :$insert['creator'] = $this->manager['manager_id'];
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
        actionLog($this->message['version'] ?? '内层未知版本','心跳版本测试校验-'.$this->machine['machine_id'] ?? '未知设备','heartbeat');
        actionLog($this->data['version'] ?? '外层未知版本','心跳版本测试校验-'.$this->machine['machine_id'] ?? '未知设备','heartbeatv2');
        if (isset($this->data["version"]) && $this->data['version']) $update['version'] = $this->data['version'];
        $result = $this->updateMachine($update);

        // 心跳后触发上线补发更新版本检查、兼容发布时间配置。
        $this->resendUpdateVersionPlanWhenOnline();

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
     * 先注释，操作时注意设备是否在线
     */
    protected $checkCurrentStatus = [
        // "sleep",
        // "wakeUp",
        // "reboot",
        // "shutdown",
        // "update",
        // "pickUpHeadInit",
        // "conveyorBeltOpen",
        // "conveyorBeltClose",
        // "boxDoorOpen",
        // "boxDoorClose",
        // "recycleOut",
        // "recycleIntro",
        // "powerWakeUp",
        // "initialization",
        // "outGoods",
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

    /**
     * 设备远程出货
     * @return array|bool|string
     */
    public function setRemoteOutGoods()
    {
        $sod_id = input('sod_id');
        $machine_id = input('machine_id');
        $channel_code = input('channel_code') ?? '';
        $detail = $this->getSaleOrdersDetailsFind(['sod_id' => $sod_id]);
        if (!$detail) return $this->r(100,"找不到订单记录");
        $order = $this->getSaleOrdersFind(['order_id' => $detail['order_id']]);
        // 先不做判断
        // if (!$channel_code){
        //     if ($detail['fail_quantity'] == 0) return "出货失败商品数量为0，无需操作";
        // }
        try{
            $this->startTrans();
            $contentArr = [];
            $outArr = [];
            if ($detail['g_type'] != 1 && isset($detail['gmg_id']) && $detail['gmg_id']) {
                $flag[] = $this->setGoodsMultipleGoodsDec(['gmg_id' => $detail['gmg_id']],'stock');
                actionLog($this->getLS(),'减固定组合商品酒店库存');
            }
            if ($detail['g_type'] == 1) {
                $outArr[$detail['channel_position']][] = [
                    "channel_code" => $channel_code ?: $detail['channel_code'],
                    "quantity" => 1,
                    "is_gift" => $detail['is_gift'] ?? 2,
                    "out_port" => $detail['out_port'] ?? 1,
                ];
            }
            if ($detail['g_type'] == 3) {
                $updateSod['sod_id'] = $detail['sod_id'];
                // 获取核销码
                $updateSod['checkOff_code'] = $this->getDetailsCheckOffCode();
                $this->updateSaleOrdersDetails($updateSod);
            }
            $logData['machine_id'] = $order['machine_id'];
            $logData['type'] = "remoteOutGoods";
            $logData['order_id'] = $detail["order_id"];
            $logData['sod_id'] = $detail["sod_id"];
            $logData['order_id'] = $detail["order_id"];
            $logData['goods_id'] = $detail["g_id"];
            $logData['channel_code'] = $channel_code ?: $detail['channel_code'];
            $logData['status'] = 1;
            $logData['manager_id'] = $this->manager['manager_id'];
            $logData['operator_at'] = date('Y-m-d H:i:s');
            $log_id = $this->addRALog($logData);
            $content = [
                "trade_no" => $order['trade_no'],
                'sod_id' => $detail['sod_id'],
                "main" => $contentArr,
                "outGoods" => $outArr,
                "log_id" => $log_id,
            ];
            $result = $this->sendToMachine(['machine_id' => $machine_id], 'remoteOutGoods', $content);
            //修改订单子表出货成功+1  出货失败-1   remote_out_goods_status = 1  订单状态
            $updateData['sod_id'] = $detail['sod_id'];
            // $updateData['success_quantity'] = $detail['success_quantity'] + 1;
            // $updateData['fail_quantity'] = $detail['fail_quantity'] - 1;
            $updateData['remote_out_goods_status'] = 1;
            $this->updateSaleOrdersDetails($updateData);
            $this->commitTrans();
        }catch(\Exception $e){
            $this->rollBackTrans();
            actionLog($e,'远程出货异常：');
            return $e->getMessage();
        }
        
        return $result;
    }


    public function remoteOutGoods(){
        actionLog($this->message, "远程出货接收mq");
        RemoteActionLogModel::update(['status' => $this->message['status']], ['id' => $this->message['log_id']]);
        return $this->updateSaleOrdersDetails(['sod_id' => $this->message['sod_id'], 'remote_out_goods_status' => $this->message['status']]);
    }

    // public function checkRecycleBox(){
    //     $machine_id = input("machine_id");
    //     return $this->updateMachine([
    //         'machine_id' => $machine_id,
    //         'recycle_box_total_capacity' => $this->message['recycle_box_total_capacity'],
    //         'recycle_box_remain_capacity' => $this->message['recycle_box_remain_capacity'],
    //     ]);
    // }

    // public function takePhotos(){
    //     return $this->updateSaleOrdersDetails([
    //         'sod_id' => input("sod_id"),
    //         'refund_photo' => $this->message['refund_photo'],
    //     ]);
    // }

        /**
     * 设备上线时补发更新版本 MQ（仅离线->上线触发一次）
     */
    protected function resendUpdateVersionPlanWhenOnline()
    {
        try {
            $now = time();
            $checkKey = 'machine.updateVersionPlan.check.' . $this->machine['machine_id'];
            $checkCoolDown = 120;

            // 心跳兜底时限频检查，避免每次心跳都查数据库。
            $lastCheckTime = cache($checkKey);
            if ($lastCheckTime && ($now - $lastCheckTime < $checkCoolDown)) {
                return;
            }
            cache($checkKey, $now, $checkCoolDown);
            //create_time大于此功能上线的时间，避免历史数据上线时被补发。2026-04-15
            $plan = Db::name('machine_version_plan')->where([
                'machine_id' => $this->machine['machine_id'],
                'status' => 1,
            ])->where('publish_time', '<=', $now)
            ->where('create_time', '>', 1776219898)
            ->field('mvp_id,machine_id,mv_id,version_no,publish_time')
            ->order('publish_time asc,mvp_id asc')
            ->find();
            actionLog($plan, '上线补发更新版本MQ检查结果');
            if (empty($plan)) {
                return;
            }
            $sendResult = $this->sendToMachine(
                ['machine_id' => $this->machine['machine_id']],
                'updateVersionPlan'
            );
            actionLog([
                'machine_id' => $this->machine['machine_id'],
                'mvp_id' => $plan['mvp_id'] ?? 0,
                'sendResult' => is_object($sendResult) ? obj2arr($sendResult) : $sendResult,
            ], '设备上线补发更新版本MQ');
        } catch (\Throwable $e) {
            actionException($e, 1);
        }
    }
}