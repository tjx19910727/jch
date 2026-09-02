<?php

namespace app\AppFactory\Kernel\Service\FaultNotice;

use app\AppFactory\AppFactory;
use app\AppFactory\Kernel\Model\Machine\MachineShutdownCommandModel;
use think\facade\Db;

/**
 * 微信故障详情页设备关机服务。
 *
 * 旧Official::shutdownNotice入口保持不变；新详情页使用独立服务并沿用相同业务条件。
 */
class FaultShutdownService
{
    const ERROR_CODE = '12202011';
    const ACTION_VALID_SECONDS = 86400;

    public function getState($log, $event, $now = null)
    {
        $now = $now === null ? time() : intval($now);
        if (strval($event['error_code'] ?? '') !== self::ERROR_CODE
            || strval($log['error_code'] ?? '') !== self::ERROR_CODE) {
            return $this->state(false, 'invalid_fault', '该故障不是设备未关机提醒');
        }
        if (intval($log['send_status'] ?? 0) !== 1) {
            return $this->state(false, 'notice_failed', '该通知尚未发送成功，无法执行关机操作');
        }
        if (intval($log['manager_id'] ?? 0) <= 0 || trim(strval($log['openid'] ?? '')) === '') {
            return $this->state(false, 'receiver_invalid', '通知接收人信息不完整，无法执行关机操作');
        }

        $logCreateTime = intval($log['create_time'] ?? 0);
        if ($logCreateTime <= 0 || $now > $logCreateTime + self::ACTION_VALID_SECONDS) {
            return $this->state(false, 'expired', '关机操作有效期为通知发送后24小时，当前已失效');
        }

        $machine = (array)Db::name('machine')
            ->where('m_id', intval($log['m_id'] ?? 0))
            ->where('machine_id', strval($event['machine_id'] ?? ''))
            ->field('m_id,machine_id,machine_name,online,http_online,is_operating,mac_address,signKey')
            ->find();
        if (!$machine) {
            return $this->state(false, 'machine_missing', '设备不存在，无法发送关机指令');
        }
        if (intval($machine['is_operating'] ?? 0) !== 1) {
            return $this->state(false, 'not_operating', '设备已不处于运营状态，无法发送关机指令');
        }
        // 关机指令通过设备MQ通道下发，与旧流程一致，以machine.online为准。
        if (intval($machine['online'] ?? 0) !== 1) {
            return $this->state(false, 'offline', '设备当前不在线，无需或无法发送关机指令');
        }

        $onOff = (array)Db::name('machine_on_off')
            ->where('m_id', intval($machine['m_id']))
            ->where('status', 1)
            ->whereNotNull('on_off_machine')
            ->where('on_off_machine', '<>', '')
            ->where('on_off_machine', '<>', '{}')
            ->order('moo_id desc')
            ->find();
        if (!$onOff) {
            return $this->state(false, 'schedule_missing', '设备没有有效的定时开关机配置');
        }

        $shutdownTimestamp = $this->getOverdueShutdownTimestamp($onOff['on_off_machine'], $now);
        if (!$shutdownTimestamp) {
            return $this->state(false, 'not_overdue', '设备当前不符合超过计划关机时间30分钟仍在线的条件');
        }

        $incidentKey = date('Ymd', $shutdownTimestamp);
        $commandWhere = [
            'openid' => strval($log['openid']),
            'm_id' => intval($machine['m_id']),
            'incident_key' => $incidentKey,
        ];
        $command = MachineShutdownCommandModel::where($commandWhere)
            ->order('msc_id desc')
            ->find();
        if ($command && intval($command['status'] ?? 0) === 2) {
            return $this->state(false, 'already_success', '本次关机指令已成功下发，请勿重复操作');
        }
        if ($command && intval($command['status'] ?? 0) === 1) {
            return $this->state(false, 'processing', '关机指令正在处理中，请勿重复提交');
        }

        return $this->state(true, 'available', '设备当前仍在线，可以发送关机指令', [
            'machine' => $machine,
            'shutdown_timestamp' => $shutdownTimestamp,
            'shutdown_time' => date('Y-m-d H:i:s', $shutdownTimestamp),
            'incident_key' => $incidentKey,
            'command_where' => $commandWhere,
        ]);
    }

    public function shutdown($log, $event)
    {
        $now = time();
        $state = $this->getState($log, $event, $now);
        if (!$state['enabled']) {
            return ['success' => false, 'status' => $state['status'], 'message' => $state['message']];
        }

        $machine = $state['machine'];
        $shutdownTimestamp = intval($state['shutdown_timestamp']);
        $incidentKey = strval($state['incident_key']);
        $commandWhere = $state['command_where'];

        try {
            $reservation = Db::transaction(function () use (
                $commandWhere,
                $log,
                $machine,
                $shutdownTimestamp,
                $incidentKey,
                $now
            ) {
                $command = MachineShutdownCommandModel::where($commandWhere)->lock(true)->find();
                if ($command) {
                    if (intval($command['status']) === 2) {
                        return ['success' => false, 'message' => '本次关机指令已成功下发，请勿重复操作'];
                    }
                    if (intval($command['status']) === 1) {
                        return ['success' => false, 'message' => '关机指令正在处理中，请勿重复提交'];
                    }
                    $command->save([
                        'wtl_id' => intval($log['wtl_id']),
                        'me_id' => intval($log['me_id'] ?? 0),
                        'status' => 1,
                        'result' => '',
                        'command_time' => 0,
                        'update_time' => $now,
                    ]);
                    return ['success' => true, 'msc_id' => intval($command['msc_id'])];
                }

                $command = MachineShutdownCommandModel::create([
                    'wtl_id' => intval($log['wtl_id']),
                    'me_id' => intval($log['me_id'] ?? 0),
                    'manager_id' => intval($log['manager_id']),
                    'openid' => strval($log['openid']),
                    'm_id' => intval($machine['m_id']),
                    'machine_id' => strval($machine['machine_id']),
                    'incident_key' => $incidentKey,
                    'shutdown_time' => $shutdownTimestamp,
                    'status' => 1,
                    'result' => '',
                    'command_time' => 0,
                    'create_time' => $now,
                    'update_time' => $now,
                ]);
                return ['success' => true, 'msc_id' => intval($command['msc_id'])];
            });
        } catch (\Throwable $e) {
            actionException($e, 1, 'faultShutdown');
            return ['success' => false, 'status' => 'processing', 'message' => '关机指令正在处理或已提交，请勿重复操作'];
        }

        if (!$reservation['success']) {
            return ['success' => false, 'status' => 'processing', 'message' => $reservation['message']];
        }

        $mscId = intval($reservation['msc_id']);
        try {
            $key = trim(strval($machine['signKey'] ?? '')) ?: trim(strval(env('api.md5Key', '')));
            if ($key === '') {
                throw new \RuntimeException('设备签名密钥不存在，无法发送关机指令');
            }
            $app = AppFactory::machine([
                'machine_id' => strval($machine['machine_id']),
                'key' => $key,
                'mac' => strval($machine['mac_address'] ?? ''),
            ]);
            $result = $app->sendMq->sendMq('shutdown');
            $resultData = obj2arr($result);
            $success = is_array($resultData) && intval($resultData['state'] ?? 0) === 200;
            MachineShutdownCommandModel::where('msc_id', $mscId)->update([
                'status' => $success ? 2 : 3,
                'result' => json_encode($resultData, JSON_UNESCAPED_UNICODE),
                'command_time' => $success ? time() : 0,
                'update_time' => time(),
            ]);
            if (!$success) {
                return [
                    'success' => false,
                    'status' => 'failed',
                    'message' => '关机指令下发失败：' . strval($resultData['msg'] ?? '未知错误'),
                ];
            }

            Db::name('wx_template_log')->where('wtl_id', intval($log['wtl_id']))->update([
                'confirm_status' => 1,
                'confirm_time' => time(),
            ]);
            actionLog([
                'msc_id' => $mscId,
                'wtl_id' => intval($log['wtl_id']),
                'manager_id' => intval($log['manager_id']),
                'm_id' => intval($machine['m_id']),
                'machine_id' => strval($machine['machine_id']),
                'shutdown_time' => $shutdownTimestamp,
            ], '微信故障详情页下发设备关机指令', 'faultShutdown');
            return ['success' => true, 'status' => 'success', 'message' => '关机指令已成功下发，请勿重复操作'];
        } catch (\Throwable $e) {
            MachineShutdownCommandModel::where('msc_id', $mscId)->update([
                'status' => 3,
                'result' => $e->getMessage(),
                'update_time' => time(),
            ]);
            actionException($e, 1, 'faultShutdown');
            return ['success' => false, 'status' => 'failed', 'message' => '关机指令下发失败：' . $e->getMessage()];
        }
    }

    protected function getOverdueShutdownTimestamp($onOffMachine, $now)
    {
        if (is_string($onOffMachine)) {
            $onOffMachine = json_decode($onOffMachine, true);
        }
        if (!is_array($onOffMachine)) {
            return 0;
        }

        $today = strtotime(date('Y-m-d', $now));
        $yesterday = strtotime('-1 day', $today);
        $todayWeekKey = strval(intval(date('N', $today)) - 1);
        $yesterdayWeekKey = strval(intval(date('N', $yesterday)) - 1);
        $nowSec = $now - $today;
        $lateShutdownSec = 23 * 3600 + 30 * 60;
        $parseOnOff = function ($weekKey) use ($onOffMachine) {
            if (!array_key_exists($weekKey, $onOffMachine)) {
                return null;
            }
            $onOffTime = explode(',', strval($onOffMachine[$weekKey]));
            $shutdownTime = trim($onOffTime[0] ?? '');
            $startupTime = trim($onOffTime[1] ?? '');
            if (
                $shutdownTime === '' || $startupTime === ''
                || strtolower($shutdownTime) === 'null' || strtolower($startupTime) === 'null'
                || strtotime($shutdownTime) === false || strtotime($startupTime) === false
            ) {
                return null;
            }
            $shutdownSec = HourMinuteSec2int($shutdownTime);
            $startupSec = HourMinuteSec2int($startupTime);
            return [
                'shutdown_sec' => $shutdownSec,
                'startup_sec' => $startupSec,
                'is_cross_day' => $shutdownSec <= $startupSec,
            ];
        };

        $todayOnOff = $parseOnOff($todayWeekKey);
        $yesterdayOnOff = $parseOnOff($yesterdayWeekKey);
        if ($yesterdayOnOff && $yesterdayOnOff['is_cross_day'] && $nowSec <= $yesterdayOnOff['shutdown_sec']) {
            return 0;
        }
        if ($todayOnOff && $nowSec >= $todayOnOff['startup_sec']) {
            if ($todayOnOff['is_cross_day'] || $nowSec <= $todayOnOff['shutdown_sec']) {
                return 0;
            }
        }

        $shutdownTimestamp = 0;
        $beforeTodayStartup = !$todayOnOff || $nowSec < $todayOnOff['startup_sec'];
        if ($yesterdayOnOff && $beforeTodayStartup) {
            if ($yesterdayOnOff['is_cross_day']) {
                $shutdownTimestamp = $today + $yesterdayOnOff['shutdown_sec'];
            } elseif ($yesterdayOnOff['shutdown_sec'] >= $lateShutdownSec) {
                $shutdownTimestamp = $yesterday + $yesterdayOnOff['shutdown_sec'];
            }
        }
        if (
            $todayOnOff && !$todayOnOff['is_cross_day']
            && $todayOnOff['shutdown_sec'] < $lateShutdownSec
            && $nowSec > $todayOnOff['shutdown_sec']
        ) {
            $shutdownTimestamp = $today + $todayOnOff['shutdown_sec'];
        }
        return $shutdownTimestamp && $now > $shutdownTimestamp + 1800 ? $shutdownTimestamp : 0;
    }

    protected function state($enabled, $status, $message, $extra = [])
    {
        return array_merge([
            'enabled' => (bool)$enabled,
            'status' => strval($status),
            'message' => strval($message),
            'shutdown_time' => '',
        ], is_array($extra) ? $extra : []);
    }
}
