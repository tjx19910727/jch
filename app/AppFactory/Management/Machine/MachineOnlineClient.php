<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/2/24
 * Time: 8:55
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Kernel\Traits\Machine\MachineOnlineSnapshotTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineOnlineTrait;
use app\AppFactory\Management\ManagementClient;

class MachineOnlineClient extends ManagementClient
{
    use MachineOnlineTrait, MachineOnlineSnapshotTrait;

    public function export($where)
    {
        $list = $this->getMachineOnlineList($where,0,'online_id,machine_id,machine_name,ckc_duration,duration,online_frequency,online_date,create_time');
        if ($list) {
            $list = $list->toArray();
            if ($list) {
                foreach ($list as $key => $value) {
                    $value['ckc_duration'] = $this->int2HourMinuteSec($value['ckc_duration']);
                    $list[$key] = $value;
                }
                $title = [
                    "online_id" => "ID",
                    "machine_id" => "设备编号",
                    "machine_name" => "设备名称",
                    "ckc_duration" => "设定运营时长",
                    "duration" => "在线时长",
                    "online_frequency" => "上线次数",
                    "online_date" => "营业日期",
                    "create_time" => "创建时间",
                ];
                $filename = "运营报表-" . date("Ymd");
                return $this->sendToExport("统计报表-运营报表",$filename,$title,$list);
            }
        }
        return $this->r(100, $this->lang("query_fail"));
    }

    /**
     * Export online snapshots for operating machines from today 00:00 to now.
     */
    public function exportTodayOperatingSnapshot($where = [])
    {
        $today = strtotime(date("Y-m-d"));
        $now = time();
        $where[] = ['record_date', '=', $today];
        $where[] = ['collect_time', 'between', [$today, $now]];
        $where[] = ['is_operating', '=', 1];

        $field = 'mos_id,machine_id,machine_name,online,is_operating,ckc_status,collect_time,slot_start_time,business_start_time,business_end_time,create_time';
        $list = $this->getMachineOnlineSnapshotList($where, 0, $field, 'collect_time asc, machine_id asc');
        if (!$list) {
            return $this->r(100, $this->lang("query_fail"));
        }

        $list = $list->toArray();
        if (!$list) {
            return $this->r(100, $this->lang("query_fail"));
        }

        foreach ($list as $key => $value) {
            $value['online'] = intval($value['online']) === 1 ? 'online' : 'offline';
            $value['is_operating'] = intval($value['is_operating']) === 1 ? 'yes' : 'no';
            $value['ckc_status'] = intval($value['ckc_status']) === 1 ? 'normal' : 'pause';
            $value['collect_time'] = date("Y-m-d H:i:s", intval($value['collect_time']));
            $value['slot_start_time'] = date("Y-m-d H:i:s", intval($value['slot_start_time']));
            $value['business_start_time'] = Int2HourMinuteSec(intval($value['business_start_time']));
            $value['business_end_time'] = Int2HourMinuteSec(intval($value['business_end_time']));
            $value['create_time'] = date("Y-m-d H:i:s", intval($value['create_time']));
            $list[$key] = $value;
        }

        $title = [
            'mos_id' => 'ID',
            'machine_id' => 'Machine ID',
            'machine_name' => 'Machine Name',
            'online' => 'Online Status',
            'is_operating' => 'Operating',
            'ckc_status' => 'Business Status',
            'collect_time' => 'Collect Time',
            'slot_start_time' => 'Slot Start Time',
            'business_start_time' => 'Business Start',
            'business_end_time' => 'Business End',
            'create_time' => 'Create Time',
        ];
        $filename = "machine_online_snapshot_" . date("Ymd_His");
        return $this->sendToExport("统计报表-设备在线状态快照", $filename, $title, $list);
    }
}
