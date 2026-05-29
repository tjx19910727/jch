<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/4/29
 * Time: 19:10
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Machine\SimCardInfoModel;
use app\AppFactory\Kernel\Model\Machine\SimSignalLogModel;

trait SimSignalLogTrait
{
    public function getSimSignalLogListJoinMachine($where, $pageNum = null, $field = "a.*", $order = "a.id desc", $eachFun = "", $group = '', $limit = '')
    {
        $join = [[
            'join' => 'machine m',
            'on' => 'm.m_id = a.m_id',
            'type' => 'inner',
        ]];
        return SimSignalLogModel::getListAndWith($where, $pageNum, $field, $order, $eachFun, $group, $limit, [], $join);
    }


    public function getSimSignalLogFind($where, $field = "*", $order = "")
    {
        return SimSignalLogModel::getFind($where, $field, $order);
    }

    public function getSimSignalLogList($where, $pageNum = null, $field = "*", $order = "", $eachFun = "", $group = '', $limit = '')
    {
        return SimSignalLogModel::getList($where, $pageNum, $field, $order, $eachFun, $group, $limit);
    }

    public function addSimSignalLog($insert)
    {
        $data = SimSignalLogModel::create($insert);
        return $data->id;
    }

    public function updateSimSignalLog($update, $where = [], $field = [])
    {
        return SimSignalLogModel::update($update, $where, $field);
    }

    /**
     * 设备上报物联卡实时信号
     * sinr: <0 等级1, 1~10 等级2, 11~20 等级3, >20 等级4
     * rsrp: <-100 等级1, -100~-90 等级2, -90~-70 等级3, >-70 等级4
     * @return int
     */
    public function updateSimSignal()
    {
        $machine = $this->machine ?? [];
        $message = $this->message ?? [];
        return $this->updateSimSignalWithData($machine, $message);
    }

    public function updateSimSignalWithData($machine, $message)
    {
        try {
            if (empty($machine['m_id']) || empty($machine['machine_id'])) {
                actionLog(['machine' => $machine, 'message' => $message], 'updateSimSignal缺少设备信息', 'DataUpload');
                return 1;
            }

            $payload = $message;

            $iccid = trim($payload['iccid'] ?? '');
            if (!$iccid) {
                $iccid = SimCardInfoModel::getFieldValue(['machine_id' => $machine['machine_id']], 'iccid', 'id desc');
            }
            if (!$iccid) {
                actionLog(['machine' => $machine, 'message' => $payload], 'updateSimSignal缺少iccid', 'DataUpload');
                return 1;
            }
            if(!isset($payload['sinr']) || !isset($payload['rsrp']) || $payload['sinr'] === '' || $payload['rsrp'] === ''){
                actionLog(['machine' => $machine, 'message' => $payload], 'updateSimSignal缺少信号信息', 'DataUpload');
                return 1;
            }
            $sinr = $payload['sinr'];
            $rsrp = $payload['rsrp'];


            if ($sinr < 0) {
                $sinrLevel = 1;
            } elseif ($sinr >= 1 && $sinr <= 10) {
                $sinrLevel = 2;
            } elseif ($sinr >= 11 && $sinr <= 20) {
                $sinrLevel = 3;
            } elseif ($sinr > 20) {
                $sinrLevel = 4;
            } else {
                $sinrLevel = 1;
            }

            if ($rsrp < -100) {
                $rsrpLevel = 1;
            } elseif ($rsrp < -90) {
                $rsrpLevel = 2;
            } elseif ($rsrp <= -70) {
                $rsrpLevel = 3;
            } else {
                $rsrpLevel = 4;
            }

            $insert = [
                'm_id' => $machine['m_id'],
                'machine_id' => $machine['machine_id'],
                'iccid' => $iccid,
                'rsrp' => $rsrp,
                'rsrp_level' => $rsrpLevel,
                'sinr' => $sinr,
                'sinr_level' => $sinrLevel,
            ];
            $this->addSimSignalLog($insert);
            return 1;
        } catch (\Throwable $e) {
            actionException($e, 1);
            return 1;
        }
    }
}
