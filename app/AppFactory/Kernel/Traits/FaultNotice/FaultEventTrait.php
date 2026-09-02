<?php

namespace app\AppFactory\Kernel\Traits\FaultNotice;

use app\AppFactory\Kernel\Support\FaultNotice\FaultWechatTemplate;
use think\facade\Db;

/**
 * 故障事件列表、筛选及导出共用查询逻辑。
 */
trait FaultEventTrait
{
    /**
     * @param array $params
     * @param int $pageNum 每页条数，沿用后台现有pageNum语义
     * @return mixed
     */
    public function getFaultEventList($params = [], $pageNum = 20)
    {
        $pageNum = max(1, min(intval($pageNum), 100));
        $paginator = $this->buildFaultEventQuery($params)
            ->order('mec.create_time desc,mec.me_id desc')
            ->paginate($pageNum, false, ['query' => request()->param()]);

        return $paginator->each(function ($item) {
            return $this->formatFaultEventRow($item);
        });
    }

    /**
     * 故障详情扁平返回，不包装basic_info/fault_detail。
     */
    public function getFaultEventDetailData($meId)
    {
        $meId = intval($meId);
        if ($meId <= 0) {
            return [];
        }

        $item = $this->buildFaultEventQuery(['me_id' => $meId])->find();
        if (!$item) {
            return [];
        }

        return $this->formatFaultEventDetailRow($item);
    }

    /**
     * 通知记录单独分页。必须先校验事件可见范围，再读取微信日志。
     *
     * @return mixed|null null表示事件不存在或无查看权限
     */
    public function getFaultEventNoticeListData($meId, $pageNum = 20)
    {
        $meId = intval($meId);
        $pageNum = max(1, min(intval($pageNum), 100));
        if ($meId <= 0 || !$this->findVisibleFaultEvent($meId)) {
            return null;
        }

        $paginator = Db::name('wx_template_log')
            ->alias('wtl')
            ->leftJoin('auth_manager am', 'am.manager_id = wtl.manager_id')
            ->where('wtl.me_id', $meId)
            ->whereIn('wtl.template_type', FaultWechatTemplate::types())
            ->field(
                "wtl.wtl_id,wtl.wt_id,wtl.template_type,wtl.template_name,wtl.manager_id," .
                "COALESCE(NULLIF(wtl.nickname,''),NULLIF(am.nickname,''),'') AS receiver_name," .
                "COALESCE(NULLIF(am.account,''),'') AS receiver_account," .
                "wtl.send_status,wtl.confirm_status,wtl.confirm_time,wtl.remark,wtl.create_time"
            )
            ->order('wtl.create_time desc,wtl.wtl_id desc')
            ->paginate($pageNum, false, ['query' => request()->param()]);

        return $paginator->each(function ($item) {
            return $this->formatFaultEventNoticeRow($item);
        });
    }

    /**
     * 导出与列表共用筛选条件；未传时间时默认导出最近一个月。
     */
    public function getFaultEventExportList($params = [])
    {
        if (!$this->hasFaultEventTimeFilter($params)) {
            $params['start_time'] = strtotime('-1 month');
            $params['end_time'] = time();
        }

        $rows = $this->buildFaultEventQuery($params)
            ->order('mec.create_time desc,mec.me_id desc')
            ->select()
            ->toArray();

        return array_map(function ($item) {
            return $this->formatFaultEventRow($item);
        }, $rows);
    }

    /** @return \think\db\Query */
    protected function buildFaultEventQuery($params)
    {
        $query = Db::name('machine_error_code')
            ->alias('mec')
            ->leftJoin(
                'machine_error_code_notice_rule mecnr',
                'mecnr.error_code = mec.errorCode'
            )
            ->leftJoin(
                'machine_fault_category mfc',
                'mfc.category_id = mec.category_id'
            )
            ->leftJoin('machine_fault_level mfl', 'mfl.level = mec.level')
            ->leftJoin('auth_manager ham', 'ham.manager_id = mec.handle_manager_id')
            ->field($this->getFaultEventFields());

        $this->applyFaultEventScope($query);
        $this->applyFaultEventManagerStartTime($query);
        $this->applyFaultEventTimeFilter($query, $params);
        $this->applyFaultEventIntegerFilters($query, $params);
        $this->applyFaultEventDeviceFilters($query, $params);
        $this->applyFaultEventCodeFilters($query, $params);
        $this->applyFaultEventKeywordFilter($query, $params);

        return $query;
    }

    protected function getFaultEventFields()
    {
        return "mec.me_id,mec.m_id,mec.machine_id,mec.machine_name,mec.address," .
            "mec.error_position,mec.errorCode,mec.remark,mec.category_id,mec.level," .
            "mec.status,mec.notice_status,mec.notice_reason,mec.notice_time," .
            "mec.handle_manager_id,mec.handle_time,mec.create_time," .
            "CASE WHEN mec.errorCode='11103021' " .
            "THEN COALESCE(NULLIF(mec.remark,''),NULLIF(mecnr.error_name,''),mec.errorCode) " .
            "ELSE COALESCE(NULLIF(mecnr.error_name,''),NULLIF(mec.remark,''),mec.errorCode) END AS error_name," .
            "COALESCE(NULLIF(mfc.category_name,''),'未分类') AS category_name," .
            "COALESCE(mfl.grade,mec.level) AS grade," .
            "COALESCE(NULLIF(mfl.level_name,''),'') AS level_name," .
            "COALESCE(NULLIF(mfl.color,''),'') AS level_color," .
            "COALESCE(NULLIF(ham.nickname,''),NULLIF(ham.account,''),'') AS handle_manager_name," .
            "COALESCE((SELECT GROUP_CONCAT(DISTINCT mgg.mg_name ORDER BY mgg.id SEPARATOR ',') " .
            "FROM machine_group_mg mgg WHERE mgg.m_id = mec.m_id),'') AS machine_group_name," .
            "(SELECT COUNT(*) FROM wx_template_log wtl " .
            "WHERE wtl.me_id = mec.me_id AND wtl.template_type IN ('mFault','mOffline','mShipmentFailed')) AS notice_receiver_count," .
            "(SELECT COUNT(*) FROM wx_template_log wtl " .
            "WHERE wtl.me_id = mec.me_id AND wtl.template_type IN ('mFault','mOffline','mShipmentFailed') AND wtl.send_status = 1) AS notice_success_count," .
            "(SELECT COUNT(*) FROM wx_template_log wtl " .
            "WHERE wtl.me_id = mec.me_id AND wtl.template_type IN ('mFault','mOffline','mShipmentFailed') AND wtl.send_status <> 1) AS notice_failed_count";
    }

    protected function applyFaultEventScope($query)
    {
    }

    /**
     * 只返回当前账号有权查看的事件，用于详情关联数据的前置鉴权。
     */
    protected function findVisibleFaultEvent($meId)
    {
        $query = Db::name('machine_error_code')->alias('mec');
        $this->applyFaultEventScope($query);
        $this->applyFaultEventManagerStartTime($query);
        return $query->where('mec.me_id', intval($meId))->field('mec.me_id')->find();
    }

    /**
     * 兼容后台账号配置的“允许查询起始时间”。
     */
    protected function applyFaultEventManagerStartTime($query)
    {
    }

    protected function applyFaultEventTimeFilter($query, $params)
    {
        $start = null;
        $end = null;
        $range = trim(strval($params['create_time'] ?? ($params['time_range'] ?? '')));
        if ($range !== '' && strpos($range, '~') !== false) {
            $parts = explode('~', $range, 2);
            $start = $this->parseFaultEventTime($parts[0], false);
            $end = $this->parseFaultEventTime($parts[1], true);
        } else {
            if (isset($params['start_time']) && $params['start_time'] !== '') {
                $start = $this->parseFaultEventTime($params['start_time'], false);
            }
            if (isset($params['end_time']) && $params['end_time'] !== '') {
                $end = $this->parseFaultEventTime($params['end_time'], true);
            }
        }

        if ($start !== null && $end !== null) {
            if ($start > $end) {
                $temp = $start;
                $start = $end;
                $end = $temp;
            }
            $query->whereBetween('mec.create_time', [$start, $end]);
        } elseif ($start !== null) {
            $query->where('mec.create_time', '>=', $start);
        } elseif ($end !== null) {
            $query->where('mec.create_time', '<=', $end);
        }
    }

    protected function applyFaultEventIntegerFilters($query, $params)
    {
        $level = $this->parseFaultEventIntegerList($params['level'] ?? '', [1, 2, 3]);
        if ($level) {
            $query->whereIn('mec.level', $level);
        }

        $eventStatusRaw = $params['event_status'] ?? ($params['status'] ?? '');
        $eventStatus = $this->parseFaultEventIntegerList($eventStatusRaw, [1, 2]);
        if ($eventStatus) {
            $query->whereIn('mec.status', $eventStatus);
        }

        $noticeStatus = $this->parseFaultEventIntegerList(
            $params['notice_status'] ?? '',
            [0, 1, 2, 3, 4]
        );
        if ($noticeStatus) {
            $query->whereIn('mec.notice_status', $noticeStatus);
        }

        $categoryIds = $this->parseFaultEventIntegerList($params['category_id'] ?? '');
        $categoryIds = array_values(array_filter($categoryIds, function ($id) {
            return $id > 0;
        }));
        if ($categoryIds) {
            $query->whereIn('mec.category_id', $categoryIds);
        }

        $meId = intval($params['me_id'] ?? ($params['event_id'] ?? 0));
        if ($meId > 0) {
            $query->where('mec.me_id', $meId);
        }
    }

    protected function applyFaultEventDeviceFilters($query, $params)
    {
        $mId = intval($params['m_id'] ?? 0);
        if ($mId > 0) {
            $query->where('mec.m_id', $mId);
        }

        $machineId = trim(strval($params['machine_id'] ?? ''));
        if ($machineId !== '') {
            $query->where('mec.machine_id', 'like', '%' . $machineId . '%');
        }

        $machineName = trim(strval($params['machine_name'] ?? ''));
        if ($machineName !== '') {
            $query->where('mec.machine_name', 'like', '%' . $machineName . '%');
        }

        $groupRaw = $params['machine_group_id'] ?? ($params['mg_id'] ?? '');
        $groupIds = $this->parseFaultEventIntegerList($groupRaw);
        $groupIds = array_values(array_filter($groupIds, function ($id) {
            return $id > 0;
        }));
        if ($groupIds) {
            $mIds = Db::name('machine_group_mg')->whereIn('mg_id', $groupIds)->column('m_id');
            $mIds = array_values(array_unique(array_filter(array_map('intval', (array)$mIds))));
            if ($mIds) {
                $query->whereIn('mec.m_id', $mIds);
            } else {
                $query->where('mec.m_id', -1);
            }
        }
    }

    protected function applyFaultEventCodeFilters($query, $params)
    {
        $errorCode = trim(strval($params['error_code'] ?? ($params['errorCode'] ?? '')));
        if ($errorCode !== '') {
            $query->where('mec.errorCode', 'like', '%' . $errorCode . '%');
        }

        // 兼容原系统日志的errorCodeType筛选，后续新页面优先使用category_id。
        $errorCodeType = trim(strval($params['errorCodeType'] ?? ''));
        if ($errorCodeType !== '') {
            $typeConfig = config('error_code_list');
            $codeList = $typeConfig[$errorCodeType]['codeList'] ?? [];
            if ($codeList) {
                $query->whereIn('mec.errorCode', $codeList);
            } else {
                $query->where('mec.me_id', -1);
            }
        }

        $noticeReason = trim(strval($params['notice_reason'] ?? ''));
        if ($noticeReason !== '') {
            $query->where('mec.notice_reason', $noticeReason);
        }
    }

    protected function applyFaultEventKeywordFilter($query, $params)
    {
        $keyword = trim(strval($params['keyword'] ?? ''));
        if ($keyword === '') {
            return;
        }

        $like = '%' . $keyword . '%';
        $query->where(function ($subQuery) use ($like) {
            $subQuery->where('mec.me_id', 'like', $like)
                ->whereOr('mec.machine_id', 'like', $like)
                ->whereOr('mec.machine_name', 'like', $like)
                ->whereOr('mec.errorCode', 'like', $like)
                ->whereOr('mec.remark', 'like', $like)
                ->whereOr('mecnr.error_name', 'like', $like)
                ->whereOr('mfc.category_name', 'like', $like);
        });
    }

    protected function formatFaultEventRow($item)
    {
        $item = is_object($item) ? $item->toArray() : (array)$item;
        $level = intval($item['level'] ?? 0);
        $eventStatus = intval($item['status'] ?? 0);
        $noticeStatus = intval($item['notice_status'] ?? 0);
        $noticeReason = strval($item['notice_reason'] ?? '');
        $createTime = intval($item['create_time'] ?? 0);
        $noticeTime = intval($item['notice_time'] ?? 0);
        $handleTime = intval($item['handle_time'] ?? 0);

        $levelDefaults = [
            1 => ['name' => '紧急', 'color' => '#F5222D'],
            2 => ['name' => '一般', 'color' => '#FA8C16'],
            3 => ['name' => '提示', 'color' => '#1677FF'],
        ];
        $eventStatusNames = [1 => '待处理', 2 => '已处理'];
        $noticeStatusNames = [
            0 => '待判定',
            1 => '通知完成',
            2 => '部分失败',
            3 => '全部失败',
            4 => '未发送',
        ];

        $item['me_id'] = intval($item['me_id'] ?? 0);
        $item['event_id'] = strval($item['me_id']);
        $item['error_code'] = strval($item['errorCode'] ?? '');
        $item['error_name'] = strval($item['error_name'] ?? '');
        $item['category_id'] = intval($item['category_id'] ?? 0);
        $item['category_name'] = strval($item['category_name'] ?? '未分类');
        $item['level'] = $level;
        $item['grade'] = intval($item['grade'] ?? $level);
        $item['level_name'] = strval($item['level_name'] ?? '') ?: ($levelDefaults[$level]['name'] ?? '未知');
        $item['level_color'] = strval($item['level_color'] ?? '') ?: ($levelDefaults[$level]['color'] ?? '');
        $item['event_status'] = $eventStatus;
        $item['event_status_name'] = $eventStatusNames[$eventStatus] ?? '未知';
        $item['notice_status'] = $noticeStatus;
        $item['notice_status_name'] = $noticeStatusNames[$noticeStatus] ?? '未知';
        $item['notice_reason_name'] = $this->getFaultNoticeReasonName($noticeReason);
        $item['notice_receiver_count'] = intval($item['notice_receiver_count'] ?? 0);
        $item['notice_success_count'] = intval($item['notice_success_count'] ?? 0);
        $item['notice_failed_count'] = intval($item['notice_failed_count'] ?? 0);
        $item['create_time'] = $createTime;
        $item['create_time_text'] = $createTime > 0 ? date('Y-m-d H:i:s', $createTime) : '';
        $item['notice_time'] = $noticeTime;
        $item['notice_time_text'] = $noticeTime > 0 ? date('Y-m-d H:i:s', $noticeTime) : '';
        $item['handle_time'] = $handleTime;
        $item['handle_time_text'] = $handleTime > 0 ? date('Y-m-d H:i:s', $handleTime) : '';

        return $item;
    }

    protected function formatFaultEventDetailRow($item)
    {
        $item = $this->formatFaultEventRow($item);
        $fields = [
            'me_id', 'event_id', 'm_id', 'machine_id', 'machine_name', 'machine_group_name',
            'address', 'error_code', 'error_name', 'remark', 'category_id', 'category_name',
            'level', 'grade', 'level_name', 'level_color', 'event_status', 'event_status_name',
            'notice_status', 'notice_status_name', 'notice_reason', 'notice_reason_name',
            'notice_receiver_count', 'notice_success_count', 'notice_failed_count',
            'notice_time', 'notice_time_text', 'handle_manager_id', 'handle_manager_name',
            'handle_time', 'handle_time_text', 'create_time', 'create_time_text',
        ];

        return array_intersect_key($item, array_flip($fields));
    }

    protected function formatFaultEventNoticeRow($item)
    {
        $item = is_object($item) ? $item->toArray() : (array)$item;
        $sendStatus = intval($item['send_status'] ?? 0);
        $confirmStatus = intval($item['confirm_status'] ?? 0);
        $createTime = intval($item['create_time'] ?? 0);
        $confirmTime = intval($item['confirm_time'] ?? 0);

        return [
            'notice_id' => intval($item['wtl_id'] ?? 0),
            'wt_id' => intval($item['wt_id'] ?? 0),
            'template_type' => strval($item['template_type'] ?? ''),
            'template_name' => strval($item['template_name'] ?? ''),
            'channel' => 'wechat',
            'channel_name' => '微信公众号',
            'manager_id' => intval($item['manager_id'] ?? 0),
            'receiver_account' => strval($item['receiver_account'] ?? ''),
            'receiver_name' => strval($item['receiver_name'] ?? ''),
            'send_status' => $sendStatus,
            'send_status_name' => $sendStatus === 1 ? '发送成功' : '发送失败',
            'result_message' => strval($item['remark'] ?? ''),
            'send_time' => $createTime,
            'send_time_text' => $createTime > 0 ? date('Y-m-d H:i:s', $createTime) : '',
            'confirm_status' => $confirmStatus,
            'confirm_status_name' => $confirmStatus === 1 ? '已确认' : '未确认',
            'confirm_time' => $confirmTime,
            'confirm_time_text' => $confirmTime > 0 ? date('Y-m-d H:i:s', $confirmTime) : '',
        ];
    }

    protected function getFaultNoticeReasonName($reason)
    {
        $names = [
            'master_disabled' => '通知总开关已关闭',
            'offline_disabled' => '离线故障通知已关闭',
            'error_code_unconfigured' => '故障码未配置',
            'category_disabled' => '故障分类已停用',
            'error_code_disabled' => '故障项已停用',
            'notice_disabled' => '故障项通知已关闭',
            'quiet_period' => '处于静默时段',
            'frequency_limited' => '触发频率限制',
            'template_invalid' => '微信模板无效',
            'no_receiver' => '没有匹配的接收人',
            'wechat_send_failed' => '微信公众号发送失败',
            'wechat_unbound' => '接收人未绑定微信',
            'wechat_account_mismatch' => '微信公众号不匹配',
        ];
        return $reason === '' ? '' : ($names[$reason] ?? $reason);
    }

    protected function parseFaultEventIntegerList($value, $allowed = [])
    {
        if ($value === '' || $value === null) {
            return [];
        }
        $values = is_array($value) ? $value : explode(',', strval($value));
        $values = array_values(array_unique(array_map('intval', $values)));
        if ($allowed) {
            $values = array_values(array_intersect($values, $allowed));
        }
        return $values;
    }

    protected function parseFaultEventTime($value, $isEnd)
    {
        if (is_numeric($value)) {
            $timestamp = intval($value);
            return $timestamp > 0 ? $timestamp : null;
        }

        $value = trim(strval($value));
        if ($value === '') {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            $value .= $isEnd ? ' 23:59:59' : ' 00:00:00';
        }
        $timestamp = strtotime($value);
        return $timestamp === false ? null : $timestamp;
    }

    protected function hasFaultEventTimeFilter($params)
    {
        return trim(strval($params['create_time'] ?? '')) !== ''
            || trim(strval($params['time_range'] ?? '')) !== ''
            || (isset($params['start_time']) && $params['start_time'] !== '')
            || (isset($params['end_time']) && $params['end_time'] !== '');
    }
}
