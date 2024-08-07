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
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

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
            $yesterday = $yesterday ? strtotime($yesterday) : strtotime(date("Y-m-d", strtotime("-1 days")));
            $machine = $this->getMachineList([], 0, 'm_id,machine_id,machine_name');
            if ($machine) {
                $machine = $machine->toArray();
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
                    $field = "m_id,machine_name,machine_id,sum(duration) duration ,count(mod_id) online_frequency,d_date online_date";
                    $onlineDetails = $this->getMachineOnlineDetailsFind($where, $field, 'mod_id desc', "m_id");
                    if ($onlineDetails) {
                        $onlineDetails = $onlineDetails->toArray();
                        $manager_id = $this->getAuthManagerMachineValue(['m_id' => $value['m_id']], 'manager_id', 'manager_id desc');
                        $onlineDetails['manager_id'] = $manager_id;
                        // 获取运营设定，计算原定运营时长，校对判断运营状态，统计上线离线次数
                        $ckcDuration = 86399;
                        $ckc = $this->getMachineOnOffFind(['m_id' => $value['m_id'],'status' => 1],'on_off_ckc');
                        if ($ckc) {
                            $ckc = $ckc->toArray();
                            $ckcList = json_decode($ckc['on_off_ckc'],true);
                            if ($ckcList) {
                                $thisWeek = date("w",strtotime(date("Y-m-d", $yesterday) . " -1 days")); // 昨天本周几，0~6，周日至周六
                                $thisWeek--;                    // 减1，-1~5，0~5为周一至周六，-1为周日
                                if ($thisWeek < 0) $thisWeek = 6;    // -1重置为6，周日
                                if (isset($ckcList[$thisWeek])) {
                                    // 获取本周几（昨天）设置的营业时间
                                    $ckcTime = explode(",",$ckcList[$thisWeek]);
                                    if ($ckcTime) {
                                        $endTime = HourMinuteSec2int($ckcTime[0]);
                                        $startTime = HourMinuteSec2int($ckcTime[1]);
                                        $ckcDuration = $endTime - $startTime;
                                    }
                                }
                            }
                        }
                        $onlineDetails['ckc_duration'] = $ckcDuration;
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
            actionException($e,1);
        } catch (ModelNotFoundException $e) {
            actionException($e,1);
        } catch (DbException $e) {
            actionException($e,1);
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
                    $flag[] = $this->updateMachine(['m_id' => $value['m_id'], 'online' => 2]);
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
}