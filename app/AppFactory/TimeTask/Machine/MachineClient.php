<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/2/23
 * Time: 12:05
 */

namespace app\AppFactory\TimeTask\Machine;


use app\AppFactory\Kernel\Traits\Activity\ActivityCouponUsedTrait;
use app\AppFactory\Kernel\Traits\Auth\AuthManagerMachineTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineMqRecordTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineOnlineDetailsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineOnlineSnapshotTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineOnlineTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineOnOffTrait;
use app\AppFactory\Kernel\Traits\Machine\SimCardInfoTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersTrait;
use app\AppFactory\Kernel\Traits\Send\ToManagerTrait;
use app\AppFactory\Kernel\Support\SimiotService\Simiot;
use app\AppFactory\TimeTask\TimeTaskBase;
use think\cache\driver\Redis;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Db;
use think\facade\Cache;
use think\facade\Env as FacadeEnv;

class MachineClient extends TimeTaskBase
{
    use MachineOnlineTrait,MachineOnlineDetailsTrait,MachineOnlineSnapshotTrait,MachineTrait,MachineOnOffTrait,MachineMqRecordTrait;
    use SaleOrdersTrait;
    use AuthManagerMachineTrait;
    use ActivityCouponUsedTrait;
    use SimCardInfoTrait;
    use ToManagerTrait;

    /**
     * 定时任务，每天定时一次，结算昨天在线时长
     */
    public function countOnline()
    {
        try {
            $yesterday = input("date");
            $machine_id = input('machine_id');
            $whereM = [];
            if ($machine_id) $whereM["machine_id"] = $machine_id;
            $yesterday = $yesterday ? strtotime($yesterday) : strtotime(date("Y-m-d", strtotime("-1 days")));
            $machine = $this->getMachineList($whereM, 0, 'm_id,machine_id,machine_name,ao_id');
            if ($machine) {
                $flag = [];
                foreach ($machine as $key => $value) {
                    $whereDetails['m_id'] = $value['m_id'];
                    $whereDetails['d_date'] = $yesterday;
                    $whereDetails['duration'] = 0;
                    // 先统计没有离线时间并且是跨天的记录在线时长，生成新的上线记录
                    $checkDetails = $this->getMachineOnlineDetailsList($whereDetails);
                    $checkDetails = $checkDetails->toArray();
                    if ($checkDetails) {
                        foreach ($checkDetails as $odk => $odv) {
                            if (!$odv['offline_time']) {
                                // 生成今天的上线记录
                                $onlineTime = strtotime(date("Y-m-d 00:00:00"));
                                $insert = [
                                    "m_id" => $odv['m_id'],
                                    "machine_name" => $odv['machine_name'],
                                    "machine_id" => $odv['machine_id'],
                                    "online_time" => $onlineTime,
                                    "heart_time" => $onlineTime,
                                    "d_date" => strtotime(date("Y-m-d")),
                                    "ao_id" => $machine['ao_id'],
                                ];
                                $flag[] = $this->addMachineOnlineDetails($insert);
                                actionLog($this->getLS(), '【SQL】生成以当天0点为上线时间的记录', 'countOnline');
                                // 当前记录的离线时间点
                                $odv['offline_time'] = strtotime(date("Y-m-d 23:59:59", $yesterday));
                            }
                            $odv['duration'] = bcsub($odv['offline_time'], $odv['online_time']);
                            $flag[] = $this->updateMachineOnlineDetails($odv);
                            actionLog($this->getLS(), '【SQL】修改设备详情的在线时长', 'countOnline');
                        }
                    }
                    // 统计昨天的在线时长
                    $where['m_id'] = $value['m_id'];
                    $where["d_date"] = $yesterday;
                    $field = "m_id,machine_name,machine_id,sum(duration) duration ,count(mod_id) online_frequency,d_date online_date,ao_id";
                    $onlineDetails = $this->getMachineOnlineDetailsFind($where, $field, 'mod_id desc', "m_id");
                    if ($onlineDetails) {
                        $onlineDetails = $onlineDetails->toArray();
                        $manager_id = $this->getAuthManagerMachineValue(['m_id' => $value['m_id']], 'manager_id', 'manager_id desc');
                        $onlineDetails['manager_id'] = $manager_id;
                        // 获取运营设定，计算原定运营时长，校对判断运营状态，统计上线离线次数
                        // 20250521，原运营时长修改为定时开关机时长
                        $ckcDuration = 86399;
                        $ckc = $this->getMachineOnOffFind(['m_id' => $value['m_id'],'status' => 1],'on_off_machine');
                        if ($ckc) {
                            $ckc = $ckc->toArray();
                            $ckcList = json_decode($ckc['on_off_machine'],true);
                            actionLog($ckcList,'定时开关机设置','countOnline');
                            if ($ckcList) {
                                $thisWeek = date("w",strtotime(date("Y-m-d", $yesterday) . " -1 days")); // 昨天本周几，0~6，周日至周六
                                actionLog($thisWeek,'系统周几值',"countOnline");
                                if (isset($ckcList[$thisWeek])) {
                                    // 获取本周几（昨天）设置的营业时间
                                    $ckcTime = explode(",",$ckcList[$thisWeek]);
                                    if ($ckcTime) {
                                        $endTime = HourMinuteSec2int($ckcTime[0]);
                                        $startTime = HourMinuteSec2int($ckcTime[1]);
                                        if ($startTime > $endTime) {
                                            actionLog([$startTime,$endTime],"开机时间，关机时间，关机时间比开机时间早","countOnline");
                                            $temp = $startTime;
                                            $startTime = $endTime;
                                            $endTime = $temp;
                                        }
                                        $ckcDuration = $endTime - $startTime;
                                    }
                                }
                            }
                        }
                        actionLog($ckcDuration,'记录的设定营业时长',"countOnline");
                        $onlineDetails['ckc_duration'] = $ckcDuration;
                        $onlineDetails['ao_id'] = $value['ao_id'];
                        // 增加统计全天的记录，并绑定ID至详情
                        $online_id = $this->addMachineOnline($onlineDetails);
                        actionLog($this->getLS(), '【SQL】统计全天在线时长', 'countOnline');
                        if ($online_id) {
                            $update['online_id'] = $online_id;
                            $flag[] = $this->updateMachineOnlineDetails($update, $where);
                            actionLog($this->getLS(), '【SQL】修改全天离线时长', 'countOnline');
                        }
                    }
                }

                actionLog($flag, '处理结果');
                $this->delMachineMqRecord([['create_time','<',strtotime("-7 days")]]);
            }
        } catch (DataNotFoundException $e) {
            actionException($e,1,'countOnline');
        } catch (ModelNotFoundException $e) {
            actionException($e,1,'countOnline');
        } catch (DbException $e) {
            actionException($e,1,'countOnline');
        }
        return "处理成功";
    }

    /**
     * Scheduled task: collect online snapshots for operating machines every 2 hours.
     * command: php think time_task machine collectOperatingOnlineSnapshot
     */
    public function collectOperatingOnlineSnapshot()
    {
        $today = strtotime(date("Y-m-d"));
        $now = time();
        $nowSec = HourMinuteSec2int(date("H:i:s", $now));
        $slotStart = $today + intval(floor(($now - $today) / 7200)) * 7200;
        $slotEnd = min($slotStart + 7199, $today + 86399);
        $recordDate = date("Y-m-d H:i:s", $today);
        $collectTime = date("Y-m-d H:i:s", $now);
        $slotStartTime = date("Y-m-d H:i:s", $slotStart);
        $slotEndTime = date("Y-m-d H:i:s", $slotEnd);

        $where = [
            ['is_operating', '=', 1],
            ['vending_machine_type', '=', 1],
        ];
        $machines = $this->getMachineList($where, 0, 'm_id,machine_id,machine_name,online,is_operating,ckc_status,ao_id');
        if (!$machines) {
            return "no data";
        }

        $flag = [];
        foreach ($machines as $machine) {
            [$businessStart, $businessEnd] = $this->getMachineBusinessWindow($machine['m_id'], $today);
            if ($nowSec < $businessStart || $nowSec > $businessEnd) {
                continue;
            }

            $saveData = [
                'm_id' => $machine['m_id'],
                'machine_id' => $machine['machine_id'],
                'machine_name' => $machine['machine_name'],
                'online' => $machine['online'],
                'is_operating' => $machine['is_operating'],
                'ckc_status' => $machine['ckc_status'] ?? 1,
                'record_date' => $recordDate,
                'collect_time' => $collectTime,
                'slot_start_time' => $slotStartTime,
                'slot_end_time' => $slotEndTime,
                'business_start_time' => date("Y-m-d H:i:s", $today + intval($businessStart)),
                'business_end_time' => date("Y-m-d H:i:s", $today + intval($businessEnd)),
                'ao_id' => $machine['ao_id'] ?? 0,
            ];

            $exists = $this->getMachineOnlineSnapshotFind([
                'm_id' => $machine['m_id'],
                'record_date' => $recordDate,
                'slot_start_time' => $slotStartTime,
            ], 'mos_id');
            if ($exists) {
                $saveData['mos_id'] = $exists['mos_id'];
                $flag[] = $this->updateMachineOnlineSnapshot($saveData);
            } else {
                $flag[] = $this->addMachineOnlineSnapshot($saveData);
            }
        }

        actionLog($flag, 'collectOperatingOnlineSnapshot');
        return "ok";
    }

    /**
     * Resolve business window (seconds in day) from machine on/off config.
     */
    protected function getMachineBusinessWindow($mId, $today)
    {
        $default = [0, 86399];
        $onOff = $this->getMachineOnOffFind(['m_id' => $mId, 'status' => 1], 'on_off_machine');
        if (!$onOff || empty($onOff['on_off_machine'])) {
            return $default;
        }

        $onOffList = json_decode($onOff['on_off_machine'], true);
        if (!$onOffList || !is_array($onOffList)) {
            return $default;
        }

        $week = intval(date('w', $today));
        if (!isset($onOffList[$week])) {
            return $default;
        }

        $timeRange = explode(",", strval($onOffList[$week]));
        $pointA = trim($timeRange[0] ?? '');
        $pointB = trim($timeRange[1] ?? '');
        if (
            $pointA === '' || $pointB === '' ||
            strtolower($pointA) === 'null' || strtolower($pointB) === 'null'
        ) {
            return $default;
        }

        $secA = HourMinuteSec2int($pointA);
        $secB = HourMinuteSec2int($pointB);
        $start = min($secA, $secB);
        $end = max($secA, $secB);

        return [$start, $end];
    }

    /**
     * Scheduled task: check offline records.
     */
    public function checkOffline()
    {
        $details = $this->getMachineOnlineDetailsList(['offline_time' => 0, ['heart_time', '<', time() - env("machine.timeout",60)]], 0, 'mod_id,m_id,machine_name,machine_id,online_time,d_date');
        $httpFlag = [];
        if ($details) {
            $flag[] = 1;
            $this->startTrans();
            try {
                $details = $details->toArray();
                foreach ($details as $key => $value) {
                    // 跨天处理
                    if ($value['d_date'] != strtotime(date("Y-m-d"))) {
                        // 结束昨天的在线记录
                        $offlineTime = strtotime(date("Y-m-d 23:59:59", $value['d_date']));
                        $update['mod_id'] = $value['mod_id'];
                        $update['offline_time'] = $offlineTime;
                        $update['heart_time'] = $offlineTime;
                        $update['duration'] = bcsub($offlineTime, $value['online_time']);
                        $this->updateMachineOnlineDetails($update);
                        // 生成今天的离线记录
                        $onlineTime = strtotime(date("Y-m-d 00:00:00"));
                        $insert = [
                            "m_id" => $value['m_id'],
                            "machine_name" => $value['machine_name'],
                            "machine_id" => $value['machine_id'],
                            "online_time" => $onlineTime,
                            "offline_time" => time(),
                            "heart_time" => $onlineTime,
                            "duration" => bcsub(time(), strtotime(date("Y-m-d 00:00:00"))),
                            "d_date" => strtotime(date("Y-m-d")),
                        ];
                        $this->addMachineOnlineDetails($insert);
                    } else {
                        // 修改设备在线记录
                        $update = [
                            'mod_id' => $value['mod_id'],
                            'offline_time' => time(),
                            'duration' => bcsub(time(), $value['online_time']),
                        ];
                        $flag[] = $this->updateMachineOnlineDetails($update);
                        actionLog($this->getLS(),'修改设备在线记录详情状态','checkOffline');
                    }
                    $upData = [
                        'm_id' => $value['m_id'],
                        'online' => 2,
                        'sighKey' => "",
                    ];
                    //查找machine_mq_record表是否有path等于/httpHeartbeat且时间在15分钟内的记录
                    $mqCount = Db::name('machine_mq_record')
                    ->where('m_id', $value['m_id'])
                    ->where('path', '/httpHeartbeat')
                    ->where('create_time', '>=', time() - 900)->count();
                    if(!$mqCount){
                        $upData['http_online'] = 2;
                    }
                    $flag[] = $this->updateMachine($upData);
                    actionLog($this->getLS(),'修改设备在线状态','checkOffline');

                    /** 发送离线通知 开始 **/
                    $machine = $this->getMachineFind(['m_id' => $value['m_id']], 'm_id,machine_id,machine_name,last_online_time,ao_id');
                    if ($machine) {
                        $machine = $machine->toArray();
                        $machine['online'] = "offline";
                        $this->noticeSendData = [
                            "ao_id" => $machine['ao_id'],
                            "m_id" => $machine['m_id'],
                            "templateType" => "online",
                            "replaceData" => $machine,
                        ];
                        // $machine['errorCode'] = '在营设备离线';
                        // $machine['date'] = date("Y年m月d日");
                        // $machine['exceptionDeclaration'] = '在营设备离线';
                        // $machine['error_code'] = '在营设备离线';
                        // $machine['error_time'] = date('Y-m-d H:i:s');
                        // $machine['error_info'] = 11103021; // 在营设备离线
                        // $this->noticeSendData = [
                        //     "ao_id" => $machine['ao_id'],
                        //     "m_id" => $machine['m_id'],
                        //     "templateType" => "mFault",
                        //     "replaceData" => $machine,
                        // ];

                        $this->noticeSend();
                    }
                    /** 发送离线通知 结束 **/
                }
                $this->commitTrans();
                //            sleep(10);
                            actionLog($flag,'检查掉线的在线记录','checkOffline');
            } catch (\Exception $e) {
                $this->rollbackTrans();
                actionLog($e->getFile() . "_" . $e->getLine() . "_" . $e->getMessage(),'tryCatchMessage', 'checkOffline');
                actionLog($e->getTrace(), 'tryCatchTrace','checkOffline');
            }
        }

        try {
            $httpList = Db::name('machine')
                ->where('online', 2)
                ->where('http_online', '<>', 2)
                ->field('m_id,machine_id,http_online')
                ->select()
                ->toArray();
            foreach ($httpList as $item) {
                $mqCount = Db::name('machine_mq_record')
                    ->where('m_id', $item['m_id'])
                    ->where('path', '/httpHeartbeat')
                    ->where('create_time', '>=', time() - 900)
                    ->count();
                if (!$mqCount) {
                    $httpFlag[] = $this->updateMachine([
                        'm_id' => $item['m_id'],
                        'http_online' => 2,
                    ]);
                }
            }
            actionLog($httpFlag, '补偿处理HTTP在线状态', 'checkOffline');
        } catch (\Exception $e) {
            actionLog($e->getFile() . "_" . $e->getLine() . "_" . $e->getMessage(), 'httpCheckTryCatchMessage', 'checkOffline');
            actionLog($e->getTrace(), 'httpCheckTryCatchTrace', 'checkOffline');
        }

        return "处理成功";
    }

    /**
     * 定时任务-10min执行一次，将当天需要执行定时开关机的设备轮询检查
     */
    public function checkOnOff(){
        // $redis = new Redis();
        // $redis->connect(FacadeEnv::get('REDIS_host'),FacadeEnv::get('REDIS_port'));
        $dayWeek = intval(date('w'));
        $time = strtotime(date('H:i'));

        $sql = Db::name('machine_on_off')->where('on_off_machine','<>','')->whereNotNull('on_off_machine')->json(['on_off_machine'])->field('machine_id,on_off_machine')->order('machine_id desc')->select();
        // 查询执行当天有包含定时开关机任务的设备
        $deviceMch = [];
        foreach($sql as $item){
            if(array_key_exists($dayWeek,$item['on_off_machine'])){
                $Mchtime = explode(',',$item['on_off_machine'][$dayWeek]);
                if(isset($Mchtime[0])&&isset($Mchtime[1])&&$Mchtime[0]!='null'&&$Mchtime[1]!='null'){
                    $Mchtime[0] = !empty($Mchtime[0])?strtotime($Mchtime[0]):0;
                    $Mchtime[1] = !empty($Mchtime[1])?strtotime($Mchtime[1]):0;
                    $deviceMch[$item['machine_id']] = $Mchtime;
                }
                continue;
            }
        }
        // 记录设备
        actionLog(json_encode($deviceMch),"需要执行定时开关机的设备",'checkOnOff');

        // 对±1hour需要定时开关机的设备做处理
        $device = [];
        foreach($deviceMch as $machine_id=>$machine_time){
            if($machine_time[0]<$machine_time[1] && time()>$machine_time[1]) continue;
            if($machine_time[0] > $time-1800 && $machine_time[0] <= $time+1800){
                // redis添加设备直接执行定时任务记录
                $device[$machine_id] = $machine_time;
                if($machine_time[0] <= $time){
                    $otherData['time_point'] = time();
                    $otherData['power_time'] = abs(time()-$machine_time[1]);
                } else {
                    $otherData['time_point'] = abs($machine_time[0]-time());
                    $otherData['power_time'] = abs($machine_time[0]-$machine_time[1]);
                }
                // $otherData['time_point'] = date('H:i',$otherData['time_point']);
                // $otherData['power_time'] = date('H:i',$otherData['power_time']) ;
                // var_dump([$machine_id=>$otherData]);
                $res = $this->sendToMachine(['machine_id'=>$machine_id],'powerWakeUp',$otherData);
                actionLog('执行结果'.$res,'设备'.$machine_id,'checkOnOff');
            }
        }
    }

    /**
     * 定时任务-建议每15分钟执行一次，检查运营中设备是否在开机窗口内超过15分钟仍未开机
     */
    public function checkOperatingStartup()
    {
        try {
            $now = time();
            $hour = intval(date('H', $now));
            $today = date('Y-m-d');
            $todayKey = date('Ymd');
            $ttl = strtotime(date('Y-m-d 23:59:59', $now)) - $now;//当前时间距离当天结束的秒数，用于设置缓存过期时间
            $intervals = [900, 1800, 3600, 5400, 7200];// 阶梯秒数：15、30、60、90、120分钟
            $firstInterval = intval($intervals[0] ?? 900);// 首个阶段时间，默认15分钟
            // 每天22:00-次日06:00跳过，不执行查库
            if ($hour >= 22 || $hour < 6) {
                actionLog(date('Y-m-d H:i:s', $now), '静默时段跳过未开机巡检', 'checkOperatingStartup');
                return '静默时段跳过';
            }

            $week = intval(date('N')) - 1; // 0-6 => 周一到周日

            $query = Db::name('machine')->alias('m')
                    ->join('machine_on_off moo', 'moo.m_id = m.m_id', 'left');
            $title = '';
            if (env('CglPay.is_test')) {
                // 测试环境仅查询特定设备，方便测试验证
                $query = $query->where('m.machine_id', 'JCHM-H2D-0064')->where('m.online', 2)->where('m.http_online', 2);
                $title = '测试';
            }else{
                //只查询最近的2天有在线记录的设备，避免查询历史数据较多的设备，影响巡检效率
                $query = $query->where('m.online', 2)
                    ->where('m.http_online', 2)
                    ->where('m.is_operating', 1)
                    ->where('m.last_online_time', '>', strtotime('-2 day'))
                    ->whereNotNull('moo.on_off_machine')
                    ->where('moo.on_off_machine', '<>', '')
                    ->where('moo.on_off_machine', '<>', '{}')
                    ->where('moo.status', 1);
            }
            $list = $query
                    ->field('m.m_id,m.machine_id,m.machine_name,m.online,m.http_online,m.last_online_time,m.ao_id,moo.on_off_machine')
                    ->order('m.m_id desc')
                    ->select();
            if(count($list) > 0){
                $list = $list->toArray();
                $flag = [];
                foreach ($list as $item) {
                    $onOffMachine = $item['on_off_machine'];
                    if (is_string($onOffMachine)) {
                        $onOffMachine = json_decode($onOffMachine, true);
                    }
                    if (!is_array($onOffMachine)) {
                        continue;
                    }

                    $weekKey = (string)$week;
                    if (empty($onOffMachine[$weekKey])) {
                        continue;
                    }

                    $onOffTime = explode(',', $onOffMachine[$weekKey]);
                    if (!isset($onOffTime[0]) || !isset($onOffTime[1])) {
                        continue;
                    }
                    $shutdownTime = trim($onOffTime[0]);
                    $startupTime = trim($onOffTime[1]);
                    if (!$shutdownTime || !$startupTime || $shutdownTime === 'null' || $startupTime === 'null') {
                        continue;
                    }

                    $startupTimestamp = strtotime($today . ' ' . $startupTime.':00');
                    $shutdownTimestamp = strtotime($today . ' ' . $shutdownTime.':00');
                    if (!$startupTimestamp || !$shutdownTimestamp) {
                        continue;
                    }

                    // 仅支持当日营业窗口：关机时间必须晚于开机时间
                    if ($shutdownTimestamp < $startupTimestamp) {
                        actionLog([
                            'm_id' => $item['m_id'],
                            'machine_id' => $item['machine_id'],
                            'startup_time' => $startupTime,
                            'shutdown_time' => $shutdownTime,
                        ], '无效营业时间配置(不支持跨天)，跳过巡检', 'checkOperatingStartup');
                        continue;
                    }
                    // 仅在开机窗口内进行检查，且开机后15分钟内不告警
                    if ($now < $startupTimestamp || $now > $shutdownTimestamp) {
                        continue;
                    }
                    if ($now <= $startupTimestamp + $firstInterval) {
                        continue;
                    }

                    $elapsed = $now - $startupTimestamp;//当前时间与开机时间的时间差
                    $stageCacheKey = 'machine_startup_exception_stage:' . $item['m_id'] . ':' . $todayKey;
                    $sentStage = intval(Cache::get($stageCacheKey, 0));
                    $nextStage = $sentStage + 1;
                    //如果已发送阶段超过设定的阶梯数，则不再发送
                    if ($nextStage > count($intervals)) {
                        continue;
                    }
                    $needSeconds = intval($intervals[$nextStage - 1]);
                    if ($elapsed < $needSeconds) {//未达到下一个阶段的时间要求，继续等待
                        continue;
                    }
                    $currentStage = $nextStage;

                    $item['errorCode'] = '在营设备未开机'.$title;
                    $item['date'] = date("Y年m月d日");
                    $item['exceptionDeclaration'] = '在营设备未开机';
                    $item['error_code'] = '在营设备未开机'.$title;
                    $item['error_time'] = date('Y-m-d H:i:s');
                    $item['error_info'] = 11102011; // 在营设备未开机
                    $item['machine_name'] = mb_substr($item['machine_name'], 0, 20, 'UTF-8');

                    $this->noticeSendData = [
                        "ao_id" => $item['ao_id'],
                        "m_id" => $item['m_id'],
                        "templateType" => "mFault",
                        "replaceData" => $item,
                    ];

                    $flag[] = $this->noticeSend();
                    //当前阶段的通知发送成功后，更新已发送阶段数缓存，过期时间为当天23:59:59
                    Cache::set($stageCacheKey, $currentStage, $ttl > 0 ? $ttl : 60);
                    actionLog([
                        'm_id' => $item['m_id'],
                        'machine_id' => $item['machine_id'],
                        'stage' => $currentStage,
                        'elapsed' => $elapsed,
                        'need_seconds' => $needSeconds,
                    ], '发送设备未开机提醒', 'checkOperatingStartup');
                }
                actionLog($flag, '处理运营中设备未开机提醒结果', 'checkOperatingStartup');
            }
            
            // // 新增：设备已开机但10分钟以上未进入首页发送模板通知
            // $homeFlag = [];
            // $homeTimeout = 600; // 10分钟
            // $homeRecordPath = 'currentStatus';
            // $homeSkipCacheKey = 'machine_startup_home_skip_list:' . $todayKey;
            // $homeSkipMIds = Cache::get($homeSkipCacheKey, []);
            // if (!is_array($homeSkipMIds)) {
            //     $homeSkipMIds = [];
            // }
            // $onlineQuery = Db::name('machine')->alias('m');
            // if (env('CglPay.is_test')) {
            //     // 测试环境仅查询特定设备，方便测试验证
            //     $onlineQuery = $onlineQuery->where('m.machine_id', 'JCHM-H2D-0064')->where('m.online', 1);
            // } else {
            //     // 仅查询当前在线的在营设备
            //     $onlineQuery = $onlineQuery->where('m.online', 1)
            //         ->where('m.is_operating', 1);
            // }

            // $onlineList = $onlineQuery
            //     ->when($homeSkipMIds, function ($query) use ($homeSkipMIds) {
            //         $query->whereNotIn('m.m_id', $homeSkipMIds);
            //     })
            //     ->field('m.m_id,m.machine_id,m.machine_name,m.online,m.http_online,m.last_online_time,m.ao_id')
            //     ->order('m.m_id desc')
            //     ->select();
            // if (count($onlineList) > 0) {
            //     $onlineList = $onlineList->toArray();
            //     foreach ($onlineList as $item) {
            //         $currentOnline = $this->getMachineOnlineDetailsFind([
            //             'm_id' => $item['m_id'],
            //             'offline_time' => 0,
            //             'd_date' => strtotime(date('Y-m-d')),
            //         ], 'mod_id,online_time', 'mod_id asc');
            //         if (!$currentOnline || empty($currentOnline['online_time'])) {
            //             continue;
            //         }
            //         $onlineTimestamp = intval($currentOnline['online_time']);
            //         $checkEnd = $onlineTimestamp + $homeTimeout;
                
            //         // 当前在线会话上线不足10分钟，继续等待
            //         if ($now <= $checkEnd) {
            //             continue;
            //         }

            //         $homeCount = Db::name('machine_mq_record')
            //             ->where('m_id', $item['m_id'])
            //             ->where('path', $homeRecordPath)
            //             ->whereLike('content', '%home%')
            //             ->where('create_time', '>=', $onlineTimestamp)
            //             ->where('create_time', '<=', $checkEnd)
            //             ->count();
            //         if ($homeCount > 0) {
            //             $homeSkipMIds[] = $item['m_id'];
            //             $homeSkipMIds = array_values(array_unique($homeSkipMIds));
            //             Cache::set($homeSkipCacheKey, $homeSkipMIds, $ttl > 0 ? $ttl : 60);
            //             continue;
            //         }

            //         $item['errorCode'] = '设备已开机未进入首页' . $title;
            //         $item['date'] = date('Y年m月d日');
            //         $item['exceptionDeclaration'] = '设备已开机未进入首页';
            //         $item['error_code'] = '设备已开机未进入首页' . $title;
            //         $item['error_time'] = date('Y-m-d H:i:s');
            //         $item['error_info'] = 11102012; // 设备已开机未进入首页
            //         $item['machine_name'] = mb_substr($item['machine_name'], 0, 20, 'UTF-8');

            //         $this->noticeSendData = [
            //             'ao_id' => $item['ao_id'],
            //             'm_id' => $item['m_id'],
            //             'templateType' => 'mFault',
            //             'replaceData' => $item,
            //         ];

            //         $homeFlag[] = $this->noticeSend();
            //         $homeSkipMIds[] = $item['m_id'];
            //         $homeSkipMIds = array_values(array_unique($homeSkipMIds));
            //         Cache::set($homeSkipCacheKey, $homeSkipMIds, $ttl > 0 ? $ttl : 60);
            //         actionLog([
            //             'm_id' => $item['m_id'],
            //             'machine_id' => $item['machine_id'],
            //             'online_timestamp' => $onlineTimestamp,
            //             'check_end' => $checkEnd,
            //             'check_time' => $now,
            //         ], '发送设备已开机未进入首页提醒', 'checkOperatingStartup');
            //     }
            // }
            // actionLog($homeFlag, '处理设备已开机未进入首页提醒结果', 'checkOperatingStartup');
        } catch (\Exception $e) {
            actionException($e, 1, 'checkOperatingStartup');
            return '处理异常';
        }

        return '处理成功';
    }
//    public function machineUploadQueue()
//    {
//        $where[] = ['status',"in",[1,3]];
//        $list = $this->getMachineList($where,0,'m_id,machine_id,mac_address,(' . time() . ") timeStamp " );
//        if ($list) $list = $list->toArray();
//        cache("machineUploadQueueList",$list);
//    }

    /**
     * 每天0点1分同步物联卡信息并统计昨日流量
     * 命令示例：php think time_task machine updateSimCardUsage
     * 可选参数：date=YYYY-mm-dd（默认当天）
     * @return string
     */
    public function updateSimCardUsage()
    {
        $date = date('Y-m-d',strtotime("-1 days"));
        $y_date = date('Y-m-d',strtotime("-2 days"));
        $machineInfoList = Db::name('machine_info')->alias('a')
            ->join('machine b', 'a.m_id = b.m_id')
            ->whereNotNull('a.iccid')
            ->where('a.iccid', '<>', '')
            ->where('a.iccid', '<>', '0')
            ->whereIn('b.is_operating', [1,3])//在营、外售
            ->field('a.m_id,b.machine_id,a.iccid')
            ->select()
            ->toArray();

        if (!$machineInfoList) {
            return '无可同步的物联卡';
        }

        $iccidList = array_values(array_unique(array_column($machineInfoList, 'iccid')));

        $batchRes = Simiot::queryCardBatch($iccidList, 90);
        if (!is_array($batchRes)) {
            return '同步失败：榫卯接口调用失败';
        }
        if (!isset($batchRes['result']) || !is_array($batchRes['result'])) {
            return '同步失败：榫卯接口返回数据异常';
        }
        $cardMap = [];
        foreach ($batchRes['result'] as $card) {
            $iccid = strval($card['iccid'] ?? '');
            $res_code = intval($card['code'] ?? -1);
            if ($iccid !== '' && $res_code === 0) {
                $cardMap[$iccid] = $card;
            }
        }

        $existTodayRows = Db::name('sim_card_machine')
            ->where('date', $date)
            ->whereIn('iccid', $iccidList)
            ->field('m_id,machine_id,iccid,date')
            ->select()
            ->toArray();
        $existTodayMap = [];
        foreach ($existTodayRows as $row) {
            $key = intval($row['m_id']) . '|' . strval($row['machine_id']) . '|' . strval($row['iccid']) . '|' . strval($row['date']);
            $existTodayMap[$key] = 1;
        }

        $prevRows = Db::name('sim_card_machine')
            ->whereIn('iccid', $iccidList)
            ->where('date', $y_date)
            ->field('iccid,total_usage,date,id')
            ->order('iccid asc,date desc,id desc')
            ->select()
            ->toArray();
        $prevMap = [];
        foreach ($prevRows as $row) {
            $iccid = strval($row['iccid']);
            if (!isset($prevMap[$iccid])) {
                $prevMap[$iccid] = $row['total_usage'] ?? 0;
            }
        }

        $insertInfoRows = [];
        $updateInfoRows = [];
        $insertMachineRows = [];
        $success = 0;
        $fail = 0;

        foreach ($machineInfoList as $item) {
            try {
                $iccid = strval($item['iccid']);
                if (!isset($cardMap[$iccid])) {
                    $fail++;
                    actionLog(['iccid' => $iccid], '物联卡信息同步失败，未返回该iccid数据');
                    continue;
                }

                $card = $cardMap[$iccid] ?? [];
                if (!$card || !is_array($card)) {
                    $fail++;
                    actionLog(['iccid' => $iccid], '物联卡信息为空');
                    continue;
                }

                $infoData = $this->mapSimCardInfoData($item, $card);
                $existInfo = $this->getSimCardInfoFind([
                    'm_id' => $item['m_id'],
                    'machine_id' => $item['machine_id'],
                    'iccid' => $iccid,
                ], 'id');
                if ($existInfo) {
                    $infoData['id'] = $existInfo['id'];
                    $updateInfoRows[] = $infoData;
                } else {
                    $insertInfoRows[] = $infoData;
                }

                $totalUsage = $card['current_period_usage'] ?? 0;
                $compositeKey = $item['m_id'] . '|' . $item['machine_id'] . '|' . $iccid . '|' . $date;
                if (!isset($existTodayMap[$compositeKey])) {
                    $prevTotal = $prevMap[$iccid] ?? 0;
                    $usage = bcsub($totalUsage, $prevTotal, 2);
                    if ($usage < 0) {
                        $usage = 0;
                    }
                    $insertMachineRows[] = [
                        'm_id' => $item['m_id'],
                        'machine_id' => $item['machine_id'],
                        'iccid' => $iccid,
                        'date' => $date,
                        'total_usage' => $totalUsage,
                        'usage' => $usage,
                        'machine_usage' => 0,
                        'camera_usage' => 0,
                        'remark' => '',
                    ];
                    $existTodayMap[$compositeKey] = 1;
                    $prevMap[$iccid] = $totalUsage;
                }
                $success++;
            } catch (\Throwable $e) {
                $fail++;
                actionException($e, 1);
            }
        }

        if ($insertInfoRows) {
            Db::name('sim_card_info')->insertAll($insertInfoRows);
        }
        if ($updateInfoRows) {
            foreach ($updateInfoRows as $updateRow) {
                $id = $updateRow['id'];
                unset($updateRow['id']);
                $this->updateSimCardInfo($updateRow, ['id' => $id]);
            }
        }
        if ($insertMachineRows) {
            Db::name('sim_card_machine')->insertAll($insertMachineRows);
        }

        return "处理成功，总数:" . count($machineInfoList) . "，成功:" . $success . "，失败:" . $fail . "，基础新增:" . count($insertInfoRows) . "，日流量新增:" . count($insertMachineRows);
    }

    protected function mapSimCardInfoData($old, $card)
    {
        $power_status = 2; // 默认未知
        $online_status = 2; // 默认未知
        if (isset($card['power_status'])) {
            if (in_array($card['power_status'], [0, '0', 'off', 'OFF'])) {
                $power_status = 0;
            } elseif (in_array($card['power_status'], [1, '1', 'on', 'ON'])) {
                $power_status = 1;
            }
        }
        if (isset($card['online_status'])) {
            if (in_array($card['online_status'], [0, '0', 'offline', 'OFFLINE'])) {
                $online_status = 0;
            } elseif (in_array($card['online_status'], [1, '1', 'online', 'ONLINE'])) {
                $online_status = 1;
            }
        }
        return [
            'm_id' => $old['m_id'],
            'machine_id' => $old['machine_id'],
            'iccid' => $old['iccid'],
            'carrier' => $card['carrier'] ?? '',
            'carrier_id' => $card['carrier_id'] ?? 0,
            'msisdn' => $card['msisdn'] ?? $card['mobile'] ?? '',
            'imsi' => $card['imsi'] ?? '',
            'allocated_at' => empty($card['allocated_at']) ? null : $card['allocated_at'],
            'silent_period_end_date' => empty($card['silent_period_end_date']) ? null : $card['silent_period_end_date'],
            'activated_time' => empty($card['activated_time']) ? (empty($card['activated_at']) ? null : $card['activated_at']) : $card['activated_time'],
            'service_end_time' => empty($card['service_end_time']) ? null : $card['service_end_time'],
            'expect_cancel_time' => empty($card['expect_cancel_time']) ? null : $card['expect_cancel_time'],
            'life_cycle' => $card['life_cycle'] ?? 0,
            'network_status' => $card['network_status'] ?? 0,
            'imei' => $card['imei'] ?? '',
            'device_card_status' => $card['device_card_status'] ?? '',
            'power_status' => $power_status,
            'online_status' => $online_status,
            'business_type' => $card['package'][0]['business_type'] ?? 0,
            'number' => $card['package'][0]['number'] ?? '',
            'title' => $card['package'][0]['title'] ?? '',
            'service_period' => $card['package'][0]['service_period'] ?? 0,
            'service_period_type' => $card['package'][0]['service_period_type'] ?? 0,
            'package_capacity' => $card['package'][0]['package_capacity'] ?? 0,
            'capacity_type' => $card['package'][0]['capacity_type'] ?? '',
            'voice_capacity' => $card['package'][0]['voice_capacity'] ?? 0,
            'subscribed_time' => empty($card['package'][0]['subscribed_time']) ? null : $card['package'][0]['subscribed_time'],
            'start_time' => empty($card['package'][0]['start_time']) ? null : $card['package'][0]['start_time'],
            'end_time' => empty($card['package'][0]['end_time']) ? null : $card['package'][0]['end_time'],
            'periods' => $card['package'][0]['periods'] ?? 0,
            'period_list' => $card['package'][0]['period_list'] ?? '',
            'current_period_begin_time' => empty($card['package'][0]['current_period_begin_time']) ? null : $card['package'][0]['current_period_begin_time'],
            'current_period_end_time' => empty($card['package'][0]['current_period_end_time']) ? null : $card['package'][0]['current_period_end_time'],
            'current_period_usage' => $card['package'][0]['current_period_usage'] ?? 0,
            'current_period_voice_usage' => $card['package'][0]['current_period_voice_usage'] ?? 0,
            'future_package_count' => $card['package'][0]['future_package_count'] ?? 0,
            'future_cycle_count' => $card['package'][0]['future_cycle_count'] ?? 0,
        ];
    }

}