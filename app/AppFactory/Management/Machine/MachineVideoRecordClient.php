<?php

namespace app\AppFactory\Management\Machine;

use app\AppFactory\Kernel\Model\Machine\MachineModel;
use app\AppFactory\Kernel\Model\Machine\MachineVideoRecordLogModel;
use app\AppFactory\Management\ManagementClient;
use think\facade\Db;

class MachineVideoRecordClient extends ManagementClient
{
    /**
     * 分页查询设备视频录制记录，时间字段统一格式化为 Y-m-d H:i:s。
     * @param array $postData
     * @return array|\think\response\Json
     */
    public function getRecordList($postData)
    {
        try {
            $pageNum = intval($postData['pageNum'] ?? 20);
            if ($pageNum <= 0) $pageNum = 20;
            $pageNum = min($pageNum, 200);

            $query = Db::name('machine_video_record_log')->alias('mvrl')
                ->leftJoin('machine m', 'm.machine_id = mvrl.machine_id')
                ->leftJoin('auth_manager am', 'am.manager_id = mvrl.manager_id')
                ->leftJoin('auth_organization ao', 'ao.ao_id = m.ao_id');

            $permitted = $this->app->machine->resolvePermittedMachineIds();
            if ($permitted !== null) {
                if (!$permitted) {
                    $query->whereRaw('1 = 0');
                } else {
                    $query->whereIn('m.m_id', array_map('intval', $permitted));
                }
            }

            if (isset($postData['log_id']) && $postData['log_id'] !== '') {
                $query->where('mvrl.id', '=', intval($postData['log_id']));
            }
            if (isset($postData['manager_id']) && $postData['manager_id'] !== '') {
                $query->where('mvrl.manager_id', '=', intval($postData['manager_id']));
            }
            foreach (['machine_id' => 'mvrl.machine_id', 'machine_name' => 'm.machine_name'] as $param => $field) {
                if (isset($postData[$param]) && trim((string)$postData[$param]) !== '') {
                    $query->where($field, 'like', '%' . trim((string)$postData[$param]) . '%');
                }
            }
            if (isset($postData['status']) && trim((string)$postData['status']) !== '') {
                $statuses = array_values(array_filter(array_map('intval', explode(',', (string)$postData['status'])), function ($status) {
                    return in_array($status, [1, 2, 3, 4], true);
                }));
                if ($statuses) $query->whereIn('mvrl.status', $statuses);
            }
            if (isset($postData['has_video']) && $postData['has_video'] !== '') {
                $hasVideo = intval($postData['has_video']);
                if ($hasVideo === 1) {
                    $query->whereNotNull('mvrl.video_path')->where('mvrl.video_path', '<>', '');
                } elseif ($hasVideo === 2) {
                    $query->where(function ($subQuery) {
                        $subQuery->whereNull('mvrl.video_path')->whereOr('mvrl.video_path', '=', '');
                    });
                }
            }
            if (!empty($postData['create_time']) && strpos((string)$postData['create_time'], '~') !== false) {
                $parts = array_map('trim', explode('~', (string)$postData['create_time'], 2));
                if (count($parts) === 2 && $parts[0] !== '' && $parts[1] !== '') {
                    $startTime = strtotime($parts[0]);
                    $endTime = strtotime($parts[1]);
                    if ($startTime !== false && $endTime !== false) {
                        $query->whereBetween('mvrl.create_time', [$startTime, $endTime]);
                    }
                }
            }

            $list = $query
                ->field('mvrl.id log_id,m.m_id,mvrl.machine_id,m.machine_name,m.ao_id,ao.organization_name,
                    mvrl.record_seconds,mvrl.status,mvrl.video_path,mvrl.fail_reason,mvrl.manager_id,
                    am.account manager_account,am.nickname manager_nickname,mvrl.sent_at,mvrl.lock_until,
                    mvrl.started_at,mvrl.finished_at,mvrl.create_time,mvrl.update_time')
                ->order('mvrl.id desc')
                ->paginate($pageNum, false, ['query' => request()->param()]);

            $statusMap = [1 => '已下发录制', 2 => '录制中', 3 => '录制成功', 4 => '录制失败'];
            $timeFields = ['sent_at', 'lock_until', 'started_at', 'finished_at', 'create_time', 'update_time'];
            $list = $list->each(function ($item) use ($statusMap, $timeFields) {
                $status = intval($item['status'] ?? 0);
                $item['status_name'] = $statusMap[$status] ?? ('未知状态#' . $status);
                $item['manager_name'] = $item['manager_nickname'] ?: $item['manager_account'];
                $item['video_path'] = !empty($item['video_path']) ? checkStrDomain($item['video_path']) : '';
                $item['has_video'] = $item['video_path'] !== '';
                $item['can_get_video'] = $status === 3 && $item['video_path'] === '';
                foreach ($timeFields as $field) {
                    $timestamp = intval($item[$field] ?? 0);
                    $item[$field] = $timestamp > 0 ? date('Y-m-d H:i:s', $timestamp) : '';
                }
                unset($item['manager_account'], $item['manager_nickname']);
                return $item;
            });

            return returnState(200, lang('query_success'), $list);
        } catch (\Exception $e) {
            actionException($e, 1, 'machineVideoRecordList');
            return returnTryCatch($e->getMessage());
        }
    }

    /**
     * 下发设备视频录制指令。
     * @param array $postData
     * @return array|\think\response\Json
     */
    public function recordVideo($postData)
    {
        $machineId = trim((string)$postData['machine_id']);
        $recordSeconds = intval($postData['record_seconds']);
        $transactionStarted = false;

        try {
            Db::startTrans();
            $transactionStarted = true;

            $machine = MachineModel::where('machine_id', $machineId)
                ->field('m_id,machine_id')
                ->lock(true)
                ->find();
            if (!$machine) {
                Db::rollback();
                $transactionStarted = false;
                return returnValidate(lang('VMachine.machine_no_data'));
            }

            $mId = intval($machine['m_id']);
            $permitted = $this->app->machine->resolvePermittedMachineIds();
            if ($permitted !== null && !in_array($mId, array_map('intval', $permitted), true)) {
                Db::rollback();
                $transactionStarted = false;
                return returnState(100, '无权操作该设备');
            }

            $now = time();
            $lockedLog = MachineVideoRecordLogModel::where('machine_id', $machineId)
                ->where('lock_until', '>', $now)
                ->field('id,record_seconds,lock_until,status')
                ->order('id', 'desc')
                ->find();
            if ($lockedLog) {
                $remainingSeconds = max(1, intval($lockedLog['lock_until']) - $now);
                Db::rollback();
                $transactionStarted = false;
                return returnState(300, '设备正在录制视频，请稍后再试', [
                    'log_id' => intval($lockedLog['id']),
                    'record_seconds' => intval($lockedLog['record_seconds']),
                    'remaining_seconds' => $remainingSeconds,
                ]);
            }

            $log = MachineVideoRecordLogModel::create([
                'machine_id' => $machineId,
                'record_seconds' => $recordSeconds,
                'status' => 1,
                'manager_id' => intval($this->manager['manager_id'] ?? 0),
                'sent_at' => 0,
                'lock_until' => 0,
                'started_at' => 0,
                'finished_at' => 0,
                'create_time' => $now,
                'update_time' => $now,
            ]);
            $logId = intval($log->getAttr('id'));
            if (!$logId) {
                throw new \RuntimeException('创建视频录制日志失败');
            }

            $result = $this->app->machine->sendToMachine(
                ['machine_id' => $machineId],
                'recordVideo',
                [
                    'log_id' => $logId,
                    'record_seconds' => $recordSeconds,
                ]
            );

            $resultData = $this->normalizeSendResult($result);
            if (intval($resultData['state'] ?? 0) !== 200) {
                $failReason = $this->resolveSendFailReason($result, $resultData, lang('VMachine.machine_no_data'));
                MachineVideoRecordLogModel::where('id', $logId)->update([
                    'status' => 4,
                    'fail_reason' => mb_substr($failReason, 0, 500),
                    'finished_at' => time(),
                    'update_time' => time(),
                ]);
                Db::commit();
                $transactionStarted = false;
                return returnState(100, $failReason, [
                    'log_id' => $logId,
                    'status' => 4,
                ]);
            }

            $sentAt = time();
            $lockUntil = $sentAt + $recordSeconds;
            MachineVideoRecordLogModel::where('id', $logId)->update([
                'sent_at' => $sentAt,
                'lock_until' => $lockUntil,
                'update_time' => $sentAt,
            ]);
            Db::commit();
            $transactionStarted = false;

            actionLog([
                'machine_id' => $machineId,
                'log_id' => $logId,
                'record_seconds' => $recordSeconds,
                'lock_until' => $lockUntil,
            ], '下发设备视频录制指令', 'recordVideo');

            return returnState(200, '录制指令已下发', [
                'log_id' => $logId,
                'machine_id' => $machineId,
                'record_seconds' => $recordSeconds,
                'status' => 1,
                'sent_at' => $sentAt,
                'lock_until' => $lockUntil,
            ]);
        } catch (\Exception $e) {
            if ($transactionStarted) Db::rollback();
            actionException($e, 1, 'recordVideo');
            return returnTryCatch($e->getMessage());
        }
    }

    /**
     * 录制成功后通知设备上传对应视频。
     * @param array $postData
     * @return array|\think\response\Json
     */
    public function getVideo($postData)
    {
        $logId = intval($postData['log_id']);
        $transactionStarted = false;

        try {
            Db::startTrans();
            $transactionStarted = true;
            $log = MachineVideoRecordLogModel::where('id', $logId)->lock(true)->find();
            if (!$log) {
                Db::rollback();
                $transactionStarted = false;
                return returnState(300, '录制日志不存在');
            }

            $machineId = trim((string)$log['machine_id']);
            $machine = MachineModel::where('machine_id', $machineId)
                ->field('m_id,machine_id')
                ->lock(true)
                ->find();
            if (!$machine) {
                Db::rollback();
                $transactionStarted = false;
                return returnState(300, '设备不存在');
            }

            $permitted = $this->app->machine->resolvePermittedMachineIds();
            if ($permitted !== null && !in_array(intval($machine['m_id']), array_map('intval', $permitted), true)) {
                Db::rollback();
                $transactionStarted = false;
                return returnState(100, '无权操作该设备');
            }
            if (intval($log['status']) !== 3) {
                Db::rollback();
                $transactionStarted = false;
                return returnState(300, '设备录制尚未成功，不能获取视频');
            }
            if (trim((string)($log['video_path'] ?? '')) !== '') {
                $videoPath = checkStrDomain($log['video_path']);
                Db::rollback();
                $transactionStarted = false;
                return returnState(300, '视频已经上传，无需重复获取', [
                    'log_id' => $logId,
                    'video_path' => $videoPath,
                ]);
            }

            $folder = 'machine_video_record';
            $result = $this->app->machine->sendToMachine(
                ['machine_id' => $machineId],
                'getRecordVideo',
                [
                    'log_id' => $logId,
                    'record_seconds' => intval($log['record_seconds']),
                    'folder' => $folder,
                ]
            );
            $resultData = $this->normalizeSendResult($result);
            if (intval($resultData['state'] ?? 0) !== 200) {
                $failReason = $this->resolveSendFailReason($result, $resultData, '获取视频指令下发失败');
                Db::rollback();
                $transactionStarted = false;
                return returnState(100, $failReason, ['log_id' => $logId]);
            }

            Db::commit();
            $transactionStarted = false;
            actionLog([
                'log_id' => $logId,
                'machine_id' => $machineId,
                'folder' => $folder,
            ], '下发获取设备录制视频指令', 'getRecordVideo');

            return returnState(200, '获取视频指令已下发', [
                'log_id' => $logId,
                'machine_id' => $machineId,
                'folder' => $folder,
            ]);
        } catch (\Exception $e) {
            if ($transactionStarted) Db::rollback();
            actionException($e, 1, 'getRecordVideo');
            return returnTryCatch($e->getMessage());
        }
    }

    private function normalizeSendResult($result)
    {
        if (is_array($result)) return $result;
        if (is_object($result) && method_exists($result, 'getData')) return $result->getData();
        return [];
    }

    private function resolveSendFailReason($result, $resultData, $default)
    {
        if (is_array($resultData) && !empty($resultData['msg'])) {
            return trim((string)$resultData['msg']);
        }
        if (is_string($result) && trim($result) !== '') {
            return trim($result);
        }
        return $default;
    }
}
