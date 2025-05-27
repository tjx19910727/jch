<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/2/24
 * Time: 8:55
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Kernel\Traits\Machine\MachineOnlineTrait;
use app\AppFactory\Management\ManagementClient;

class MachineOnlineClient extends ManagementClient
{
    use MachineOnlineTrait;

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
}