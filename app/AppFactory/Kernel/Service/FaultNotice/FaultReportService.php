<?php

namespace app\AppFactory\Kernel\Service\FaultNotice;

use app\AppFactory\AppFactory;
use app\AppFactory\Kernel\Model\Machine\MachineErrorCodeModel;
use app\AppFactory\Kernel\Support\FaultNotice\FaultNoticeConfig;
use app\AppFactory\Kernel\Support\FaultNotice\FaultWechatTemplate;
use app\AppFactory\Kernel\Traits\Auth\AuthOrganizationTrait;
use think\facade\Db;

/**
 * 新故障上报与微信公众号通知编排服务。
 *
 * 只负责新流程；旧MachineErrorCodeTrait::errorCode()保持不动，入口需要回滚时可切回旧方法。
 * 每次调用始终新增machine_error_code，不合并、不更新重复事件、不补发通知。
 */
class FaultReportService
{
    use AuthOrganizationTrait;

    const OFFLINE_ERROR_CODE = '11103021';

    /**
     * @param array|object $machine
     * @param array $message
     * @return int 新增的me_id
     */
    public function report($machine, $message)
    {
        $machine = $this->toArray($machine);
        $message = is_array($message) ? $message : [];
        $mId = intval($machine['m_id'] ?? 0);
        $aoId = intval($machine['ao_id'] ?? 0);
        $errorCode = trim(strval($message['errorCode'] ?? ($message['error_code'] ?? '')));
        $organizationNotice = !empty($message['organization_notice']);
        if ($aoId <= 0 || $errorCode === '' || (!$organizationNotice && $mId <= 0)) {
            throw new \InvalidArgumentException('故障上报缺少设备、组织或故障码');
        }

        $rule = (array)Db::name('machine_error_code_notice_rule')
            ->alias('mecnr')
            ->leftJoin(
                'machine_fault_category mfc',
                'mfc.ao_id = mecnr.ao_id AND mfc.category_id = mecnr.category_id'
            )
            ->where('mecnr.ao_id', $aoId)
            ->where('mecnr.error_code', $errorCode)
            ->field(
                'mecnr.error_code,mecnr.error_name,mecnr.wechat_text,mecnr.category_id,' .
                'mecnr.level,mecnr.status,mecnr.notice_enabled,' .
                'mfc.category_name,mfc.status AS category_status,mfc.template_type'
            )
            ->find();

        $now = time();
        $meId = $this->insertEvent($machine, $message, $rule, $errorCode, $now);
        try {
            $this->processNotice($meId, $machine, $message, $rule, $errorCode, $now);
        } catch (\Throwable $e) {
            // 事件已成功落库后，不再向上抛出导致MQ重投和重复事件；通知也不补发。
            actionException($e, 1, 'faultReportNotice');
            $this->updateEventNotice($meId, 3, 'wechat_send_failed', $now);
        }
        return $meId;
    }

    protected function insertEvent($machine, $message, $rule, $errorCode, $now)
    {
        $position = intval($message['error_position'] ?? 0);
        if ($position <= 0) {
            $length = strlen($errorCode);
            $position = $length === 9 ? 2 : ($length === 7 ? 3 : 1);
        }
        $remark = trim(strval($message['event_remark'] ?? ''));
        if ($remark === '') {
            $remark = trim(strval($rule['error_name'] ?? ''));
        }
        if ($remark === '') {
            $remark = trim(strval($message['msg'] ?? '')) ?: $errorCode;
        }
        $insert = [
            'm_id' => intval($machine['m_id']),
            'machine_id' => strval($machine['machine_id'] ?? ''),
            'machine_name' => strval($machine['machine_name'] ?? ''),
            'address' => strval($machine['address'] ?? ''),
            'error_position' => $position,
            'errorCode' => $errorCode,
            'category_id' => intval($rule['category_id'] ?? 0),
            'level' => intval($rule['level'] ?? 2) ?: 2,
            'remark' => $remark,
            'msg' => strval($message['msg'] ?? ''),
            'trade_no' => $this->getTradeNo($message),
            'ao_id' => intval($machine['ao_id']),
            'notice_status' => 0,
            'notice_reason' => '',
            'notice_time' => 0,
            'create_time' => $now,
        ];
        if (in_array($errorCode, ['1200010', '1200020'], true)
            && !empty($message['creator_id'])) {
            $insert['creator_id'] = intval($message['creator_id']);
        }
        $model = MachineErrorCodeModel::create($insert);
        return intval($model->me_id);
    }

    protected function processNotice($meId, $machine, $message, $rule, $errorCode, $now)
    {
        if (!$rule) {
            return $this->updateEventNotice($meId, 4, 'error_code_unconfigured');
        }
        $aoId = intval($machine['ao_id']);
        $global = (array)Db::name('machine_fault_notice_config')->where('ao_id', $aoId)->find();
        if (intval($global['notice_enabled'] ?? 1) !== 1) {
            return $this->updateEventNotice($meId, 4, 'master_disabled');
        }
        if ($errorCode === self::OFFLINE_ERROR_CODE
            && intval($global['offline_notice_enabled'] ?? 1) !== 1) {
            return $this->updateEventNotice($meId, 4, 'offline_disabled');
        }
        if (intval($rule['category_status'] ?? 0) !== 1) {
            return $this->updateEventNotice($meId, 4, 'category_disabled');
        }
        if (intval($rule['status'] ?? 0) !== 1) {
            return $this->updateEventNotice($meId, 4, 'error_code_disabled');
        }
        if (intval($rule['notice_enabled'] ?? 0) !== 1) {
            return $this->updateEventNotice($meId, 4, 'notice_disabled');
        }

        $level = intval($rule['level'] ?? 2) ?: 2;
        $strategy = $this->getLevelStrategy($aoId, $level);
        if ($this->isQuietPeriod($strategy)) {
            return $this->updateEventNotice($meId, 4, 'quiet_period');
        }
        if (!$this->isFrequencyAllowed($aoId, intval($machine['m_id']), $errorCode, $strategy, $now)) {
            return $this->updateEventNotice($meId, 4, 'frequency_limited');
        }

        $templateType = strval($rule['template_type'] ?? '');
        $templateConfig = FaultWechatTemplate::find($templateType);
        if (!FaultWechatTemplate::isValid($templateType)) {
            return $this->updateEventNotice($meId, 4, 'template_invalid');
        }
        $templateId = FaultWechatTemplate::getTemplateId($templateType);
        $official = $this->getNoticeOfficial($aoId);
        if (!$official) {
            return $this->updateEventNotice($meId, 4, 'wechat_official_unconfigured');
        }
        $receivers = $this->getMatchedReceivers(
            $aoId,
            intval($machine['m_id']),
            intval($rule['category_id']),
            $errorCode,
            intval($official['id']),
            !empty($message['organization_notice'])
        );
        if (!$receivers) {
            return $this->updateEventNotice($meId, 4, 'no_receiver');
        }

        $replaceData = $this->buildReplaceData($machine, $message, $rule, $errorCode, $now);
        foreach ($receivers as $receiver) {
            $template = [
                'wt_id' => 0,
                'wx_id' => intval($official['id']),
                'template_name' => strval($templateConfig['template_name']),
                'template_type' => $templateType,
                'template_id' => $templateId,
                'url' => '',
                'miniprogram' => '',
                'body' => json_encode($templateConfig['body'], JSON_UNESCAPED_UNICODE),
                'ao_id' => $aoId,
            ];
            $noticeData = [
                'ao_id' => $aoId,
                'm_id' => intval($machine['m_id']),
                'me_id' => intval($meId),
                'error_code' => $errorCode,
                'sendType' => 1,
                'templateType' => $templateType,
                'config' => $this->buildOfficialConfig($official),
                'template' => $template,
                'receiver' => [[
                    'manager_id' => intval($receiver['manager_id']),
                    'nickname' => strval($receiver['nickname'] ?? ''),
                    'openid' => strval($receiver['openid']),
                ]],
                'replaceData' => $replaceData,
            ];
            try {
                // 仅发送微信公众号；不调用Application::send()，避免触发邮件。
                AppFactory::notice($noticeData)->weChat->sendV2();
            } catch (\Throwable $e) {
                actionException($e, 1, 'faultWechatSend');
            }
        }

        $logs = Db::name('wx_template_log')
            ->where('me_id', intval($meId))
            ->whereIn('template_type', FaultWechatTemplate::types());
        $success = intval((clone $logs)->where('send_status', 1)->count());
        $expected = count($receivers);
        if ($expected > 0 && $success === $expected) {
            return $this->updateEventNotice($meId, 1, '', $now);
        }
        if ($success > 0) {
            return $this->updateEventNotice($meId, 2, 'wechat_send_failed', $now);
        }
        return $this->updateEventNotice($meId, 3, 'wechat_send_failed', $now);
    }

    protected function getLevelStrategy($aoId, $level)
    {
        $defaults = FaultNoticeConfig::levelStrategyDefaults();
        $defaults = is_array($defaults) ? $defaults : [];
        $strategy = $defaults[$level] ?? [
            'level' => $level,
            'quiet_enabled' => 2,
            'quiet_start' => null,
            'quiet_end' => null,
            'interval_minutes' => 0,
            'day_limit' => 1,
        ];
        $saved = (array)Db::name('machine_fault_notice_frequency')->where([
            'ao_id' => intval($aoId),
            'level' => intval($level),
        ])->find();
        return $saved ? array_merge($strategy, $saved) : $strategy;
    }

    protected function isQuietPeriod($strategy)
    {
        if (intval($strategy['level'] ?? 0) === 1
            || intval($strategy['quiet_enabled'] ?? 2) !== 1) {
            return false;
        }
        $start = substr(strval($strategy['quiet_start'] ?? ''), 0, 8);
        $end = substr(strval($strategy['quiet_end'] ?? ''), 0, 8);
        if ($start === '' || $end === '' || $start === $end) {
            return false;
        }
        $current = date('H:i:s');
        return $start < $end
            ? ($current >= $start && $current < $end)
            : ($current >= $start || $current < $end);
    }

    protected function isFrequencyAllowed($aoId, $mId, $errorCode, $strategy, $now)
    {
        $query = Db::name('wx_template_log')->where([
            'ao_id' => intval($aoId),
            'm_id' => intval($mId),
            'error_code' => strval($errorCode),
        ])->whereIn('template_type', FaultWechatTemplate::types())
            ->where('me_id', '>', 0);

        $dayLimit = intval($strategy['day_limit'] ?? 1);
        $eventIds = (clone $query)
            ->whereBetween('create_time', [strtotime(date('Y-m-d 00:00:00', $now)), strtotime(date('Y-m-d 23:59:59', $now))])
            ->group('me_id')
            ->column('me_id');
        if ($dayLimit > 0 && count(array_unique(array_map('intval', $eventIds))) >= $dayLimit) {
            return false;
        }
        $intervalMinutes = intval($strategy['interval_minutes'] ?? 0);
        if ($intervalMinutes > 0) {
            $last = intval((clone $query)->order('create_time desc')->value('create_time'));
            if ($last > 0 && $now - $last < $intervalMinutes * 60) {
                return false;
            }
        }
        return true;
    }

    protected function getMatchedReceivers($aoId, $mId, $categoryId, $errorCode, $wxId, $organizationNotice = false)
    {
        $rows = Db::name('machine_fault_receiver')
            ->alias('mfr')
            ->join('auth_manager am', 'am.manager_id = mfr.manager_id', 'INNER')
            ->where('mfr.ao_id', intval($aoId))
            ->where('mfr.status', 1)
            ->where('am.status', 1)
            ->where('am.wx_id', intval($wxId))
            ->whereNotNull('am.openid')
            ->where('am.openid', '<>', '')
            ->field(
                'mfr.receiver_id,mfr.manager_id,mfr.machine_scope,mfr.fault_scope,' .
                'am.ao_id manager_ao_id,am.pid,am.nickname,am.account,am.openid,am.wx_id'
            )
            ->order('mfr.receiver_id asc')
            ->select()
            ->toArray();
        $matched = [];
        foreach ($rows as $row) {
            $parentAoIds = $this->getParentIds(intval($row['manager_ao_id'] ?? 0));
            if (!in_array(intval($aoId), array_map('intval', $parentAoIds), true)) {
                continue;
            }
            $receiverId = intval($row['receiver_id']);
            if ($organizationNotice) {
                // 组织级预警没有具体设备，只匹配“全部设备”的接收人。
                if (intval($row['machine_scope']) !== 1
                    || intval($row['pid'] ?? 0) > 0
                    || intval($row['manager_ao_id'] ?? 0) !== intval($aoId)) {
                    continue;
                }
            } else {
                if (intval($row['machine_scope']) === 2
                    && !$this->receiverHasScope($receiverId, 1, strval($mId))) {
                    continue;
                }
                if (intval($row['pid'] ?? 0) > 0
                    && !Db::name('auth_manager_machine')->where([
                        'manager_id' => intval($row['manager_id']),
                        'm_id' => intval($mId),
                    ])->find()) {
                    continue;
                }
            }
            $faultScope = intval($row['fault_scope']);
            if ($faultScope === 2
                && !$this->receiverHasScope($receiverId, 2, strval($categoryId))) {
                continue;
            }
            if ($faultScope === 3
                && !$this->receiverHasScope($receiverId, 3, strval($errorCode))) {
                continue;
            }
            $matched[] = $row;
        }
        return $matched;
    }

    /**
     * 优先使用设备所属组织的有效公众号；未配置时兜底使用系统中最早创建的有效公众号。
     */
    protected function getNoticeOfficial($aoId)
    {
        $field = 'id,gh_id,wx_name,app_id,secret,token,aes_key,ao_id,creator';
        $query = Db::name('wx_official')
            ->where('status', 1)
            ->whereNotNull('app_id')
            ->where('app_id', '<>', '')
            ->whereNotNull('secret')
            ->where('secret', '<>', '');

        $official = (clone $query)
            ->where('ao_id', intval($aoId))
            ->field($field)
            ->order('create_time asc,id asc')
            ->find();
        if ($official) {
            return (array)$official;
        }

        $official = $query
            ->field($field)
            ->order('create_time asc,id asc')
            ->find();
        if ($official) {
            actionLog([
                'device_ao_id' => intval($aoId),
                'fallback_wx_id' => intval($official['id']),
                'fallback_wx_ao_id' => intval($official['ao_id']),
            ], '故障通知使用兜底微信公众号', 'faultWechatOfficialFallback');
        }
        return $official ? (array)$official : [];
    }

    protected function receiverHasScope($receiverId, $scopeType, $targetValue)
    {
        return (bool)Db::name('machine_fault_receiver_scope')->where([
            'receiver_id' => intval($receiverId),
            'scope_type' => intval($scopeType),
            'target_value' => strval($targetValue),
        ])->find();
    }

    protected function buildOfficialConfig($official)
    {
        return [
            'id' => intval($official['id']),
            'gh_id' => strval($official['gh_id'] ?? ''),
            'wx_name' => strval($official['wx_name'] ?? ''),
            'app_id' => strval($official['app_id'] ?? ''),
            'secret' => strval($official['secret'] ?? ''),
            'token' => strval($official['token'] ?? ''),
            'aes_key' => strval($official['aes_key'] ?? ''),
            'ao_id' => intval($official['ao_id'] ?? 0),
            'creator' => intval($official['creator'] ?? 0),
        ];
    }

    protected function buildReplaceData($machine, $message, $rule, $errorCode, $now)
    {
        $tradeNo = $this->getTradeNo($message);
        $channelCode = '';
        if (strval($rule['template_type'] ?? '') === 'mShipmentFailed') {
            $channelCode = trim(strval($message['channel_code'] ?? ($message['channelCode'] ?? '')));
            if ($channelCode === '') {
                $channelCode = $this->getFailedChannelCode($machine, $tradeNo);
            }
        }
        $lastOnline = $message['last_online_time'] ?? ($machine['last_online_time'] ?? 0);
        if (is_numeric($lastOnline)) {
            $lastOnline = intval($lastOnline) > 0 ? date('Y-m-d H:i:s', intval($lastOnline)) : '-';
        }
        return [
            'machine_id' => strval($machine['machine_id'] ?? ''),
            'machine_name' => mb_substr(strval($machine['machine_name'] ?? ''), 0, 20, 'UTF-8'),
            'error_time' => date('Y-m-d H:i:s', $now),
            'error_info' => $errorCode,
            'trade_no' => $tradeNo ?: '-',
            // 通用模板的“设备地址”以及出货模板的“商品名称”均显示该短名称。
            'error_code' => strval($rule['wechat_text'] ?? ''),
            'last_online_time' => strval($lastOnline ?: '-'),
            'offline_minutes' => strval(intval($message['offline_minutes'] ?? 0)),
            'channel_code' => $channelCode ?: '-',
        ];
    }

    protected function getTradeNo($message)
    {
        return trim(strval($message['trade_no'] ?? ($message['order_no'] ?? '')));
    }

    /**
     * 调用方未传货道号时，按出货结果筛选明细，并优先取失败数量最多的货道号。
     */
    protected function getFailedChannelCode($machine, $tradeNo)
    {
        $tradeNo = trim(strval($tradeNo));
        if ($tradeNo === '') {
            return '';
        }

        return trim(strval(Db::name('sale_orders')
            ->alias('so')
            ->join('sale_orders_details sod', 'sod.order_id = so.order_id', 'inner')
            ->where('so.trade_no', $tradeNo)
            ->where('so.m_id', intval($machine['m_id'] ?? 0))
            ->where('so.machine_id', strval($machine['machine_id'] ?? ''))
            ->where('so.ao_id', intval($machine['ao_id'] ?? 0))
            ->whereRaw('((sod.success_quantity = 0 AND sod.fail_quantity = 0) OR sod.fail_quantity > 0)')
            ->order('sod.fail_quantity desc,sod.sod_id asc')
            ->value('sod.channel_code')));
    }

    protected function updateEventNotice($meId, $status, $reason = '', $noticeTime = 0)
    {
        Db::name('machine_error_code')->where('me_id', intval($meId))->update([
            'notice_status' => intval($status),
            'notice_reason' => strval($reason),
            'notice_time' => intval($noticeTime),
        ]);
        return true;
    }

    protected function toArray($value)
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_object($value) && method_exists($value, 'toArray')) {
            return $value->toArray();
        }
        return (array)$value;
    }
}
