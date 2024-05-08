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
    use MachineOnOffTrait,MachineTrait;


    /**
     * 导入营业配置Excel
     * @param $data
     * @return array|string
     */
    public function importCkc($data)
    {
        try {
            $path = root_path() . "public" . $data['file_path'];
            $title = ["machine_id", "off0", "on0","off1", "on1", "off2", "on2", "off3", "on3", "off4", "on4", "off5", "on5", "off6", "on6"];
            $other = ['creator' => $this->manager['manager_id'] ?? 0, 'ao_id' => $this->manager['ao_id'] ?? 0];
            $moo = Excel::importExcel($path, $title, [],4);
            actionLog($moo,'导入的营业数据');
            if ($moo) {
                $flag = [];
                $this->startTrans();
                foreach ($moo  as $k => $v) {
                    $m = $this->getMachineFind(['machine_id' => $v['machine_id']],'m_id,machine_id,machine_name');
                    $ckc = json_encode([
                        $v['off0'] . "," . $v['on0'],
                        $v['off1'] . "," . $v['on1'],
                        $v['off2'] . "," . $v['on2'],
                        $v['off3'] . "," . $v['on3'],
                        $v['off4'] . "," . $v['on4'],
                        $v['off5'] . "," . $v['on5'],
                        $v['off6'] . "," . $v['on6'],
                    ]);
                    $moo_id = $this->getMachineOnOffValue(['m_id' => $m['m_id']],'moo_id');
                    if (!$moo_id) {
                        $insert = array_merge($m->toArray(),$other,['on_off_ckc' => $ckc]);
                        $flag[] = $this->addMachineOnOff($insert);
                    } else {
                        $flag[] = $this->updateMachineOnOff(['moo_id' => $moo_id,'on_off_ckc' => $ckc]);
                    }
                }
                $result = flag_check($flag);
                return $this->checkTrans($result);
            }
            return $this->r(100,'获取不到Excel文档中的数据');
        } catch (\Exception $e) {
            actionException($e,1);
            return $this->rValidate($e->getMessage());
        }
    }

    /**
     * 导入开关机配置Excel
     * @param $data
     * @return array|string
     */
    public function importOnOff($data)
    {
        try {
            $path = root_path() . "public" . $data['file_path'];
            $title = ["machine_id","status", "off0", "on0","off1", "on1", "off2", "on2", "off3", "on3", "off4", "on4", "off5", "on5", "off6", "on6"];
            $other = ['creator' => $this->manager['manager_id'] ?? 0, 'ao_id' => $this->manager['ao_id'] ?? 0];
            $moo = Excel::importExcel($path, $title, [],4);
            actionLog($moo,'导入的营业数据');
            if ($moo) {
                $flag = [];
                $this->startTrans();
                foreach ($moo  as $k => $v) {
                    $m = $this->getMachineFind(['machine_id' => $v['machine_id']],'m_id,machine_id,machine_name');
                    $onOff = json_encode([
                        $v['off0'] . "," . $v['on0'],
                        $v['off1'] . "," . $v['on1'],
                        $v['off2'] . "," . $v['on2'],
                        $v['off3'] . "," . $v['on3'],
                        $v['off4'] . "," . $v['on4'],
                        $v['off5'] . "," . $v['on5'],
                        $v['off6'] . "," . $v['on6'],
                    ]);
                    $moo_id = $this->getMachineOnOffValue(['m_id' => $m['m_id']],'moo_id');
                    if (!$moo_id) {
                        $insert = array_merge($m->toArray(),$other,['on_off_machine' => $onOff]);
                        $flag[] = $this->addMachineOnOff($insert);
                    } else {
                        $flag[] = $this->updateMachineOnOff(['moo_id' => $moo_id,'on_off_machine' => $onOff]);
                    }
                }
                $result = flag_check($flag);
                return $this->checkTrans($result);
            }
            return $this->r(100,'获取不到Excel文档中的数据');
        } catch (\Exception $e) {
            actionException($e,1);
            return $this->rValidate($e->getMessage());
        }
    }

}