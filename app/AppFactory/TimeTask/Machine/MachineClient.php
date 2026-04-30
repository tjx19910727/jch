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
use app\AppFactory\Kernel\Traits\Machine\MachineOnlineTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineOnOffTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersTrait;
use app\AppFactory\Kernel\Traits\Send\ToManagerTrait;
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
    use MachineOnlineTrait,MachineOnlineDetailsTrait,MachineTrait,MachineOnOffTrait,MachineMqRecordTrait;
    use SaleOrdersTrait;
    use AuthManagerMachineTrait;
    use ActivityCouponUsedTrait;
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
     * 定时任务-30秒查询一次，间隔3分钟，检查掉线的在线记录
     */
    public function checkOffline()
    {
        $details = $this->getMachineOnlineDetailsList(['offline_time' => 0, ['heart_time', '<', time() - env("machine.timeout",60)]], 0, 'mod_id,m_id,machine_name,machine_id,online_time,d_date');
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
                    $flag[] = $this->updateMachine(['m_id' => $value['m_id'], 'online' => 2,'sighKey' => ""]);
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
     * 定时任务-建议每5分钟执行一次，检查运营中设备是否在开机窗口内超过5分钟仍未开机
     */
    public function checkOperatingStartup()
    {
        try {
            $now = time();
            $hour = intval(date('H', $now));
            $today = date('Y-m-d');
            $todayKey = date('Ymd');
            $ttl = strtotime(date('Y-m-d 23:59:59', $now)) - $now;//当前时间距离当天结束的秒数，用于设置缓存过期时间
            $intervals = [300, 900, 1800, 3600, 7200];// 阶梯秒数：5、15、30、60、120分钟
            $firstInterval = intval($intervals[0] ?? 300);
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
                $query = $query->where('m.machine_id', 'JCHM-H2D-0064')->where('m.online', 2);
                $title = '测试';
            }else{
                //只查询最近的2天有在线记录的设备，避免查询历史数据较多的设备，影响巡检效率
                $query = $query->where('m.online', 2)
                    ->where('m.is_operating', 1)
                    ->where('m.last_online_time', '>', strtotime('-2 day'))
                    ->whereNotNull('moo.on_off_machine')
                    ->where('moo.on_off_machine', '<>', '')
                    ->where('moo.on_off_machine', '<>', '{}')
                    ->where('moo.status', 1);
            }
            $list = $query
                    ->field('m.m_id,m.machine_id,m.machine_name,m.online,m.last_online_time,m.ao_id,moo.on_off_machine')
                    ->order('m.m_id desc')
                    ->select();
            if(count($list) == 0){
                return '无需处理';
            }
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
                // 仅在开机窗口内进行检查，且开机后5分钟内不告警
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
                $item['machine_name'] = $item['machine_id'];

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
}