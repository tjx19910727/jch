<?php

namespace app\AppFactory\Machine\Laser;

use app\AppFactory\Kernel\BaseClient;
use app\AppFactory\Kernel\Model\Machine\MachineModel;
use app\AppFactory\Kernel\Model\Machine\MachineMqRecordModel;
use app\AppFactory\Kernel\Model\Machine\MachineVideoRecordLogModel;
use app\AppFactory\Kernel\Util\SignUtil;
use app\machine\validate\VLaser;
use think\facade\Db;

class LaserClient extends BaseClient
{
    /**
     * Laser 请求公共认证。
     * uploadImage 为 H5 接口，仅验签；其他接口按设备请求校验 MAC、消息ID和时间戳。
     * 测试环境跳过认证校验，但各业务方法仍执行自身业务参数校验。
     * @param bool $isH5
     * @return bool|array|\think\response\Json
     */
    public function checkRequest($isH5 = false)
    {
        //if (env('CglPay.is_test')) return true;

        $data = $this->getRequestData();
        try {
            validate(VLaser::class)
                ->scene($isH5 ? 'h5Request' : 'deviceRequest')
                ->check($data);
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }

        $machineId = trim((string)($data['machine_id'] ?? ''));
        $machine = null;
        if ($machineId !== '') {
            $machine = MachineModel::where('machine_id', $machineId)
                ->field('m_id,machine_id,machine_name,mac_address,signKey')
                ->find();
        }

        if (!$isH5) {
            if (!$machine) return returnState(100, '设备不存在');

            $mac = trim((string)($this->config['mac'] ?? ''));
            if ($mac === '') return returnState(300, 'MAC地址不能为空');
            if ($mac !== trim((string)$machine['mac_address'])) {
                actionLog([
                    'machine_id' => $machineId,
                    'mac' => $mac,
                    'mac_address' => $machine['mac_address'],
                ], 'Laser设备请求MAC地址匹配失败', 'mac_check');
                return returnState(300, 'MAC地址匹配失败');
            }

            $timestamp = intval($data['timestamp']);
            $tolerance = intval(config('rabbit_mq.machine_receive_timestamp_tolerance') ?: 180);
            if ($tolerance < 120) $tolerance = 120;
            if ($timestamp <= 0 || abs(time() - $timestamp) > $tolerance) {
                return returnState(300, lang('VReceive.timestamp_checkTimestamp_overdue'), [
                    'server_time' => time(),
                    'request_timestamp' => $timestamp,
                    'server_time_offset' => $timestamp ? time() - $timestamp : 0,
                    'timestamp_tolerance' => $tolerance,
                ]);
            }
        }

        $signKey = $machine ? trim((string)$machine['signKey']) : '';
        if ($signKey === '') $signKey = env('api.md5Key');
        $signValid = SignUtil::checkSign($data, $signKey);
        if (!$signValid && strtolower((string)request()->action()) === 'uploadbehaviortracking') {
            // Flutter jsonEncode 保留中文和斜杠，仅此嵌套数据接口兼容该签名格式。
            $signValid = SignUtil::checkSign(
                $data,
                $signKey,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        }
        if (!$signValid) {
            return returnState(100, lang('VLaser.check_sign_fail'));
        }

        if (!$isH5) {
            $msgId = trim((string)$data['msg_id']);
            if (MachineMqRecordModel::where('msg_id', $msgId)->find()) {
                return returnState(300, lang('VReceive.msg_id_unique'));
            }

            $record = MachineMqRecordModel::create([
                'm_id' => intval($machine['m_id']),
                'machine_id' => $machineId,
                'machine_name' => $machine['machine_name'] ?? '',
                'msg_id' => $msgId,
                'path' => request()->controller() . '/' . request()->action(),
                'content' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'from' => 1,
                'type' => 1,
            ]);
            if (!$record || !intval($record->getAttr('mr_id'))) {
                return returnState(100, '设备请求消息记录失败');
            }
        }

        return true;
    }

    /**
     * 设备通过 HTTP 上报视频录制状态。
     * status：2录制中、3录制成功、4录制失败。
     * @return array|\think\response\Json
     */
    public function reportStatus()
    {
        $transactionStarted = false;
        try {
            $signData = $this->getRequestData();
            $machineId = trim((string)($this->config['machine_id'] ?? ''));
            $logId = intval($signData['log_id'] ?? 0);
            $status = intval($signData['status'] ?? 0);
            $videoPath = trim((string)($signData['video_path'] ?? ''));
            $failReason = trim((string)($signData['fail_reason'] ?? ''));

            if ($machineId === '') return returnState(100, '缺少设备编号');
            if ($logId <= 0) return returnState(100, '缺少录制日志ID');
            if (!in_array($status, [2, 3, 4], true)) {
                return returnState(100, '录制状态仅支持2、3、4');
            }
            if (mb_strlen($videoPath) > 1000) {
                return returnState(100, '视频地址长度不能超过1000个字符');
            }
            if (mb_strlen($failReason) > 500) {
                return returnState(100, '失败原因长度不能超过500个字符');
            }

            Db::startTrans();
            $transactionStarted = true;
            $log = MachineVideoRecordLogModel::where('id', $logId)
                ->lock(true)
                ->find();
            if (!$log || (string)$log['machine_id'] !== $machineId) {
                Db::rollback();
                $transactionStarted = false;
                actionLog($signData, '视频录制状态上报未匹配到设备日志', 'reportVideoRecordStatus');
                return returnState(100, '录制日志不存在或与当前设备不匹配');
            }

            $currentStatus = intval($log['status']);
            // 录制成功后，设备会在收到 getRecordVideo 后上传文件，
            // 并再次以 status=3 + video_path 上报。此时只补写空的视频地址，不改变终态。
            if ($currentStatus === 3 && $status === 3 && $videoPath !== '') {
                $storedVideoPath = trim((string)($log['video_path'] ?? ''));
                if ($storedVideoPath === '') {
                    $now = time();
                    $result = MachineVideoRecordLogModel::where('id', $logId)->update([
                        'video_path' => $videoPath,
                        'update_time' => $now,
                    ]);
                    if ($result === false) {
                        throw new \RuntimeException('写入录制视频地址失败');
                    }
                    $storedVideoPath = $videoPath;
                }
                Db::commit();
                $transactionStarted = false;
                actionLog([
                    'machine_id' => $machineId,
                    'log_id' => $logId,
                    'video_path' => $storedVideoPath,
                ], '设备HTTP上报录制视频地址', 'reportVideoRecordStatus');
                return returnState(200, '录制视频地址上报成功', [
                    'log_id' => $logId,
                    'status' => 3,
                    'video_path' => checkStrDomain($storedVideoPath),
                ]);
            }

            // 成功、失败是终态；设备重试上报时返回成功，但不覆盖终态。
            if (in_array($currentStatus, [3, 4], true)) {
                Db::commit();
                $transactionStarted = false;
                return returnState(200, '录制状态已上报', [
                    'log_id' => $logId,
                    'status' => $currentStatus,
                ]);
            }

            // 重复上报“录制中”按幂等成功处理。
            if ($currentStatus === 2 && $status === 2) {
                Db::commit();
                $transactionStarted = false;
                return returnState(200, '录制状态已上报', [
                    'log_id' => $logId,
                    'status' => 2,
                ]);
            }

            $now = time();
            $update = [
                'status' => $status,
                'update_time' => $now,
            ];
            if ($status === 2 && intval($log['started_at']) <= 0) {
                $update['started_at'] = $now;
            }
            if (in_array($status, [3, 4], true)) {
                $update['finished_at'] = $now;
            }
            if ($status === 3 && $videoPath !== '') {
                $update['video_path'] = $videoPath;
            }
            if ($status === 4 && $failReason !== '') {
                $update['fail_reason'] = $failReason;
            }

            $result = MachineVideoRecordLogModel::where('id', $logId)->update($update);
            if ($result === false) {
                throw new \RuntimeException('更新视频录制状态失败');
            }
            Db::commit();
            $transactionStarted = false;

            actionLog([
                'machine_id' => $machineId,
                'log_id' => $logId,
                'previous_status' => $currentStatus,
                'status' => $status,
            ], '设备HTTP上报视频录制状态', 'reportVideoRecordStatus');

            return returnState(200, '录制状态上报成功', [
                'log_id' => $logId,
                'status' => $status,
            ]);
        } catch (\Exception $e) {
            if ($transactionStarted) Db::rollback();
            actionException($e, 1, 'reportVideoRecordStatus');
            return returnTryCatch($e->getMessage());
        }
    }

    private function getRequestData()
    {
        $data = $this->config['data'] ?? [];
        return is_array($data) ? $data : json2arr($data);
    }
}
