<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/2/23
 * Time: 12:05
 */

namespace app\AppFactory\TimeTask\Machine;


use app\AppFactory\Kernel\Traits\Activity\ActivityCouponUsedTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineOnlineDetailsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineOnlineTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersTrait;
use app\AppFactory\TimeTask\TimeTaskBase;

class MachineClient extends TimeTaskBase
{
    use MachineOnlineTrait,MachineOnlineDetailsTrait,MachineTrait;
    use SaleOrdersTrait;
    use ActivityCouponUsedTrait;

    /**
     * 定时任务，每天定时一次，结算昨天在线时长
     */
    public function countOnline()
    {
        $yesterday = strtotime(date("Y-m-d",strtotime("-1 days")));
        $where["d_date"] = $yesterday;
        $where['online_id'] = 0;
        $field = "m_id,machine_name,machine_id,sum(duration) duration ,d_date online_date";
        $onlineDetails = $this->getMachineOnlineDetailsList($where,0,$field,'','','machine_id')->toArray();
        if ($onlineDetails) {
            $flag[] = 1;
            foreach ($onlineDetails as $key => $value) {
                $value['manager_id'] = $this->getMachineValue(['m_id' => $value['m_id']], "manager_id");
                $online_id = $this->addMachineOnline($value);
                if ($online_id) {
                    // 生成结算记录ID，修改每天在线详情绑定的结算记录ID
                    $update['online_id'] = $online_id;
                    $where['d_date'] = $value['online_date'];
                    $where['m_id'] = $value['m_id'];
                    $this->updateMachineOnlineDetails($update,$where);
                }
            }
            actionLog($flag, '结算昨天在线时长', 'countOnline');
        }
        return "处理成功";
    }

    /**
     * 定时任务-30秒查询一次，间隔3分钟，检查掉线的在线记录
     */
    public function checkOffline()
    {
        $details = $this->getMachineonlineDetailsList(['offline_time' => 0, ['heart_time', '<', time() - 180]], 0, 'mod_id,m_id,machine_name,machine_id,online_time,d_date');
        if ($details) {
            $flag[] = 1;
            foreach ($details as $key => $value) {
                // 跨天处理
                if ($value['d_date'] != strtotime(date("Y-m-d"))) {
                    // 结束昨天的在线记录
                    $offlineTime = strtotime(date("Y-m-d 23:59:59",$value['d_date']));
                    $update['mod_id'] = $value['mod_id'];
                    $update['offline_time'] = $offlineTime;
                    $update['heart_time'] = $offlineTime;
                    $update['duration'] = bcsub($offlineTime,  $value['online_time']);
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
                        "duration" => bcsub(time(),strtotime(date("Y-m-d 00:00:00"))),
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
                }
                $flag[] = $this->updateMachine(['m_id' => $value['m_id'],'online' => 2]);
            }
//            actionLog($flag,'检查掉线的在线记录','checkClose');
        }
        return "处理成功";
    }
}