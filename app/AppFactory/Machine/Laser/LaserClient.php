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
        if (env('CglPay.is_test')) return true;

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
        $unicodeJsonSignActions = ['uploadbehaviortracking', 'uploadtallyrecords'];
        if (!$signValid && in_array(strtolower((string)request()->action()), $unicodeJsonSignActions, true)) {
            // Flutter jsonEncode 保留中文和斜杠，这类嵌套数据接口兼容该签名格式。
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

    /**
     * 设备一次性上传完整理货批次。
     * @return array|\think\response\Json
     */
    public function uploadTallyRecords()
    {
        $transactionStarted = false;
        try {
            $requestData = $this->getRequestData();
            $machineId = trim((string)($this->config['machine_id'] ?? ($requestData['machine_id'] ?? '')));
            $machine = MachineModel::where('machine_id', $machineId)
                ->field('m_id,machine_id')
                ->find();
            if (!$machine) return $this->tallyResponse(100, '设备不存在');

            $batchData = $this->normalizeTallyBatch($this->extractTallyRecords($requestData));
            $mId = intval($machine['m_id']);
            $machineId = strval($machine['machine_id']);

            Db::startTrans();
            $transactionStarted = true;
            $batch = Db::name('machine_tally_batch')
                ->where('machine_id', $machineId)
                ->where('batch_id', $batchData['batch_id'])
                ->lock(true)
                ->find();

            if ($batch) {
                if (!hash_equals(strval($batch['payload_hash'] ?? ''), $batchData['payload_hash'])) {
                    throw new \InvalidArgumentException('该理货批次已经存在，但本次上传内容与原批次不一致');
                }
                $batchLogId = intval($batch['id']);
            } else {
                $batchLogId = $this->insertTallyBatch($mId, $machineId, $batchData);
                $this->insertTallyRecords($batchLogId, $mId, $machineId, $batchData);
            }

            Db::commit();
            $transactionStarted = false;
            $storedBatch = Db::name('machine_tally_batch')->where('id', $batchLogId)->find();
            return $this->tallyResponse(200, '理货记录上传成功', $this->buildTallyBatchResponse($storedBatch));
        } catch (\InvalidArgumentException $e) {
            if ($transactionStarted) Db::rollback();
            return $this->tallyResponse(100, $e->getMessage());
        } catch (\Throwable $e) {
            if ($transactionStarted) Db::rollback();
            actionException($e, 1, 'uploadTallyRecords');
            return $this->tallyResponse(300, $e->getMessage());
        }
    }

    /**
     * 分页查询当前设备的理货批次，每个批次包含全部理货记录。
     * @return array|\think\response\Json
     */
    public function getTallyRecords()
    {
        try {
            $requestData = $this->getRequestData();
            $body = $requestData['data'] ?? [];
            if (is_string($body)) {
                $decoded = json_decode($body, true);
                $body = is_array($decoded) ? $decoded : [];
            }
            $page = max(1, intval($requestData['page'] ?? $body['page'] ?? 1));
            $pageSize = intval(
                $requestData['pageNum']
                ?? $requestData['pageSize']
                ?? $requestData['page_size']
                ?? $body['pageNum']
                ?? $body['pageSize']
                ?? $body['page_size']
                ?? 15
            );
            if ($pageSize <= 0) $pageSize = 15;
            $pageSize = min($pageSize, 100);

            $machineId = trim((string)($this->config['machine_id'] ?? ($requestData['machine_id'] ?? '')));
            $total = intval(Db::name('machine_tally_batch')
                ->where('machine_id', $machineId)
                ->count());
            $batches = Db::name('machine_tally_batch')
                ->where('machine_id', $machineId)
                ->order('batch_started_at desc,id desc')
                ->page($page, $pageSize)
                ->select();
            $batches = $batches ? $batches->toArray() : [];

            $recordsByBatch = [];
            $batchLogIds = array_map('intval', array_column($batches, 'id'));
            if ($batchLogIds) {
                $recordRows = Db::name('machine_tally_record')
                    ->whereIn('batch_log_id', $batchLogIds)
                    ->order('batch_log_id asc,sequence_no asc,id asc')
                    ->select();
                $recordRows = $recordRows ? $recordRows->toArray() : [];
                foreach ($recordRows as $recordRow) {
                    $recordsByBatch[intval($recordRow['batch_log_id'])][] = $recordRow;
                }
            }

            $list = [];
            foreach ($batches as $batch) {
                $batchLogId = intval($batch['id']);
                $list[] = $this->buildTallyBatchResponse($batch, $recordsByBatch[$batchLogId] ?? []);
            }

            return $this->tallyResponse(200, '查询成功', [
                'list' => $list,
                'pagination' => [
                    'page' => $page,
                    'pageSize' => $pageSize,
                    'total' => $total,
                    'totalPage' => $total > 0 ? intval(ceil($total / $pageSize)) : 0,
                ],
            ]);
        } catch (\Throwable $e) {
            actionException($e, 1, 'getTallyRecords');
            return $this->tallyResponse(300, $e->getMessage());
        }
    }

    private function insertTallyBatch($mId, $machineId, array $batchData)
    {
        $batchLogId = intval(Db::name('machine_tally_batch')->insertGetId([
            'm_id' => intval($mId),
            'machine_id' => $machineId,
            'batch_id' => $batchData['batch_id'],
            'mode' => $batchData['mode'],
            'batch_started_at' => $batchData['batch_started_at'],
            'batch_ended_at' => $batchData['batch_ended_at'],
            'summary_success' => $batchData['summary_success'],
            'success_count' => $batchData['success_count'],
            'failure_count' => $batchData['failure_count'],
            'actual_success_count' => $batchData['actual_success_count'],
            'actual_failure_count' => $batchData['actual_failure_count'],
            'item_count' => count($batchData['items']),
            'interrupted' => $batchData['interrupted'],
            'data_status' => $batchData['data_status'],
            'payload_hash' => $batchData['payload_hash'],
            'raw_summary' => $batchData['raw_summary'],
        ]));
        if ($batchLogId <= 0) throw new \RuntimeException('保存理货批次失败');
        return $batchLogId;
    }

    private function insertTallyRecords($batchLogId, $mId, $machineId, array $batchData)
    {
        $channelMap = $this->getTallyChannelMap($mId, $batchData['items']);
        $insertRecords = [];
        foreach ($batchData['items'] as $item) {
            list($mcId, $channelPosition) = $this->resolveTallyChannel($item, $channelMap);
            $eventIdentity = $item['device_item_id'] !== ''
                ? $item['device_item_id']
                : implode('|', [$item['sequence_no'], $item['slot_id'], $item['event_at']]);
            $insertRecords[] = [
                'batch_log_id' => intval($batchLogId),
                'm_id' => intval($mId),
                'machine_id' => $machineId,
                'batch_id' => $batchData['batch_id'],
                'device_item_id' => $item['device_item_id'] !== '' ? $item['device_item_id'] : null,
                'event_key' => hash('sha256', $machineId . '|' . $batchData['batch_id'] . '|' . $eventIdentity),
                'sequence_no' => $item['sequence_no'],
                'slot_id' => $item['slot_id'],
                'mc_id' => $mcId,
                'channel_position' => $channelPosition,
                'success' => $item['success'],
                'error_code' => $item['error_code'],
                'error_message' => $item['error_message'],
                'event_at' => $item['event_at'],
                'raw_record' => $item['raw_record'],
            ];
        }
        if (!$insertRecords) return;

        $inserted = Db::name('machine_tally_record')->insertAll($insertRecords);
        if (intval($inserted) !== count($insertRecords)) {
            throw new \RuntimeException('保存理货明细失败');
        }
    }

    private function extractTallyRecords(array $requestData)
    {
        if (array_key_exists('records', $requestData)) {
            $records = $requestData['records'];
        } else {
            $body = $requestData['data'] ?? [];
            if (is_string($body)) {
                $body = json_decode($body, true);
                if (!is_array($body)) throw new \InvalidArgumentException('data不是有效的JSON数据');
            }
            $records = isset($body['records']) ? $body['records'] : $body;
        }

        if (is_string($records)) {
            $records = json_decode($records, true);
            if (!is_array($records)) throw new \InvalidArgumentException('records不是有效的JSON数组');
        }
        if (!is_array($records) || !$records) throw new \InvalidArgumentException('理货记录不能为空');
        if (count($records) > 2000) throw new \InvalidArgumentException('单个理货批次最多允许2000条记录');
        foreach ($records as $record) {
            if (!is_array($record)) throw new \InvalidArgumentException('理货记录格式错误');
        }
        return array_values($records);
    }

    private function normalizeTallyBatch(array $records)
    {
        $summary = null;
        $summaryCount = 0;
        foreach ($records as $record) {
            if ($this->normalizeTallyKind($this->getTallyValue($record, ['kind'])) === 'summary') {
                $summary = $record;
                $summaryCount++;
            }
        }
        if ($summaryCount !== 1 || !$summary) {
            throw new \InvalidArgumentException('每个理货批次必须且只能包含一条summary记录');
        }

        $batchId = trim(strval($this->getTallyValue($summary, ['batchId', 'batch_id'])));
        if ($batchId === '' || mb_strlen($batchId) > 64) {
            throw new \InvalidArgumentException('批次ID不能为空且长度不能超过64个字符');
        }
        $mode = trim(strval($this->getTallyValue($summary, ['mode'])));
        if (mb_strlen($mode) > 32) throw new \InvalidArgumentException('理货模式长度不能超过32个字符');

        $batchStartedAt = $this->normalizeTallyDateTime(
            $this->getTallyValue($summary, ['batchStartedAt', 'batch_started_at']),
            '批次开始时间'
        );
        $batchEndedAt = $this->normalizeTallyDateTime(
            $this->getTallyValue($summary, ['batchEndedAt', 'batch_ended_at']),
            '批次结束时间'
        );
        if ($batchEndedAt < $batchStartedAt) {
            throw new \InvalidArgumentException('批次结束时间不能早于开始时间');
        }

        $summarySuccess = $this->normalizeTallyBoolean(
            $this->getTallyValue($summary, ['success']),
            'summary.success'
        );
        $interrupted = $this->normalizeTallyBoolean(
            $this->getTallyValue($summary, ['interrupted']),
            'summary.interrupted'
        );
        $successCount = $this->normalizeTallyCount(
            $this->getTallyValue($summary, ['successCount', 'success_count']),
            'successCount'
        );
        $failureCount = $this->normalizeTallyCount(
            $this->getTallyValue($summary, ['failureCount', 'failure_count']),
            'failureCount'
        );

        $items = [];
        $actualSuccessCount = 0;
        $actualFailureCount = 0;
        foreach ($records as $record) {
            $kind = $this->normalizeTallyKind($this->getTallyValue($record, ['kind']));
            if ($kind === 'summary') continue;
            if ($kind !== 'item') {
                throw new \InvalidArgumentException('不支持的理货记录kind，当前仅支持item和summary');
            }

            $itemBatchId = trim(strval($this->getTallyValue($record, ['batchId', 'batch_id'])));
            if ($itemBatchId !== $batchId) throw new \InvalidArgumentException('一次请求只能上传同一个理货批次');

            $itemMode = trim(strval($this->getTallyValue($record, ['mode'])));
            if ($itemMode !== '' && $itemMode !== $mode) {
                throw new \InvalidArgumentException('理货明细的mode与summary不一致');
            }
            $itemStartedAtValue = $this->getTallyValue($record, ['batchStartedAt', 'batch_started_at']);
            if ($itemStartedAtValue !== null && $itemStartedAtValue !== '') {
                $itemStartedAt = $this->normalizeTallyDateTime($itemStartedAtValue, '明细批次开始时间');
                if ($itemStartedAt !== $batchStartedAt) {
                    throw new \InvalidArgumentException('理货明细的批次开始时间与summary不一致');
                }
            }

            $slotId = trim(strval($this->getTallyValue($record, ['slotId', 'slot_id'])));
            if ($slotId === '' || mb_strlen($slotId) > 32) {
                throw new \InvalidArgumentException('slotId不能为空且长度不能超过32个字符');
            }
            $success = $this->normalizeTallyBoolean(
                $this->getTallyValue($record, ['success']),
                'item.success'
            );
            if ($success) {
                $actualSuccessCount++;
            } else {
                $actualFailureCount++;
            }

            $errorCode = trim(strval($this->getTallyValue($record, ['errorCode', 'error_code']) ?? ''));
            $errorMessage = trim(strval(
                $this->getTallyValue($record, ['errorMessage', 'error_message', 'message']) ?? ''
            ));
            if (mb_strlen($errorCode) > 64 || mb_strlen($errorMessage) > 500) {
                throw new \InvalidArgumentException('失败代码或失败原因长度超过限制');
            }
            $deviceItemId = trim(strval(
                $this->getTallyValue($record, ['itemId', 'item_id', 'deviceItemId', 'device_item_id']) ?? ''
            ));
            if (mb_strlen($deviceItemId) > 64) {
                throw new \InvalidArgumentException('设备明细ID长度不能超过64个字符');
            }

            $items[] = [
                'sequence_no' => count($items) + 1,
                'device_item_id' => $deviceItemId,
                'slot_id' => $slotId,
                'requested_mc_id' => intval($this->getTallyValue($record, ['mcId', 'mc_id']) ?? 0),
                'requested_channel_position' => intval(
                    $this->getTallyValue($record, ['channelPosition', 'channel_position']) ?? 0
                ),
                'success' => $success ? 1 : 0,
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
                'event_at' => $this->normalizeTallyDateTime(
                    $this->getTallyValue($record, ['time', 'eventAt', 'event_at']),
                    '明细时间'
                ),
                'raw_record' => $this->encodeTallyJson($record),
            ];
        }

        $countMatched = $successCount === $actualSuccessCount
            && $failureCount === $actualFailureCount;

        return [
            'batch_id' => $batchId,
            'mode' => $mode,
            'batch_started_at' => $batchStartedAt,
            'batch_ended_at' => $batchEndedAt,
            'summary_success' => $summarySuccess ? 1 : 0,
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'actual_success_count' => $actualSuccessCount,
            'actual_failure_count' => $actualFailureCount,
            'interrupted' => $interrupted ? 1 : 0,
            'data_status' => $interrupted ? 4 : ($countMatched ? 2 : 3),
            'payload_hash' => hash('sha256', $this->encodeTallyJson($this->canonicalizeTallyValue($records))),
            'raw_summary' => $this->encodeTallyJson($summary),
            'items' => $items,
        ];
    }

    private function getTallyChannelMap($mId, array $items)
    {
        $slotIds = array_values(array_unique(array_column($items, 'slot_id')));
        if (!$slotIds) return [];

        $rows = Db::name('machine_channel')
            ->where('m_id', intval($mId))
            ->whereIn('channel_code', $slotIds)
            ->field('mc_id,channel_code,channel_position')
            ->select();
        $rows = $rows ? $rows->toArray() : [];
        $map = [];
        foreach ($rows as $row) {
            $map[strval($row['channel_code'])][] = [
                'mc_id' => intval($row['mc_id']),
                'channel_position' => intval($row['channel_position']),
            ];
        }
        return $map;
    }

    private function resolveTallyChannel(array $item, array $channelMap)
    {
        $candidates = $channelMap[$item['slot_id']] ?? [];
        $requestedMcId = intval($item['requested_mc_id']);
        $requestedPosition = intval($item['requested_channel_position']);

        foreach ($candidates as $candidate) {
            if ($requestedMcId > 0 && intval($candidate['mc_id']) === $requestedMcId) {
                return [intval($candidate['mc_id']), intval($candidate['channel_position'])];
            }
        }
        if ($requestedPosition > 0) {
            $matched = array_values(array_filter($candidates, function ($candidate) use ($requestedPosition) {
                return intval($candidate['channel_position']) === $requestedPosition;
            }));
            if (count($matched) === 1) {
                return [intval($matched[0]['mc_id']), intval($matched[0]['channel_position'])];
            }
        }
        if (count($candidates) === 1) {
            return [intval($candidates[0]['mc_id']), intval($candidates[0]['channel_position'])];
        }
        return [0, $requestedPosition];
    }

    private function buildTallyBatchResponse($batch, $rows = null)
    {
        if (!$batch) return [];

        if ($rows === null) {
            $rows = Db::name('machine_tally_record')
                ->where('batch_log_id', intval($batch['id']))
                ->order('sequence_no asc,id asc')
                ->select();
            $rows = $rows ? $rows->toArray() : [];
        } elseif (is_object($rows) && method_exists($rows, 'toArray')) {
            $rows = $rows->toArray();
        }
        $records = [];
        foreach ($rows as $row) {
            $rawRecord = json_decode(strval($row['raw_record'] ?? ''), true);
            $records[] = is_array($rawRecord) ? $rawRecord : $this->buildTallyItemFallback($batch, $row);
        }

        $rawSummary = json_decode(strval($batch['raw_summary'] ?? ''), true);
        if (!is_array($rawSummary)) $rawSummary = $this->buildTallySummaryFallback($batch);
        $records[] = $rawSummary;

        $countMatched = intval($batch['success_count']) === intval($batch['actual_success_count'])
            && intval($batch['failure_count']) === intval($batch['actual_failure_count']);
        return [
            'machineId' => strval($batch['machine_id']),
            'batchId' => strval($batch['batch_id']),
            'batchStartedAt' => $this->getTallyValue($rawSummary, ['batchStartedAt', 'batch_started_at'])
                ?: $this->formatTallyDateTime($batch['batch_started_at'] ?? ''),
            'batchEndedAt' => $this->getTallyValue($rawSummary, ['batchEndedAt', 'batch_ended_at'])
                ?: $this->formatTallyDateTime($batch['batch_ended_at'] ?? ''),
            'mode' => strval($batch['mode']),
            'success' => intval($batch['summary_success']) === 1,
            'successCount' => intval($batch['success_count']),
            'failureCount' => intval($batch['failure_count']),
            'actualSuccessCount' => intval($batch['actual_success_count']),
            'actualFailureCount' => intval($batch['actual_failure_count']),
            'itemCount' => intval($batch['item_count']),
            'interrupted' => intval($batch['interrupted']) === 1,
            'countMatched' => $countMatched,
            'dataStatus' => intval($batch['data_status']),
            'records' => $records,
        ];
    }

    private function buildTallyItemFallback(array $batch, array $row)
    {
        return [
            'time' => $this->formatTallyDateTime($row['event_at'] ?? ''),
            'kind' => 'item',
            'batchId' => strval($batch['batch_id']),
            'batchStartedAt' => $this->formatTallyDateTime($batch['batch_started_at'] ?? ''),
            'mode' => strval($batch['mode']),
            'slotId' => strval($row['slot_id']),
            'success' => intval($row['success']) === 1,
        ];
    }

    private function buildTallySummaryFallback(array $batch)
    {
        return [
            'time' => $this->formatTallyDateTime($batch['batch_ended_at'] ?? ''),
            'kind' => 'summary',
            'batchId' => strval($batch['batch_id']),
            'batchStartedAt' => $this->formatTallyDateTime($batch['batch_started_at'] ?? ''),
            'batchEndedAt' => $this->formatTallyDateTime($batch['batch_ended_at'] ?? ''),
            'mode' => strval($batch['mode']),
            'slotId' => '',
            'success' => intval($batch['summary_success']) === 1,
            'successCount' => intval($batch['success_count']),
            'failureCount' => intval($batch['failure_count']),
            'interrupted' => intval($batch['interrupted']) === 1,
        ];
    }

    private function getTallyValue(array $data, array $keys)
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) return $data[$key];
        }
        return null;
    }

    private function normalizeTallyKind($kind)
    {
        $kind = strtolower(trim(strval($kind)));
        if (in_array($kind, ['item', 'record', '明细', '记录'], true)) return 'item';
        if (in_array($kind, ['summary', '汇总', '合计'], true)) return 'summary';
        return $kind;
    }

    private function normalizeTallyBoolean($value, $field)
    {
        if (is_bool($value)) return $value;
        if ($value === 1 || $value === '1') return true;
        if ($value === 0 || $value === '0') return false;
        $value = strtolower(trim(strval($value)));
        if (in_array($value, ['true', 'yes', '成功', '是'], true)) return true;
        if (in_array($value, ['false', 'no', '失败', '否'], true)) return false;
        throw new \InvalidArgumentException($field . '必须是布尔值');
    }

    private function normalizeTallyCount($value, $field)
    {
        if (!is_numeric($value) || intval($value) < 0 || strval(intval($value)) !== strval($value)) {
            throw new \InvalidArgumentException($field . '必须是非负整数');
        }
        return intval($value);
    }

    private function normalizeTallyDateTime($value, $field)
    {
        $value = trim(strval($value));
        if ($value === '' || !preg_match(
            '/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:\d{2})?$/',
            $value
        )) {
            throw new \InvalidArgumentException($field . '格式错误');
        }
        try {
            $dateTime = new \DateTimeImmutable($value);
            $timezone = new \DateTimeZone(date_default_timezone_get() ?: 'Asia/Shanghai');
            return $dateTime->setTimezone($timezone)->format('Y-m-d H:i:s.u');
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException($field . '格式错误');
        }
    }

    private function formatTallyDateTime($value)
    {
        $value = trim(strval($value));
        return $value === '' ? '' : str_replace(' ', 'T', $value);
    }

    private function encodeTallyJson($data)
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new \InvalidArgumentException('理货数据包含无法编码的中文或非法字符');
        }
        return $json;
    }

    private function canonicalizeTallyValue($value)
    {
        if (!is_array($value)) return $value;
        $isList = !$value || array_keys($value) === range(0, count($value) - 1);
        if (!$isList) ksort($value);
        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalizeTallyValue($item);
        }
        return $value;
    }

    private function tallyResponse($state, $message, $data = [])
    {
        return json([
            'state' => intval($state),
            'msg' => strval($message),
            'data' => $data,
        ], 200, [], [
            'json_encode_param' => JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        ]);
    }

    private function getRequestData()
    {
        $data = $this->config['data'] ?? [];
        return is_array($data) ? $data : json2arr($data);
    }
}
