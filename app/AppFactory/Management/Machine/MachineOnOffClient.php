<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/8
 * Time: 9:51
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Kernel\Support\Excel;
use app\AppFactory\Kernel\Traits\Machine\MachineOnOffTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Management\ManagementClient;

class MachineOnOffClient extends ManagementClient
{
    use MachineOnOffTrait, MachineTrait;

    public function addOf($postData)
    {
        $flag = [];
        $mIds = explode(",",$postData['m_id']);
        unset($postData['m_id']);
        if ($mIds) {
            foreach ($mIds as $m_id) {
                $m_id = trim($m_id);
                if (!$m_id) continue;
                $check = $this->getMachineOnOffFind(['m_id' => $m_id]);
                if ($check) {
                    $check = $check->toArray();
                    $update = array_merge($check,$postData);
                    if($check['ao_id'] == 0) $update["ao_id"] = $this->manager['ao_id'];
                    $result = $this->updateMachineOnOff($update);
                    $flag[] = $result;
                    if ($result) {
                        $this->sendToMachine(['machine_id' => $check['machine_id']], 'updateMachineOnOff');
                    }
//                    return $this->rFail($check['machine_id'] . ": " . $this->lang("VMachineOnOff.is_exists"));
                } else {
                    $machine = $this->getMachineFind(['m_id' => $m_id], 'm_id,machine_id,machine_name,ao_id');
                    if (!$machine) return $this->rFail($this->lang("query_machine_no_data"));
                    $machine = $machine->toArray();
                    $insert = array_merge($postData, $machine);
                    $insert["ao_id"] = $this->manager['ao_id'];
                    $addOf = $this->addMachineOnOff($insert);
                    if ($addOf) {
                        $flag[] = 1;
                        $this->sendToMachine(['machine_id' => $machine['machine_id']], 'updateMachineOnOff');
                    }
                }
            }
        }
        $result = $this->checkFlag($flag);
        return $this->rA($result);
    }

    public function updateOf($postData)
    {
        $result = $this->updateMachineOnOff($postData);
        if ($result) {
            $machine = $this->getMachineOnOffFind(['moo_id' => $postData['moo_id']],'machine_id')->toArray();
            $this->sendToMachine($machine,'updateMachineOnOff');
        }
        return $this->rU($result);
    }

    public function delOf($where)
    {
        $machine = $this->getMachineOnOffFind($where,'machine_id')->toArray();
        $result = $this->delMachineOnOff($where);
        if ($result) {
            $this->sendToMachine($machine,'updateMachineOnOff');
        }
        return $this->rD($result);
    }

    /**
     * 导入营业配置Excel
     * @param $data
     * @return array|string
     */
    public function importCkc($data)
    {
        $this->startTrans();
        try {
            $path = root_path() . "public" . $data['file_path'];
            $title = ["machine_id", "off0", "on0", "off1", "on1", "off2", "on2", "off3", "on3", "off4", "on4", "off5", "on5", "off6", "on6"];
            $other = ['creator' => $this->manager['manager_id'] ?? 0, 'ao_id' => $this->manager['ao_id'] ?? 0];
            $moo = Excel::importExcel($path, $title, [], 4);
            actionLog($moo, '导入的营业数据');
            if ($moo) {
                $flag = [];
                foreach ($moo as $k => $v) {
                    if ($v['machine_id']) {
                        $m = $this->getMachineFind(['machine_id' => $v['machine_id']], 'm_id,machine_id,machine_name');
                        if ($m) {
                            $m = $m->toArray();
                            if ($m) {
                                $ckcArr = [];
                                $ckc = "";
                                for ($i = 0; $i <= 6; $i++) {
                                    if ($v['off' . $i] || $v['on' . $i]) {
                                        $ckcArr[$i] = $v["off" . $i] . "," . $v['on' . $i];
                                    }
                                }
                                if ($ckcArr) $ckc = json_encode($ckcArr);
                                $moo_id = $this->getMachineOnOffValue(['m_id' => $m['m_id']], 'moo_id');
                                if (!$moo_id) {
                                    $insert = array_merge($m, $other, ['on_off_ckc' => $ckc]);
                                    $flag[] = $this->addMachineOnOff($insert);
                                } else {
                                    $flag[] = $this->updateMachineOnOff(['moo_id' => $moo_id, 'on_off_ckc' => $ckc]);
                                }
                            }
                        }
                    }
                }
                $result = flag_check($flag);
                return $this->checkTrans($result);
            }
            $this->rollbackTrans();
            return $this->r(100, '获取不到Excel文档中的数据');
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e,1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 导入开关机配置Excel
     * @param $data
     * @return array|string
     */
    public function importOnOff($data)
    {
        $this->startTrans();
        try {
            $path = root_path() . "public" . $data['file_path'];
            $title = ["machine_id", "status", "off0", "on0", "off1", "on1", "off2", "on2", "off3", "on3", "off4", "on4", "off5", "on5", "off6", "on6"];
            $other = ['creator' => $this->manager['manager_id'] ?? 0, 'ao_id' => $this->manager['ao_id'] ?? 0];
            $moo = Excel::importExcel($path, $title, [], 4);
            actionLog($moo, '导入的营业数据');
            if ($moo) {
                $flag = [];
                foreach ($moo as $k => $v) {
                    $m = $this->getMachineFind(['machine_id' => $v['machine_id']], 'm_id,machine_id,machine_name');
                    $onOff = json_encode([
                        $v['off0'] . "," . $v['on0'],
                        $v['off1'] . "," . $v['on1'],
                        $v['off2'] . "," . $v['on2'],
                        $v['off3'] . "," . $v['on3'],
                        $v['off4'] . "," . $v['on4'],
                        $v['off5'] . "," . $v['on5'],
                        $v['off6'] . "," . $v['on6'],
                    ]);
                    $moo_id = $this->getMachineOnOffValue(['m_id' => $m['m_id']], 'moo_id');
                    if (!$moo_id) {
                        $insert = array_merge($m->toArray(), $other, ['on_off_machine' => $onOff]);
                        $flag[] = $this->addMachineOnOff($insert);
                    } else {
                        $flag[] = $this->updateMachineOnOff(['moo_id' => $moo_id, 'on_off_machine' => $onOff]);
                    }
                }
                $result = flag_check($flag);
                return $this->checkTrans($result);
            }
            $this->rollbackTrans();
            return $this->r(100, '获取不到Excel文档中的数据');
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e,1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 导出设备营业配置Excel
     * @param $where
     * @return array|\think\response\Json
     */
    public function exportOnOff($where)
    {
        $list = $this->getMachineOnOffList($where, 0, 'machine_id,machine_name,status,on_off_ckc,on_off_machine');
        if ($list) {
            $list = $list->toArray();
            foreach ($list as $key => $value) {
                $onOff = json2arr($value['on_off_machine']);
                for ($i = 0; $i <= 6; $i++) {
                    $onOffArr = explode(",", $onOff[$i] ?? "");
                    $value["off" . $i] = $onOffArr[0] ?? "";
                    $value["on" . $i] = $onOffArr[1] ?? "";
                }
                $ckc = json2arr($value['on_off_ckc']);
                for ($j = 0; $j <= 6; $j++) {
                    $ckcArr = explode(",", $ckc[$j] ?? "");
                    $value["ckcOff" . $j] = $ckcArr[0] ?? "";
                    $value["ckcOn" . $j] = $ckcArr[1] ?? "";
                }
                $value['status'] = ($value['status'] == 1 ? "on" : "off");
                $list[$key] = $value;
            }
            $title = [
                "machine_id" => "机器ID",
                "machine_name" => "机器名称",
                "status" => "开关机应用开关",
                "off0" => "关机时间",
                "on0" => "开机时间",
                "off1" => "关机时间",
                "on1" => "开机时间",
                "off2" => "关机时间",
                "on2" => "开机时间",
                "off3" => "关机时间",
                "on3" => "开机时间",
                "off4" => "关机时间",
                "on4" => "开机时间",
                "off5" => "关机时间",
                "on5" => "开机时间",
                "off6" => "关机时间",
                "on6" => "开机时间",
                "ckcOff0" => "停止营业时间",
                "ckcOn0" => "恢复营业时间",
                "ckcOff1" => "停止营业时间",
                "ckcOn1" => "恢复营业时间",
                "ckcOff2" => "停止营业时间",
                "ckcOn2" => "恢复营业时间",
                "ckcOff3" => "停止营业时间",
                "ckcOn3" => "恢复营业时间",
                "ckcOff4" => "停止营业时间",
                "ckcOn4" => "恢复营业时间",
                "ckcOff5" => "停止营业时间",
                "ckcOn5" => "恢复营业时间",
                "ckcOff6" => "停止营业时间",
                "ckcOn6" => "恢复营业时间",
            ];
            $merge = [
                ["merge" => "A1:A2", "cell" => "A1", "name" => "机器ID"],
                ["merge" => "B1:B2", "cell" => "B1", "name" => "机器名称"],
                ["merge" => "C1:C2", "cell" => "C1", "name" => "开关机应用开关"],
                ["merge" => "D1:E1", "cell" => "D1", "name" => "星期一"],
                ["merge" => "F1:G1", "cell" => "F1", "name" => "星期二"],
                ["merge" => "H1:I1", "cell" => "H1", "name" => "星期三"],
                ["merge" => "J1:K1", "cell" => "J1", "name" => "星期四"],
                ["merge" => "L1:M1", "cell" => "L1", "name" => "星期五"],
                ["merge" => "N1:O1", "cell" => "N1", "name" => "星期六"],
                ["merge" => "P1:Q1", "cell" => "P1", "name" => "星期日"],
                ["merge" => "R1:S1", "cell" => "R1", "name" => "星期一"],
                ["merge" => "T1:U1", "cell" => "T1", "name" => "星期二"],
                ["merge" => "V1:W1", "cell" => "V1", "name" => "星期三"],
                ["merge" => "X1:Y1", "cell" => "X1", "name" => "星期四"],
                ["merge" => "Z1:AA1", "cell" => "Z1", "name" => "星期五"],
                ["merge" => "AB1:AC1", "cell" => "AB1", "name" => "星期六"],
                ["merge" => "AD1:AE1", "cell" => "AD1", "name" => "星期日"],
                ["merge" => "AF1:AF2", "cell" => "AF1", "name" => "备注"],
            ];
            $filename = "导出机器营业配置-" . date("Ymd");
            return $this->sendToExport("设备管理-营业配置", $filename, $title, $list, ['startRow' => 2, 'merge' => $merge]);
//                return $this->rAction(Excel::exportExcel($list, $title, $filename, 0, 2, $merge));
        }
        return $this->rFail($this->lang("query_fail"));
    }

}