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
use app\AppFactory\Kernel\Traits\RemoteRemovalLog\RemoteRemovalLogTrait;
use think\facade\Db;
trait MachineTrait
{
    use SaleOrdersTrait, RemoteActionLogTrait, RemoteRemovalLogTrait,MachineChannelTrait;
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
    public function getMachineFind($where,$field = "*",$order = "",$with = [])
    {
        if ($with) {
            return MachineModel::with($with)->where($where)->field($field)->order($order)->find();
        }
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
        $result = MachineModel::getListAndWith($where,$pageNum,$field,$order,$eachFun,$group,$limit,['machineLevelData','simSignalLog' => function ($query) {
                $query->whereTime('created_at', 'today')->order('id desc');
            },]);
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
            $update = ['m_id' => $this->machine['m_id'],'current_status' => $this->message['current_status']];
            if($this->message['current_status'] == 'maintenance'){
                $update['ckc_status'] = 2;
            }elseif($this->message['current_status'] == 'normal'){
                $update['ckc_status'] = 1;
            }
            $result = $this->updateMachine($update);
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
        if (!$machine_id) {
            return $this->r(100, $this->lang("VMachine.machine_id_require"));
        }

        if (!$sod_id) {
            try {
                $this->startTrans();
                $nowStr = date("YmdHis");
                $main = input('main') ?? [];
                $outGoods = input('outGoods') ?? [];
                $remoteOutGoodsItem = $this->extractFirstRemoteOutGoodsItem([
                    'channel_code' => $channel_code,
                    'channel_position' => input('channel_position') ?? 1,
                    'quantity' => input('quantity') ?? 1,
                    'is_gift' => input('is_gift') ?? 2,
                    'out_port' => input('out_port') ?? 1,
                    'main' => $main,
                    'outGoods' => $outGoods,
                ]);
                if (!$channel_code) {
                    $channel_code = $remoteOutGoodsItem['channel_code'] ?? '';
                }
                $payload = $this->buildRemoteOutGoodsPayload([
                    'machine_id' => $machine_id,
                    'order_id' => $nowStr,
                    'sod_id' => 0,
                    'goods_id' => intval(input('goods_id') ?? input('g_id') ?? 0),
                    'channel_code' => $channel_code,
                    'trade_no' => input('trade_no') ?: '',
                    'main' => $main,
                    'outGoods' => $outGoods,
                    'quantity' => intval($remoteOutGoodsItem['quantity'] ?? input('quantity') ?? 1),
                    'channel_position' => intval($remoteOutGoodsItem['channel_position'] ?? input('channel_position') ?? 1),
                    'is_gift' => intval($remoteOutGoodsItem['is_gift'] ?? input('is_gift') ?? 2),
                    'out_port' => intval($remoteOutGoodsItem['out_port'] ?? input('out_port') ?? 1),
                ]);
                $result = $this->sendRemoteOutGoodsWithLog($machine_id, $payload);
                $this->commitTrans();
                return $result;
            } catch (\Exception $e) {
                $this->rollbackTrans();
                actionLog($e, '远程出货无sod_id异常：');
                return $e->getMessage();
            }
        }

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
            $payload = $this->buildRemoteOutGoodsPayload([
                'machine_id' => $order['machine_id'],
                'order_id' => $detail['order_id'],
                'sod_id' => $detail['sod_id'],
                'goods_id' => $detail['g_id'],
                'channel_code' => $channel_code ?: $detail['channel_code'],
                "trade_no" => $order['trade_no'],
                'main' => $contentArr,
                'outGoods' => $outArr,
                'quantity' => 1,
            ]);
            $result = $this->sendRemoteOutGoodsWithLog($machine_id, $payload);
            // 远程出货发起时仅记录子订单远程出货状态，实际成功/失败结果由MQ回执处理
            $updateData['sod_id'] = $detail['sod_id'];
            $updateData['remote_out_goods_status'] = 1;
            $this->updateSaleOrdersDetails($updateData);
            $this->commitTrans();
        }catch(\Exception $e){
            $this->rollbackTrans();
            actionLog($e,'远程出货异常：');
            return $e->getMessage();
        }
        
        return $result;
    }

    protected function buildRemoteOutGoodsPayload(array $data): array
    {
        $channelCode = trim((string)($data['channel_code'] ?? ''));
        $remoteOutGoodsItem = $this->extractFirstRemoteOutGoodsItem($data);
        if ($channelCode === '' && !empty($remoteOutGoodsItem['channel_code'])) {
            $channelCode = $remoteOutGoodsItem['channel_code'];
        }
        $quantity = intval($data['quantity'] ?? 1);
        if ($quantity <= 0 && isset($remoteOutGoodsItem['quantity'])) {
            $quantity = intval($remoteOutGoodsItem['quantity']);
        }
        if ($quantity <= 0) {
            $quantity = 1;
        }

        $outGoods = $this->normalizeRemoteOutGoodsPayload($data['outGoods'] ?? []);
        if (!$outGoods && $channelCode !== '') {
            $channelPosition = intval($remoteOutGoodsItem['channel_position'] ?? $data['channel_position'] ?? 1);
            if ($channelPosition <= 0) {
                $channelPosition = 1;
            }
            $outGoods[$channelPosition][] = [
                'channel_code' => $channelCode,
                'quantity' => $quantity,
                'is_gift' => intval($remoteOutGoodsItem['is_gift'] ?? $data['is_gift'] ?? 2),
                'out_port' => intval($remoteOutGoodsItem['out_port'] ?? $data['out_port'] ?? 1),
            ];
        }

        return [
            'machine_id' => $data['machine_id'] ?? '',
            'order_id' => intval($data['order_id'] ?? 0),
            'sod_id' => intval($data['sod_id'] ?? 0),
            'goods_id' => intval($data['goods_id'] ?? 0),
            'channel_code' => $channelCode,
            'trade_no' => $data['trade_no'] ?? '',
            'main' => $data['main'] ?? [],
            'outGoods' => $outGoods,
            'quantity' => $quantity,
        ];
    }

    protected function normalizeRemoteOutGoodsPayload($value): array
    {
        if (is_string($value) && $value !== '') {
            $value = json2arr($value);
        }
        if (!is_array($value)) {
            return [];
        }
        return $value;
    }

    protected function extractFirstRemoteOutGoodsItem(array $data): array
    {
        $channelCode = trim((string)($data['channel_code'] ?? ''));
        $channelPosition = intval($data['channel_position'] ?? 0);
        $quantity = intval($data['quantity'] ?? 0);
        $isGift = intval($data['is_gift'] ?? 0);
        $outPort = intval($data['out_port'] ?? 0);

        foreach (['outGoods', 'main'] as $field) {
            $payload = $this->normalizeRemoteOutGoodsPayload($data[$field] ?? []);
            if (isset($payload['channel_code'])) {
                $payload = [intval($data['channel_position'] ?? 1) => [$payload]];
            } elseif (isset($payload[0]) && is_array($payload[0]) && (isset($payload[0]['channel_code']) || isset($payload[0][0]))) {
                $payload = [intval($data['channel_position'] ?? 1) => $payload];
            }
            foreach ($payload as $position => $items) {
                if (!is_array($items)) {
                    continue;
                }
                if (isset($items['channel_code']) || isset($items[0])) {
                    $items = [$items];
                }
                foreach ($items as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    if (!$channelPosition) {
                        $channelPosition = intval($position);
                    }
                    if ($channelCode === '') {
                        $channelCode = trim((string)($item['channel_code'] ?? ($item[0] ?? '')));
                    }
                    if ($quantity <= 0) {
                        $quantity = intval($item['quantity'] ?? ($item['success_quantity'] ?? ($item[1] ?? 0)));
                    }
                    if ($isGift <= 0 && isset($item['is_gift'])) {
                        $isGift = intval($item['is_gift']);
                    }
                    if ($outPort <= 0 && isset($item['out_port'])) {
                        $outPort = intval($item['out_port']);
                    }
                    if ($channelCode !== '') {
                        return [
                            'channel_code' => $channelCode,
                            'channel_position' => $channelPosition > 0 ? $channelPosition : 1,
                            'quantity' => $quantity > 0 ? $quantity : 1,
                            'is_gift' => $isGift > 0 ? $isGift : 2,
                            'out_port' => $outPort > 0 ? $outPort : 1,
                        ];
                    }
                }
            }
        }

        return [
            'channel_code' => $channelCode,
            'channel_position' => $channelPosition > 0 ? $channelPosition : 1,
            'quantity' => $quantity > 0 ? $quantity : 1,
            'is_gift' => $isGift > 0 ? $isGift : 2,
            'out_port' => $outPort > 0 ? $outPort : 1,
        ];
    }

    protected function sendRemoteOutGoodsWithLog(string $machineId, array $payload, array $logData = [])
    {
        $logData = array_merge([
            'machine_id' => $payload['machine_id'] ?: $machineId,
            'type' => 'remoteOutGoods',
            'order_id' => $payload['order_id'],
            'sod_id' => $payload['sod_id'],
            'goods_id' => $payload['goods_id'],
            'channel_code' => $payload['channel_code'],
            'status' => 1,
            'manager_id' => $this->manager['manager_id'] ?? 0,
            'operator_at' => date('Y-m-d H:i:s'),
        ], $logData);
        $logId = $this->addRALog($logData);

        $isWithoutOrder = intval($payload['sod_id']) <= 0;
        $videoKey = $isWithoutOrder ? ('remote_out_goods_log_' . $logId) : '';

        $content = [
            'trade_no' => $payload['trade_no'] ?: ($videoKey ?: ('remote_out_goods_' . $logId)),
            'sod_id' => $payload['sod_id'],
            'main' => $payload['main'],
            'outGoods' => $payload['outGoods'],
            'channel_code' => $payload['channel_code'],
            'quantity' => $payload['quantity'],
            'log_id' => $logId,
            'manager_id' => $this->manager['manager_id'] ?? 0,
        ];
        if ($videoKey !== '') {
            // 无订单远程出货视频按动作日志唯一归属，旧设备会忽略新字段。
            $content['video_scene'] = 'remote_action_log';
            $content['video_key'] = $videoKey;
        }
        $result = $this->sendToMachine(['machine_id' => $machineId], 'remoteOutGoods', $content);
        if (!is_object($result)) {
            $this->updateRALog(
                ['status' => 4, 'operator_at' => date('Y-m-d H:i:s')],
                ['id' => $logId],
                ['status', 'operator_at']
            );
        }
        return $result;
    }

    /**
     * 远程出货步骤集，按 machine_level + 是否挂件 分组
     * key = English步骤键, value = 中文描述
     * 2_1: kalos_hang (machine_level=2, 挂件版)
     * 2_0: kalos_normal (machine_level=2, 普通版)
     * 1_1: kalosV3_hang (machine_level=1, 挂件版)
     * 1_0: kalosV3_normal (machine_level=1, 普通版)
     * @return array
     */
    protected function getRemoteOutGoodsStepSets()
    {
        return [
            // ========== kalos_hang (machine_level=2, 挂件版) 31步 ==========
            '2_1' => [
                'check_discharge_box'              => '检测出料箱',
                'start_channel_discharge'          => '开始货道出货',
                'discharge_box_zero_to_standby'    => '出料箱回零并到待机位',
                'discharge_box_recycle_close_door' => '出料箱回收并关闭取货门',
                'z_axis_to_standby'                => 'Z轴回待机位',
                'xy_axis_to_channel'               => 'XY轴移动到货道',
                'z_axis_extend_to_pickup'          => 'Z轴伸出到取货位',
                'suction_nozzle_open'              => '吸嘴打开',
                'check_suction_pressure'           => '检测吸附压力',
                'z_axis_to_suction_check'          => 'Z轴移动到吸附检测位',
                'second_check_suction'             => '二次检测吸附',
                'hang_z_axis_lift'                 => '挂件Z轴抬升',
                'xy_axis_to_hang_discharge_box'    => 'XY轴移动到挂件出料箱上方',
                'discharge_box_to_hang_position'   => '出料箱移动到挂件出货位',
                'z_axis_descend_to_hang_drop'      => 'Z轴下放到挂件落料位',
                'discharge_belt_reverse'           => '出料履带反转',
                'hang_descend_to_drop_height'      => '挂件下降到落料高度',
                'discharge_belt_stop'              => '出料履带停止',
                'discharge_box_to_origin'          => '出料箱回原点',
                'z_axis_to_hang_safe'              => 'Z轴回挂件安全位',
                'suction_nozzle_close'             => '吸嘴关闭',
                'z_axis_final_to_standby'          => 'Z轴最终回待机位',
                'xy_axis_to_pickup_safe'           => 'XY轴移动到取货安全位',
                'output_box_extend'                => '出货箱伸出',
                'pickup_door_open'                 => '取货门打开',
                'pickup_light_on'                  => '取货灯打开',
                'check_pickup_complete'            => '检测取货完成',
                'pickup_light_off'                 => '取货灯关闭',
                'pickup_door_close'                => '取货门关闭',
                'check_discharge_box_remaining'    => '检测出料箱剩余商品',
                'output_box_to_standby'            => '出货箱回待机位',
            ],
            // ========== kalos_normal (machine_level=2, 普通版) 29步 ==========
            '2_0' => [
                'check_discharge_box'              => '检测出料箱',
                'start_channel_discharge'          => '开始货道出货',
                'discharge_box_zero_to_standby'    => '出料箱回零并到待机位',
                'discharge_box_recycle_close_door' => '出料箱回收并关闭取货门',
                'z_axis_to_standby'                => 'Z轴回待机位',
                'xy_axis_to_channel'               => 'XY轴移动到货道',
                'z_axis_extend_to_pickup'          => 'Z轴伸出到取货位',
                'suction_nozzle_open'              => '吸嘴打开',
                'check_suction_pressure'           => '检测吸附压力',
                'z_axis_to_suction_check'          => 'Z轴移动到吸附检测位',
                'second_check_suction'             => '二次检测吸附',
                'z_axis_to_zero'                   => 'Z轴回零',
                'xy_axis_to_discharge_box'         => 'XY轴移动到出料箱上方',
                'discharge_belt_reverse'           => '出料履带反转',
                'check_discharge_belt_sensor'      => '检测出料履带传感器',
                'z_axis_descend_to_drop'           => 'Z轴下放到落料位',
                'suction_nozzle_close'             => '吸嘴关闭',
                'z_axis_to_standby_2'              => 'Z轴回待机位',
                'discharge_belt_stop'              => '出料履带停止',
                'z_axis_final_to_standby'          => 'Z轴最终回待机位',
                'xy_axis_to_pickup_safe'           => 'XY轴移动到取货安全位',
                'output_box_extend'                => '出货箱伸出',
                'pickup_door_open'                 => '取货门打开',
                'pickup_light_on'                  => '取货灯打开',
                'check_pickup_complete'            => '检测取货完成',
                'pickup_light_off'                 => '取货灯关闭',
                'pickup_door_close'                => '取货门关闭',
                'check_discharge_box_remaining'    => '检测出料箱剩余商品',
                'output_box_to_standby'            => '出货箱回待机位',
            ],
            // ========== kalosV3_hang (machine_level=1, 挂件版) 22步 ==========
            '1_1' => [
                'check_discharge_box'              => '检测出料箱',
                'start_channel_discharge'          => '开始货道出货',
                'pickup_door_close'                => '取货门关闭',
                'cover_door_close'                 => '罩门关闭',
                'xy_axis_to_channel'               => 'XY轴移动到货道',
                'x_axis_to_channel'                => 'X轴移动到货道',
                'y_axis_to_channel'                => 'Y轴移动到货道',
                'discharge_belt_early_start'       => '出料履带提前启动',
                'channel_motor_discharge'          => '货道电机出货',
                'xy_axis_to_safe'                  => 'XY轴回安全位',
                'd0_linkage_discharge'             => 'D0联动出货',
                'd0_belt_fallback_start'           => 'D0履带兜底启动',
                'd0_discharge_belt_stop'           => 'D0出料履带停止',
                'discharge_belt_start'             => '出料履带启动',
                'x_axis_to_discharge'              => 'X轴移动到出货位',
                'discharge_belt_stop'              => '出料履带停止',
                'x_axis_to_pickup'                 => 'X轴移动到取货位',
                'cover_door_open'                  => '罩门打开',
                'y_axis_to_pickup_height'          => 'Y轴移动到取货高度',
                'pickup_door_open'                 => '取货门打开',
                'check_pickup_complete'            => '检测取货完成',
                'y_axis_to_safe_height'            => 'Y轴回安全高度',
            ],
            // ========== kalosV3_normal (machine_level=1, 普通版) 22步 ==========
            '1_0' => [
                'check_discharge_box'              => '检测出料箱',
                'start_channel_discharge'          => '开始货道出货',
                'pickup_door_close'                => '取货门关闭',
                'cover_door_close'                 => '罩门关闭',
                'xy_axis_to_channel'               => 'XY轴移动到货道',
                'x_axis_to_channel'                => 'X轴移动到货道',
                'y_axis_to_channel'                => 'Y轴移动到货道',
                'discharge_belt_early_start'       => '出料履带提前启动',
                'channel_motor_discharge'          => '货道电机出货',
                'xy_axis_to_safe'                  => 'XY轴回安全位',
                'd0_linkage_discharge'             => 'D0联动出货',
                'd0_belt_fallback_start'           => 'D0履带兜底启动',
                'd0_discharge_belt_stop'           => 'D0出料履带停止',
                'discharge_belt_start'             => '出料履带启动',
                'x_axis_to_discharge'              => 'X轴移动到出货位',
                'discharge_belt_stop'              => '出料履带停止',
                'x_axis_to_pickup'                 => 'X轴移动到取货位',
                'cover_door_open'                  => '罩门打开',
                'y_axis_to_pickup_height'          => 'Y轴移动到取货高度',
                'pickup_door_open'                 => '取货门打开',
                'check_pickup_complete'            => '检测取货完成',
                'y_axis_to_safe_height'            => 'Y轴回安全高度',
            ],
        ];
    }

    /**
     * 处理远程出货步骤上报，目前已改为http接口，暂保留此方法以备MQ回执使用
     * 根据 machine_level + is_hang 匹配步骤集，全量步骤默认status=2，
     * 设备上报的key覆盖对应status，先删后插。
     * @param int   $sodId 子单id
     * @param array $steps 设备上报步骤 [['key'=>'xxx','status'=>1],...]
     * @param int $managerId 管理员ID
     */
    protected function handleRemoteOutGoodsSteps($sodId, $steps, $managerId = 0)
    {
        try {
            $machineMId = $this->machine['m_id'] ?? 0;
            $machineId  = $this->machine['machine_id'] ?? '';
            // 单条步骤转为数组
            if (isset($steps['key'])) {
                $steps = [$steps];
            }

            if (!$machineMId || !$machineId) {
                $detail = $this->getSaleOrdersDetailsFind(['sod_id' => $sodId], 'order_id');
                if ($detail) {
                    $detail = is_object($detail) ? $detail->toArray() : $detail;
                    $order  = $this->getSaleOrdersFind(['order_id' => $detail['order_id']], 'm_id,machine_id');
                    if ($order) {
                        $order      = is_object($order) ? $order->toArray() : $order;
                        $machineMId = intval($order['m_id'] ?? 0);
                        $machineId  = $order['machine_id'] ?? '';
                    }
                }
            }

            if (!$machineMId || !$machineId) {
                actionLog(['sod_id' => $sodId], '远程出货步骤缺少m_id或machine_id', 'remoteOutGoodsSteps');
                return;
            }
   
            // 取 machine_level：优先 $this->machine，其次查库
            $machineLevel = intval($this->machine['machine_level'] ?? 0);
            if (!$machineLevel) {
                $machineInfo  = $this->getMachineFind(['m_id' => $machineMId], 'machine_level');
                $machineLevel = intval($machineInfo['machine_level'] ?? 0);
            }

            // 取是否挂件：优先消息中的 is_hang/hang，其次设备属性
            $isHang = intval($this->message['is_hang'] ?? ($this->message['hang'] ?? ($this->machine['is_hang'] ?? 0)));

            $setKey   = $machineLevel . '_' . ($isHang ? '1' : '0');
            $stepSets = $this->getRemoteOutGoodsStepSets();
            $stepSet  = $stepSets[$setKey] ?? [];

            if (!$stepSet) {
                actionLog(
                    ['machine_level' => $machineLevel, 'is_hang' => $isHang, 'setKey' => $setKey],
                    '远程出货步骤未匹配到步骤集',
                    'remoteOutGoodsSteps'
                );
                return;
            }

            // 构建设备上报映射：key => ['status'=>, 'value'=>]
            $reportedMap = [];
            foreach ($steps as $step) {
                $key = $step['key'] ?? '';
                if ($key) {
                    $reportedMap[$key] = [
                        'status' => intval($step['status'] ?? 2),
                        'value'  => $step['value'] ?? '',
                    ];
                }
            }

            $managerId = $managerId ?: ($this->manager['manager_id'] ?? 0);

            // 先删除该 sod_id + 设备 下的旧步骤数据
            Db::name('machine_remote_steps')->where([
                'sod_id' => $sodId,
                'm_id'   => $machineMId,
            ])->delete();

            // 按步骤集全量插入，默认status=2，上报过的覆盖
            $insertRows = [];
            $stepNum    = 0;
            foreach ($stepSet as $stepKey => $stepDesc) {
                $stepNum++;
                $reported = $reportedMap[$stepKey] ?? null;
                $status   = $reported ? $reported['status'] : 2;
                $value    = $reported ? $reported['value'] : '';

                $insertRows[] = [
                    'm_id'       => $machineMId,
                    'sod_id'     => $sodId,
                    'name'       => $stepDesc,
                    'machine_id' => $machineId,
                    'key'        => $stepKey,
                    'step'       => $stepNum,
                    'status'     => $status ?: 2,
                    'value'      => $value,
                    'desc'       => $setKey.'-'.$stepDesc,
                    'manager_id' => $managerId,
                ];
            }

            if ($insertRows) {
                Db::name('machine_remote_steps')->insertAll($insertRows);
                actionLog(
                    ['sod_id' => $sodId, 'set' => $setKey, 'total' => count($insertRows), 'reported' => count($reportedMap)],
                    '远程出货步骤入库',
                    'remoteOutGoodsSteps'
                );
            }
        } catch (\Exception $e) {
            actionException($e, 1, 'remoteOutGoodsSteps');
        }
    }

    public function remoteOutGoods(){
        actionLog($this->message, "远程出货接收mq");
        $status = intval($this->message['status'] ?? 0);
        $sodId = intval($this->message['sod_id'] ?? 0);
        $logId = intval($this->message['log_id'] ?? 0);
        $reportedLogId = $logId;
        if (!$reportedLogId && $sodId) {
            $pendingHeadAction = RemoteActionLogModel::getFind([
                ['sod_id', '=', $sodId],
                ['type', 'in', ['continueOutGoods', 'recycGoods']],
                ['status', 'in', [1, 2]],
            ], 'id,type,status', 'id desc');
            if ($pendingHeadAction) {
                actionLog(
                    ['sod_id' => $sodId, 'status' => $status],
                    '机头商品处理回执缺少log_id，拒绝按普通远程出货处理',
                    'headGoodsAction'
                );
                return false;
            }
        }
        [$logId, $log] = $this->resolveRemoteOutGoodsLog($logId, $sodId);
        if ($reportedLogId && !$log) {
            actionLog(
                ['log_id' => $reportedLogId, 'sod_id' => $sodId, 'status' => $status],
                '远程出货回执log_id无效',
                'remoteOutGoods'
            );
            return false;
        }
        if ($log) {
            $logSodId = intval($log['sod_id'] ?? 0);
            $logMachineId = (string)($log['machine_id'] ?? '');
            $currentMachineId = (string)($this->machine['machine_id'] ?? '');
            if (($sodId && $logSodId && $sodId !== $logSodId)
                || ($logMachineId !== '' && $currentMachineId !== '' && $logMachineId !== $currentMachineId)) {
                actionLog(
                    [
                        'log_id' => $logId,
                        'sod_id' => $sodId,
                        'log_sod_id' => $logSodId,
                        'machine_id' => $currentMachineId,
                        'log_machine_id' => $logMachineId,
                    ],
                    '远程出货回执与动作日志不匹配',
                    'remoteOutGoods'
                );
                return false;
            }
        }
        $actionType = is_array($log) ? ($log['type'] ?? '') : '';
        if (in_array($actionType, ['continueOutGoods', 'recycGoods'], true)) {
            return $this->handleHeadGoodsActionReport($status, $logId, $log);
        }
        if (!$sodId) {
            actionLog($this->message, "远程出货缺少sod_id", "remoteOutGoods");
            return $this->handleRemoteOutGoodsWithoutOrder($status, $logId, $log);
        }

        $detail = $this->getSaleOrdersDetailsFind(
            ['sod_id' => $sodId],
            'sod_id,channel_code,channel_position,quantity,success_quantity,fail_quantity,remote_out_goods_status'
        );
        if (!$detail) {
            actionLog(['sod_id' => $sodId], "远程出货未找到子订单", "remoteOutGoods");
            return $this->handleRemoteOutGoodsWithoutOrder($status, $logId, $log);
        }
        $detail = is_object($detail) ? $detail->toArray() : $detail;
        // 处理远程出货步骤上报
        $steps = $this->message['steps'] ?? $this->message['step'] ?? [];
        if ($steps) {
            $this->handleRemoteOutGoodsSteps($sodId, $steps);
        }
        try {
            $this->startTrans();

            $previousStatus = intval($detail['remote_out_goods_status'] ?? 0);
            if ($logId) {
                $this->updateRALog(
                    ['status' => $status, 'operator_at' => date('Y-m-d H:i:s')],
                    ['id' => $logId],
                    ['status', 'operator_at']
                );
            }

            $updateSod = [
                'sod_id' => $sodId,
                'remote_out_goods_status' => $status,
            ];
            $updateFields = ['remote_out_goods_status'];
            $flag = [];
            $understockNotice = null;

            // remoteOutGoods 状态定义：
            // 1-已发指令 2-设备已接收 20-不减库存 21-扣减库存 3-出货成功 4-出货失败
            // status=21/3/4 首次处理库存；status=4 记录出货失败库存，BAD恢复时再回补。
            $shouldProcessRemoteStock = in_array($status, [21, 3, 4], true) && !in_array($previousStatus, [21, 3, 4], true);
            $shouldMarkRemoteFailStock = $status === 4 && $previousStatus === 21;
            if ($shouldProcessRemoteStock || $shouldMarkRemoteFailStock) {
                $remoteOutGoodsItem = $this->extractFirstRemoteOutGoodsItem($this->message);
                $channelCode = $this->message['channel_code'] ?? ($log['channel_code'] ?? $detail['channel_code']);
                if (!$channelCode) {
                    $channelCode = $remoteOutGoodsItem['channel_code'] ?? '';
                }
                $machineMId = $this->machine['m_id'] ?? 0;
                if (!$machineMId && !empty($log['machine_id'])) {
                    $machineInfo = $this->getMachineFind(['machine_id' => $log['machine_id']], 'm_id');
                    $machineMId = intval($machineInfo['m_id'] ?? 0);
                }

                if (!$channelCode || !$machineMId) {
                    actionLog(['m_id' => $machineMId, 'channel_code' => $channelCode], '远程出货缺少货道定位信息', 'remoteOutGoods');
                    $this->rollbackTrans();
                    return false;
                }

                $mc = $this->getMachineChannelFind(
                    ['m_id' => $machineMId, 'channel_code' => $channelCode],
                    'mc_id,m_id,machine_id,channel_code,channel_position,mg_id,g_id,g_name,gc_id,gc_name,pic,sku,bar_code,stock,out_fail_stock,stock_warning'
                );
                if (!$mc) {
                    actionLog(['m_id' => $machineMId, 'channel_code' => $channelCode], '远程出货未找到对应货道', 'remoteOutGoods');
                    $this->rollbackTrans();
                    return false;
                }
                $mc = is_object($mc) ? $mc->toArray() : $mc;
                $updateSod['channel_code'] = $mc['channel_code'];
                $updateSod['channel_position'] = $mc['channel_position'];
                $updateFields[] = 'channel_code';
                $updateFields[] = 'channel_position';

                if (in_array($status, [21, 3], true)) {
                    $quantity = max(1, intval($detail['quantity'] ?? 0));
                    $currentSuccess = max(0, intval($detail['success_quantity'] ?? 0));
                    $nextSuccess = min($quantity, $currentSuccess + 1);
                    $successIncrement = max(0, $nextSuccess - $currentSuccess);
                    $updateSod['success_quantity'] = $nextSuccess;
                    $updateSod['fail_quantity'] = max(
                        0,
                        intval($detail['fail_quantity'] ?? 0) - $successIncrement
                    );
                    $updateFields[] = 'success_quantity';
                    $updateFields[] = 'fail_quantity';
                }

                $changeValue = $shouldProcessRemoteStock ? min(1, max(0, intval($mc['stock']))) : 0;
                $newStock = $shouldProcessRemoteStock ? max(0, intval($mc['stock']) - 1) : intval($mc['stock']);
                $updateMc = [
                    'mc_id' => $mc['mc_id'],
                    'stock' => $newStock,
                ];
                if ($status === 4) {
                    $updateMc['out_fail_stock'] = max(0, intval($mc['out_fail_stock'] ?? 0)) + 1;
                }
                $flag[] = $this->updateMachineChannel($updateMc);
                if ($changeValue > 0) {
                    $this->addRemoteOutGoodsChange($mc, $changeValue);
                }
                if ($this->shouldSendRemoteOutGoodsUnderstockNotice($mc, $newStock)) {
                    $understockNotice = [$this->machine ?? [], $mc, $newStock];
                }
                actionLog($this->getLS(), '【SQL】远程出货(status=21/3/4)修改货道', 'remoteOutGoods');
            }

            $flag[] = $this->updateSaleOrdersDetails($updateSod, [], $updateFields);
            actionLog($this->getLS(), '【SQL】远程出货修改订单副表', 'remoteOutGoods');

            $result = $this->checkFlag($flag);
            if (!$result) {
                $this->rollbackTrans();
                return false;
            }

            $this->commitTrans();
            if ($understockNotice) {
                $this->sendRemoteOutGoodsUnderstockNotice($understockNotice[0], $understockNotice[1], $understockNotice[2]);
            }
            return true;
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1, 'remoteOutGoods');
            return false;
        }
    }

    /**
     * 处理机头遗留商品的继续出货/直接回收回执。
     *
     * 这两类动作复用 remoteOutGoods 上报协议，但原订单已经完成库存结算，
     * 因此这里只维护动作日志，不能再次修改订单、子单、退款、库存或
     * remote_out_goods_status。
     */
    protected function handleHeadGoodsActionReport($status, $logId, array $log)
    {
        $status = intval($status);
        $logId = intval($logId);
        if (!$logId || !in_array($status, [2, 20, 21, 3, 4], true)) {
            actionLog(
                ['log_id' => $logId, 'status' => $status],
                '机头商品处理回执参数无效',
                'headGoodsAction'
            );
            return false;
        }

        try {
            Db::startTrans();
            $lockedLog = Db::name('remote_action_log')->where(['id' => $logId])->lock(true)->find();
            if (!$lockedLog || !in_array($lockedLog['type'] ?? '', ['continueOutGoods', 'recycGoods'], true)) {
                Db::rollback();
                actionLog(['log_id' => $logId], '未找到机头商品处理日志', 'headGoodsAction');
                return false;
            }

            $machineId = (string)($this->machine['machine_id'] ?? '');
            if ($machineId !== '' && (string)$lockedLog['machine_id'] !== $machineId) {
                Db::rollback();
                actionLog(
                    ['log_id' => $logId, 'log_machine_id' => $lockedLog['machine_id'], 'machine_id' => $machineId],
                    '机头商品处理回执设备不匹配',
                    'headGoodsAction'
                );
                return false;
            }

            $messageSodId = intval($this->message['sod_id'] ?? 0);
            $logSodId = intval($lockedLog['sod_id'] ?? 0);
            if (!$messageSodId || !$logSodId || $messageSodId !== $logSodId) {
                Db::rollback();
                actionLog(
                    ['log_id' => $logId, 'message_sod_id' => $messageSodId, 'log_sod_id' => $logSodId],
                    '机头商品处理回执sod_id不匹配',
                    'headGoodsAction'
                );
                return false;
            }

            $currentStatus = intval($lockedLog['status'] ?? 0);
            // 一个动作日志一旦成功或失败即为终态；重试必须创建新的 log_id。
            if (in_array($currentStatus, [3, 4], true)) {
                Db::commit();
                actionLog(
                    ['log_id' => $logId, 'type' => $lockedLog['type'], 'status' => $status, 'current_status' => $currentStatus],
                    '机头商品处理重复终态回执，按幂等成功返回',
                    'headGoodsAction'
                );
                return true;
            }

            // 2/20/21 都是执行中的过程状态；只有3/4是最终成功/失败。
            $actionStatus = in_array($status, [2, 20, 21], true) ? 2 : $status;
            $result = Db::name('remote_action_log')->where(['id' => $logId])->update([
                'status' => $actionStatus,
                'operator_at' => date('Y-m-d H:i:s'),
            ]);
            if ($result === false) {
                Db::rollback();
                return false;
            }

            Db::commit();
            actionLog(
                [
                    'log_id' => $logId,
                    'type' => $lockedLog['type'],
                    'device_status' => $status,
                    'action_status' => $actionStatus,
                    'sod_id' => $logSodId,
                ],
                '机头商品处理回执完成',
                'headGoodsAction'
            );
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            actionException($e, 1, 'headGoodsAction');
            return false;
        }
    }

    protected function resolveRemoteOutGoodsLog($logId, $sodId): array
    {
        $logId = intval($logId);
        $sodId = intval($sodId);
        $hasExplicitLogId = $logId > 0;
        if (!$logId) {
            $tradeNo = trim((string)($this->message['trade_no'] ?? ''));
            if (strpos($tradeNo, 'remote_out_goods_') === 0) {
                $logId = intval(str_replace('remote_out_goods_', '', $tradeNo));
                $hasExplicitLogId = $logId > 0;
            }
        }

        $log = null;
        if ($logId) {
            $log = RemoteActionLogModel::getFind(
                ['id' => $logId],
                'id,type,machine_id,order_id,sod_id,goods_id,channel_code,status'
            );
            $log = is_object($log) ? $log->toArray() : $log;
        }

        if (!$log && !$hasExplicitLogId && $sodId) {
            $where = [
                'type' => 'remoteOutGoods',
                'sod_id' => $sodId,
            ];
            if (!empty($this->message['machine_id'])) {
                $where['machine_id'] = $this->message['machine_id'];
            } elseif (!empty($this->machine['machine_id'])) {
                $where['machine_id'] = $this->machine['machine_id'];
            }
            $log = RemoteActionLogModel::getFind(
                $where,
                'id,type,machine_id,order_id,sod_id,goods_id,channel_code,status',
                'id desc'
            );
            $log = is_object($log) ? $log->toArray() : $log;
            if ($log) {
                $logId = intval($log['id'] ?? 0);
            }
        }

        return [$logId, $log ?: null];
    }

    protected function handleRemoteOutGoodsWithoutOrder($status, $logId, $log)
    {
        if (!$logId || !$log) {
            actionLog(['log_id' => $logId, 'status' => $status], '远程出货无订单且缺少日志，跳过库存处理', 'remoteOutGoods');
            return true;
        }

        try {
            $this->startTrans();

            $previousStatus = intval($log['status'] ?? 0);
            $flag = [];
            $understockNotice = null;
            $updateLog = ['status' => $status, 'operator_at' => date('Y-m-d H:i:s')];
            $updateLogFields = ['status', 'operator_at'];

            $shouldProcessRemoteStock = in_array($status, [21, 3, 4], true) && !in_array($previousStatus, [21, 3, 4], true);
            $shouldMarkRemoteFailStock = $status === 4 && $previousStatus === 21;
            if ($shouldProcessRemoteStock || $shouldMarkRemoteFailStock) {
                $remoteOutGoodsItem = $this->extractFirstRemoteOutGoodsItem($this->message);
                $channelCode = $this->message['channel_code'] ?? ($log['channel_code'] ?? '');
                if (!$channelCode) {
                    $channelCode = $remoteOutGoodsItem['channel_code'] ?? '';
                }
                if ($channelCode && empty($log['channel_code'])) {
                    $updateLog['channel_code'] = $channelCode;
                    $updateLogFields[] = 'channel_code';
                }
                $machineMId = $this->machine['m_id'] ?? 0;
                if (!$machineMId && !empty($log['machine_id'])) {
                    $machineInfo = $this->getMachineFind(['machine_id' => $log['machine_id']], 'm_id');
                    $machineMId = intval($machineInfo['m_id'] ?? 0);
                }

                if (!$channelCode || !$machineMId) {
                    actionLog(['m_id' => $machineMId, 'channel_code' => $channelCode], '无订单远程出货缺少货道定位信息', 'remoteOutGoods');
                    $this->rollbackTrans();
                    return false;
                }

                $mc = $this->getMachineChannelFind(
                    ['m_id' => $machineMId, 'channel_code' => $channelCode],
                    'mc_id,m_id,machine_id,channel_code,mg_id,g_id,g_name,gc_id,gc_name,pic,sku,bar_code,stock,out_fail_stock,stock_warning'
                );
                if (!$mc) {
                    actionLog(['m_id' => $machineMId, 'channel_code' => $channelCode], '无订单远程出货未找到对应货道', 'remoteOutGoods');
                    $this->rollbackTrans();
                    return false;
                }
                $mc = is_object($mc) ? $mc->toArray() : $mc;

                $changeValue = $shouldProcessRemoteStock ? min(1, max(0, intval($mc['stock']))) : 0;
                $newStock = $shouldProcessRemoteStock ? max(0, intval($mc['stock']) - 1) : intval($mc['stock']);
                $updateMc = [
                    'mc_id' => $mc['mc_id'],
                    'stock' => $newStock,
                ];
                if ($status === 4) {
                    $updateMc['out_fail_stock'] = max(0, intval($mc['out_fail_stock'] ?? 0)) + 1;
                }
                $flag[] = $this->updateMachineChannel($updateMc);
                if ($changeValue > 0) {
                    $this->addRemoteOutGoodsChange($mc, $changeValue);
                }
                if ($this->shouldSendRemoteOutGoodsUnderstockNotice($mc, $newStock)) {
                    $understockNotice = [$this->machine ?? [], $mc, $newStock];
                }
                actionLog($this->getLS(), '【SQL】无订单远程出货修改货道', 'remoteOutGoods');
            }

            $flag[] = $this->updateRALog(
                $updateLog,
                ['id' => $logId],
                $updateLogFields
            );

            $result = $this->checkFlag($flag);
            if (!$result) {
                $this->rollbackTrans();
                return false;
            }

            $this->commitTrans();
            if ($understockNotice) {
                $this->sendRemoteOutGoodsUnderstockNotice($understockNotice[0], $understockNotice[1], $understockNotice[2]);
            }
            return true;
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1, 'remoteOutGoods');
            return false;
        }
    }

    protected function sendRemoteOutGoodsUnderstockNotice($machine, $mc, $stock)
    {
        $machine = is_object($machine) ? $machine->toArray() : (array) $machine;
        $mc = is_object($mc) ? $mc->toArray() : (array) $mc;
        $stock = intval($stock);
        $stockWarning = intval($mc['stock_warning'] ?? 0);
        if ($stock > $stockWarning) {
            return;
        }

        try {
            $machineMId = intval($machine['m_id'] ?? ($mc['m_id'] ?? 0));
            if (!$machineMId) {
                actionLog(['machine' => $machine, 'mc' => $mc], '远程出货库存达到预警缺少设备信息，跳过商品不足公众号通知', 'remoteOutGoods');
                return;
            }

            if (empty($machine['ao_id']) || empty($machine['machine_id']) || empty($machine['machine_name'])) {
                $machineInfo = $this->getMachineFind(['m_id' => $machineMId], 'm_id,ao_id,machine_id,machine_name');
                $machine = is_object($machineInfo) ? $machineInfo->toArray() : (array) $machineInfo;
            }
            if (empty($machine['ao_id'])) {
                actionLog(['m_id' => $machineMId], '远程出货库存达到预警缺少组织信息，跳过商品不足公众号通知', 'remoteOutGoods');
                return;
            }

            $errorCode = "1000101";
            $noticeData = [
                "ao_id" => $machine['ao_id'],
                "m_id" => $machineMId,
                "sendType" => 1,
                "templateType" => "understock",
                "replaceData" => [
                    "machine_id" => $machine['machine_id'] ?? '',
                    "machine_name" => $machine['machine_name'] ?? '',
                    "stock" => $stock,
                    "channel_code" => $mc['channel_code'] ?? '',
                    "stock_warning" => $stockWarning,
                    "error_code" => $this->lang("deviceErrorCode.".$errorCode),
                    "error_time" => date('Y-m-d H:i:s'),
                    "error_info" => $mc['channel_code'] ?? '',
                ],
            ];
            actionLog($noticeData, '远程出货库存达到预警发送商品不足公众号通知', 'remoteOutGoods');
            $result = AppFactory::notice($noticeData)->weChat->send();
            actionLog($result, '远程出货库存达到预警发送商品不足公众号通知结果', 'remoteOutGoods');
        } catch (\Throwable $e) {
            actionException($e, 1, 'remoteOutGoods');
        }
    }

    protected function addRemoteOutGoodsChange(array $mc, $changeValue)
    {
        $changeValue = intval($changeValue);
        if ($changeValue <= 0) {
            return false;
        }
        if (!method_exists($this, 'addGoodsChange')) {
            actionLog($mc, '远程出货缺少商品变化记录方法，跳过商品变化记录', 'remoteOutGoods');
            return false;
        }

        $machine = $this->machine ?? [];
        $machine = is_object($machine) ? $machine->toArray() : (array)$machine;
        $machineMId = intval($machine['m_id'] ?? ($mc['m_id'] ?? 0));
        if ($machineMId && (empty($machine['machine_id']) || empty($machine['machine_name']) || empty($machine['ao_id']))) {
            $machineInfo = $this->getMachineFind(['m_id' => $machineMId], 'm_id,ao_id,machine_id,machine_name');
            $machineInfo = is_object($machineInfo) ? $machineInfo->toArray() : (array)$machineInfo;
            foreach ($machineInfo as $key => $value) {
                if (!isset($machine[$key]) || $machine[$key] === '' || $machine[$key] === 0) {
                    $machine[$key] = $value;
                }
            }
        }

        $insertGChange = [
            "m_id" => $machineMId,
            "machine_id" => $machine['machine_id'] ?? ($mc['machine_id'] ?? ''),
            "machine_name" => $machine['machine_name'] ?? '',
            "mc_id" => $mc['mc_id'],
            "channel_code" => $mc['channel_code'],
            "mg_id" => $mc['mg_id'] ?? 0,
            "g_id" => $mc['g_id'] ?? 0,
            "g_name" => $mc['g_name'] ?? "",
            "gc_id" => $mc['gc_id'] ?? 0,
            "gc_name" => $mc['gc_name'] ?? "",
            "pic" => $mc['pic'] ?? "",
            "sku" => $mc['sku'] ?? "",
            "bar_code" => $mc['bar_code'] ?? "",
            "ao_id" => $machine['ao_id'] ?? 0,
            "change_value" => $changeValue,
            "desc" => $this->lang("goodsChange.terminal_sale_dec_stock"),
            "position" => 1,
            "type" => 3,
        ];
        $changeId = $this->addGoodsChange($insertGChange);
        actionLog(['change_id' => $changeId, 'data' => $insertGChange], '【SQL】远程出货添加商品变化数据', 'remoteOutGoods');
        return $changeId;
    }

    protected function shouldSendRemoteOutGoodsUnderstockNotice($mc, $stock): bool
    {
        $mc = is_object($mc) ? $mc->toArray() : (array) $mc;
        return intval($stock) <= intval($mc['stock_warning'] ?? 0);
    }

    /**
     * 远程下架回收结束上报
     * 根据成功回收数量扣减货道库存；库存不足则清空货道商品并下发更新货道MQ。
     * @return int
     */
    public function remoteRemovalEnd()
    {
        try {
            $whereMc = ['m_id' => $this->machine['m_id']];
            if (isset($this->message['mc_id']) && $this->message['mc_id']) {
                $whereMc['mc_id'] = intval($this->message['mc_id']);
            }
            if (isset($this->message['channel_code']) && $this->message['channel_code']) {
                $whereMc['channel_code'] = $this->message['channel_code'];
            }

            if (!isset($whereMc['mc_id']) && !isset($whereMc['channel_code'])) {
                actionLog($this->message, 'remoteRemovalEnd参数缺少mc_id/channel_code', 'DataUpload');
                return 1;
            }

            $mc = $this->getMachineChannelFind(
                $whereMc,
                'mc_id,m_id,machine_id,mg_id,g_id,g_name,gc_id,gc_name,pic,channel_code,sku,bar_code,cost_price,market_price,retail_price,gift_points,stock,frozen_stock'
            );
            if (!$mc) {
                actionLog($whereMc, 'remoteRemovalEnd未找到货道', 'DataUpload');
                return 1;
            }
            $mc = $mc->toArray();

            $totalCount = intval($this->message['total_count'] ?? 0);
            $successCount = intval($this->message['success_count'] ?? ($this->message['success'] ?? 0));
            $failCount = intval($this->message['fail_count'] ?? ($this->message['fail'] ?? 0));
            if ($totalCount <= 0) {
                $totalCount = $successCount + $failCount;
            }
            if ($failCount <= 0 && $totalCount > $successCount) {
                $failCount = $totalCount - $successCount;
            }
            if ($successCount < 0) {
                $successCount = 0;
            }

            $remark = $this->message['remark'] ?? ($this->message['msg'] ?? '');
            if (is_array($remark) || is_object($remark)) {
                $remark = json_encode($remark, 320);
            }

            $lastLog = $this->getRemoteRemovalLogFind(
                ['m_id' => $mc['m_id'], 'mc_id' => $mc['mc_id']],
                'id,creator,interrupted_at',
                'id desc'
            );
            $isInterruptedReport = $lastLog && intval($lastLog['interrupted_at'] ?? 0) > 0;

            $logData = [
                'm_id' => $mc['m_id'],
                'machine_id' => $mc['machine_id'],
                'mc_id' => $mc['mc_id'],
                'g_id' => $mc['g_id'],
                'sku' => $mc['sku'] ?? '',
                'total_count' => $totalCount,
                'success_count' => $successCount,
                'fail_count' => $failCount,
                'remark' => $remark,
                'reported_at' => time(),
            ];

            if ($lastLog) {
                $this->updateRemoteRemovalLog($logData, ['id' => $lastLog['id']]);
            } else {
                $logData['created_at'] = time();
                $this->addRemoteRemovalLog($logData);
            }

            if ($successCount > 0) {
                $currentStock = max(0, intval($mc['stock'] ?? 0));
                $newStock = max(0, $currentStock - $successCount);
                $updateMc = ['stock' => $newStock];
                $shouldDisable = !$isInterruptedReport && $newStock <= 0;
                if ($shouldDisable) {
                    $updateMc['status'] = 3;
                }
                $this->updateMachineChannel($updateMc, ['mc_id' => $mc['mc_id']]);

                $changeValue = min($successCount, $currentStock);
                if ($changeValue > 0 && method_exists($this, 'addGoodsChange')) {
                    $creator = intval($lastLog['creator'] ?? ($this->message['manager_id'] ?? 0));
                    $goodsChange = [
                        'm_id' => $mc['m_id'],
                        'machine_id' => $mc['machine_id'],
                        'machine_name' => $this->machine['machine_name'] ?? '',
                        'mc_id' => $mc['mc_id'],
                        'channel_code' => $mc['channel_code'],
                        'mg_id' => $mc['mg_id'] ?? 0,
                        'g_id' => $mc['g_id'] ?? 0,
                        'g_name' => $mc['g_name'] ?? '',
                        'gc_id' => $mc['gc_id'] ?? 0,
                        'gc_name' => $mc['gc_name'] ?? '',
                        'pic' => $mc['pic'] ?? '',
                        'sku' => $mc['sku'] ?? '',
                        'bar_code' => $mc['bar_code'] ?? '',
                        'ao_id' => $this->machine['ao_id'] ?? 0,
                        'creator' => $creator,
                        'change_value' => $changeValue,
                        'type' => 3,
                        'desc' => '远程下架回收扣减',
                        'position' => 1,
                        'create_time' => time(),
                    ];
                    $this->addGoodsChange($goodsChange);

                    if ($shouldDisable) {
                        $goodsChange['desc'] = '远程下架回收完毕设置货道为BAD';
                        $this->addGoodsChange($goodsChange);
                    }
                }

                if (!empty($mc['machine_id'])) {
                    $mqResult = $this->sendToMachine(
                        ['machine_id' => $mc['machine_id']],
                        'updateMc',
                        ['mc_id' => intval($mc['mc_id'])]
                    );
                    actionLog(
                        ['mc_id' => intval($mc['mc_id']), 'quantity' => $successCount, 'mq_result' => $mqResult],
                        '远程下架上报扣减货道库存后下发updateMc',
                        'remoteRemovalEnd'
                    );
                }
            }

            return 1;
        } catch (\Exception $e) {
            actionException($e, 1, 'remoteRemovalEnd');
            return 1;
        }
    }

    /**
     * 扣减货道库存；库存不足时清空货道商品，并复用后台 updateMc MQ 同步设备货道信息。
     * @param array $mc
     * @param int $quantity
     * @param string $logTag
     * @return mixed
     */
    protected function deductMachineChannelStockAndSendUpdateMq($mc, $quantity, $logTag = 'DataUpload')
    {
        $quantity = intval($quantity);
        if ($quantity <= 0 || empty($mc['mc_id'])) {
            return true;
        }

        $newStock = intval($mc['stock'] ?? 0) - $quantity;
        if ($newStock > 0) {
            $result = $this->updateMachineChannel(['mc_id' => $mc['mc_id'], 'stock' => $newStock]);
        } else {
            $clearData = [
                'mc_id' => $mc['mc_id'],
                'mg_id' => 0,
                'g_id' => 0,
                'g_name' => '',
                'gc_id' => 0,
                'gc_name' => '',
                'pic' => '',
                'sku' => '',
                'bar_code' => '',
                'cost_price' => 0,
                'market_price' => 0,
                'retail_price' => 0,
                'gift_points' => 0,
                'stock' => 0,
                'frozen_stock' => 0,
            ];
            $result = $this->updateMachineChannel($clearData);
        }

        if (!empty($mc['machine_id'])) {
            $mqResult = $this->sendToMachine(['machine_id' => $mc['machine_id']], 'updateMc', ['mc_id' => intval($mc['mc_id'])]);
            actionLog(['mc_id' => intval($mc['mc_id']), 'quantity' => $quantity, 'mq_result' => $mqResult], '扣减货道库存后下发updateMc', $logTag);
        }
        return $result;
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
            $checkCoolDown = 300;

            // 心跳兜底时限频检查，避免每次心跳都查数据库。偶发文件缓存读取失败导致的漏发问题
            $lastCheckTime = cache($checkKey);
            if ($lastCheckTime && ($now - $lastCheckTime < $checkCoolDown)) {
                 return;
            }
             cache($checkKey, $now, $checkCoolDown);
            //create_time大于最近3天，避免历史数据上线时被补发。
            $plan = Db::name('machine_version_plan')->where([
                'machine_id' => $this->machine['machine_id'],
                'download_progress' => 0,
                'status' => 1,
            ])->where('publish_time', '<=', $now)
            ->where('create_time', '>', $now - 259200)
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
