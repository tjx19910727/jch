<?php

namespace app\AppFactory\Kernel\Traits\FaultNotice;

use app\AppFactory\Kernel\Support\FaultNotice\FaultWechatTemplate;
use think\facade\Db;

/**
 * 故障通知接收人及其设备、故障范围配置。
 */
trait FaultReceiverTrait
{
    /**
     * 接收配置主列表。列表直接返回范围ID和显示摘要，编辑无需再次查询详情。
     */
    public function getFaultReceiverListData($params = [], $pageNum = 20)
    {
        $pageNum = max(1, min(intval($pageNum), 100));
        $query = Db::name('machine_fault_receiver')
            ->alias('mfr')
            ->leftJoin('auth_manager am', 'am.manager_id = mfr.manager_id')
            ->leftJoin('auth_organization ao', 'ao.ao_id = am.ao_id')
            ->leftJoin('user u', 'u.user_id = am.user_id')
            ->leftJoin('wx_official wo', 'wo.id = am.wx_id')
            ->where('mfr.ao_id', $this->getFaultReceiverAoId())
            ->field(
                "mfr.receiver_id,mfr.manager_id,mfr.machine_scope,mfr.fault_scope," .
                "mfr.status,mfr.update_time,am.account,am.nickname,am.real_name," .
                "am.status AS account_status,am.openid,am.wx_id," .
                "COALESCE(u.mobile,'') AS mobile,COALESCE(ao.organization_name,'') AS organization_name," .
                "COALESCE(wo.wx_name,'') AS wx_name,COALESCE(wo.status,0) AS wx_status"
            );

        $status = intval($params['status'] ?? 0);
        if ($status > 0) {
            if (!in_array($status, [1, 2], true)) {
                throw new \InvalidArgumentException('接收人状态参数错误');
            }
            $query->where('mfr.status', $status);
        }
        $accountStatus = intval($params['account_status'] ?? 0);
        if ($accountStatus > 0) {
            if (!in_array($accountStatus, [1, 2], true)) {
                throw new \InvalidArgumentException('后台账号状态参数错误');
            }
            $query->where('am.status', $accountStatus);
        }
        $keyword = trim(strval($params['keyword'] ?? ''));
        if ($keyword !== '') {
            $like = '%' . $keyword . '%';
            $query->where(function ($subQuery) use ($like) {
                $subQuery->where('am.account', 'like', $like)
                    ->whereOr('am.nickname', 'like', $like)
                    ->whereOr('am.real_name', 'like', $like)
                    ->whereOr('u.mobile', 'like', $like);
            });
        }

        $paginator = $query
            ->order('mfr.update_time desc,mfr.receiver_id desc')
            ->paginate($pageNum, false, ['query' => request()->param()]);

        return $paginator->each(function ($row) {
            return $this->formatFaultReceiverRow($row);
        });
    }

    /**
     * 接收人编辑回显详情，只返回已保存配置，不返回账号、设备、分类等选项列表。
     */
    public function getFaultReceiverDetailData($receiverId)
    {
        $receiverId = intval($receiverId);
        if (!$this->findFaultReceiver($receiverId)) {
            return [];
        }
        return $this->findAndFormatFaultReceiver($receiverId);
    }

    public function addFaultReceiverData($params)
    {
        $managerId = intval($params['manager_id'] ?? 0);
        $manager = $this->getAvailableFaultReceiverManager($managerId);
        if (Db::name('machine_fault_receiver')->where([
            'ao_id' => $this->getFaultReceiverAoId(),
            'manager_id' => $managerId,
        ])->find()) {
            throw new \InvalidArgumentException('该后台账号已经配置为通知接收人');
        }
        $this->validateFaultReceiverWechat($manager);
        $scopeData = $this->normalizeFaultReceiverScopes($params, $manager);
        $status = intval($params['status'] ?? 1);
        if (!in_array($status, [1, 2], true)) {
            throw new \InvalidArgumentException('接收人状态参数错误');
        }

        $operatorId = intval($this->manager['manager_id'] ?? 0);
        $now = time();
        $receiverId = intval(Db::name('machine_fault_receiver')->insertGetId([
            'ao_id' => $this->getFaultReceiverAoId(),
            'manager_id' => $managerId,
            'machine_scope' => $scopeData['machine_scope'],
            'fault_scope' => $scopeData['fault_scope'],
            'status' => $status,
            'creator' => $operatorId,
            'create_time' => $now,
            'update_id' => $operatorId,
            'update_time' => $now,
        ]));
        $this->insertFaultReceiverScopeRows($receiverId, $scopeData['scope_rows'], $now);

        return $this->findAndFormatFaultReceiver($receiverId);
    }

    public function updateFaultReceiverData($params)
    {
        $receiverId = intval($params['receiver_id'] ?? 0);
        $receiver = $this->findFaultReceiver($receiverId);
        if (!$receiver) {
            throw new \InvalidArgumentException('通知接收人不存在');
        }
        $manager = $this->getAvailableFaultReceiverManager(intval($receiver['manager_id']));
        if (isset($params['manager_id'])
            && intval($params['manager_id']) !== intval($receiver['manager_id'])) {
            throw new \InvalidArgumentException('通知接收人的后台账号不允许修改');
        }
        $this->validateFaultReceiverWechat($manager);
        $scopeData = $this->normalizeFaultReceiverScopes($params, $manager);
        $status = intval($params['status'] ?? $receiver['status']);
        if (!in_array($status, [1, 2], true)) {
            throw new \InvalidArgumentException('接收人状态参数错误');
        }
        $now = time();
        Db::name('machine_fault_receiver')->where([
            'receiver_id' => $receiverId,
            'ao_id' => $this->getFaultReceiverAoId(),
        ])->update([
            'machine_scope' => $scopeData['machine_scope'],
            'fault_scope' => $scopeData['fault_scope'],
            'status' => $status,
            'update_id' => intval($this->manager['manager_id'] ?? 0),
            'update_time' => $now,
        ]);

        // 已在写库前完成全部范围校验；按当前提交结果完整替换范围明细。
        Db::name('machine_fault_receiver_scope')->where('receiver_id', $receiverId)->delete();
        $this->insertFaultReceiverScopeRows($receiverId, $scopeData['scope_rows'], $now);

        return $this->findAndFormatFaultReceiver($receiverId);
    }

    public function updateFaultReceiverStatusData($receiverId, $status)
    {
        $receiverId = intval($receiverId);
        $status = intval($status);
        if (!in_array($status, [1, 2], true)) {
            throw new \InvalidArgumentException('接收人状态参数错误');
        }
        if (!$this->findFaultReceiver($receiverId)) {
            throw new \InvalidArgumentException('通知接收人不存在');
        }
        Db::name('machine_fault_receiver')->where([
            'receiver_id' => $receiverId,
            'ao_id' => $this->getFaultReceiverAoId(),
        ])->update([
            'status' => $status,
            'update_id' => intval($this->manager['manager_id'] ?? 0),
            'update_time' => time(),
        ]);
        return $this->findAndFormatFaultReceiver($receiverId);
    }

    public function deleteFaultReceiverData($receiverId)
    {
        $receiverId = intval($receiverId);
        $receiver = $this->findFaultReceiver($receiverId);
        if (!$receiver) {
            throw new \InvalidArgumentException('通知接收人不存在');
        }
        Db::name('machine_fault_receiver_scope')->where('receiver_id', $receiverId)->delete();
        Db::name('machine_fault_receiver')->where([
            'receiver_id' => $receiverId,
            'ao_id' => $this->getFaultReceiverAoId(),
        ])->delete();
        return [
            'receiver_id' => $receiverId,
            'manager_id' => intval($receiver['manager_id']),
        ];
    }

    protected function normalizeFaultReceiverScopes($params, $manager)
    {
        $machineScope = intval($params['machine_scope'] ?? 1);
        $faultScope = intval($params['fault_scope'] ?? 2);
        if (!in_array($machineScope, [1, 2], true)) {
            throw new \InvalidArgumentException('设备范围参数错误');
        }
        if (!in_array($faultScope, [1, 2, 3], true)) {
            throw new \InvalidArgumentException('故障范围参数错误');
        }

        $machineIds = $this->normalizeFaultReceiverValueList($params['machine_ids'] ?? []);
        $categoryIds = $this->normalizeFaultReceiverValueList($params['category_ids'] ?? []);
        $errorCodes = $this->normalizeFaultReceiverValueList($params['error_codes'] ?? [], false);
        $rows = [];

        if ($machineScope === 2) {
            if (!$machineIds) {
                throw new \InvalidArgumentException('指定设备范围时至少选择一台设备');
            }
            $this->validateFaultReceiverMachines($machineIds, $manager);
            foreach ($machineIds as $mId) {
                $rows[] = ['scope_type' => 1, 'target_value' => strval($mId)];
            }
        }

        if ($faultScope === 2) {
            if (!$categoryIds) {
                throw new \InvalidArgumentException('指定故障分类时至少选择一个分类');
            }
            $this->validateFaultReceiverCategories($categoryIds, intval($manager['wx_id']));
            foreach ($categoryIds as $categoryId) {
                $rows[] = ['scope_type' => 2, 'target_value' => strval($categoryId)];
            }
        } elseif ($faultScope === 3) {
            if (!$errorCodes) {
                throw new \InvalidArgumentException('指定故障码时至少选择一个故障码');
            }
            $this->validateFaultReceiverCodes($errorCodes, intval($manager['wx_id']));
            foreach ($errorCodes as $errorCode) {
                $rows[] = ['scope_type' => 3, 'target_value' => strval($errorCode)];
            }
        }

        return [
            'machine_scope' => $machineScope,
            'fault_scope' => $faultScope,
            'scope_rows' => $rows,
        ];
    }

    protected function validateFaultReceiverMachines($machineIds, $manager)
    {
        $machineIds = array_values(array_unique(array_map('intval', $machineIds)));
        $query = Db::name('machine')
            ->where('ao_id', $this->getFaultReceiverAoId())
            ->whereIn('m_id', $machineIds);
        $validIds = array_map('intval', $query->column('m_id'));
        if (count($validIds) !== count($machineIds)) {
            throw new \InvalidArgumentException('指定设备不存在或不属于当前组织');
        }

        if (intval($manager['pid'] ?? 0) > 0) {
            $authorizedIds = Db::name('auth_manager_machine')
                ->where('manager_id', intval($manager['manager_id']))
                ->whereIn('m_id', $machineIds)
                ->column('m_id');
            $authorizedIds = array_values(array_unique(array_map('intval', $authorizedIds)));
            if (count($authorizedIds) !== count($machineIds)) {
                throw new \InvalidArgumentException('指定设备超出该后台账号的设备权限');
            }
        }
    }

    protected function validateFaultReceiverCategories($categoryIds, $wxId)
    {
        $categoryIds = array_values(array_unique(array_map('intval', $categoryIds)));
        $rows = Db::name('machine_fault_category')
            ->alias('mfc')
            ->where('mfc.ao_id', $this->getFaultReceiverAoId())
            ->where('mfc.status', 1)
            ->whereIn('mfc.template_type', FaultWechatTemplate::types())
            ->whereIn('mfc.category_id', $categoryIds)
            ->column('mfc.category_id');
        $validIds = array_values(array_unique(array_map('intval', $rows)));
        if (count($validIds) !== count($categoryIds)) {
            throw new \InvalidArgumentException('故障分类不存在、已停用或模板类型不可用');
        }
    }

    protected function validateFaultReceiverCodes($errorCodes, $wxId)
    {
        $errorCodes = array_values(array_unique(array_map('strval', $errorCodes)));
        $rows = Db::name('machine_error_code_notice_rule')
            ->alias('mecnr')
            ->innerJoin(
                'machine_fault_category mfc',
                'mfc.ao_id = mecnr.ao_id AND mfc.category_id = mecnr.category_id'
            )
            ->where('mecnr.ao_id', $this->getFaultReceiverAoId())
            ->where('mecnr.status', 1)
            ->where('mfc.status', 1)
            ->whereIn('mfc.template_type', FaultWechatTemplate::types())
            ->whereIn('mecnr.error_code', $errorCodes)
            ->column('mecnr.error_code');
        $validCodes = array_values(array_unique(array_map('strval', $rows)));
        sort($validCodes);
        sort($errorCodes);
        if ($validCodes !== $errorCodes) {
            throw new \InvalidArgumentException('故障码不存在、已停用或模板类型不可用');
        }
    }

    protected function getAvailableFaultReceiverManager($managerId)
    {
        $managerId = intval($managerId);
        if ($managerId <= 0) {
            throw new \InvalidArgumentException('请选择后台账号');
        }
        $manager = (array)Db::name('auth_manager')
            ->where('manager_id', $managerId)
            ->field('manager_id,ao_id,pid,account,nickname,openid,wx_id,status')
            ->find();
        if (!$manager) {
            throw new \InvalidArgumentException('后台账号不存在');
        }
        if (intval($manager['status'] ?? 0) !== 1) {
            throw new \InvalidArgumentException('后台账号已停用');
        }
        $parentAoIds = $this->app->authOrganization->getParentIds(
            intval($manager['ao_id'] ?? 0)
        );
        if (!in_array(
            $this->getFaultReceiverAoId(),
            array_map('intval', $parentAoIds),
            true
        )) {
            throw new \InvalidArgumentException('后台账号不属于当前组织或下级组织');
        }
        return $manager;
    }

    protected function validateFaultReceiverWechat($manager)
    {
        if (trim(strval($manager['openid'] ?? '')) === '' || intval($manager['wx_id'] ?? 0) <= 0) {
            throw new \InvalidArgumentException('该后台账号尚未绑定微信公众号');
        }
        if (!Db::name('wx_official')->where([
            'id' => intval($manager['wx_id']),
            'status' => 1,
        ])->find()) {
            throw new \InvalidArgumentException('该后台账号绑定的微信公众号不可用');
        }
    }

    protected function insertFaultReceiverScopeRows($receiverId, $rows, $createTime)
    {
        if (!$rows) {
            return;
        }
        $insert = [];
        foreach ($rows as $row) {
            $insert[] = [
                'receiver_id' => intval($receiverId),
                'scope_type' => intval($row['scope_type']),
                'target_value' => strval($row['target_value']),
                'create_time' => intval($createTime),
            ];
        }
        Db::name('machine_fault_receiver_scope')->insertAll($insert);
    }

    protected function normalizeFaultReceiverValueList($value, $integer = true)
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : explode(',', $value);
        }
        if (!is_array($value)) {
            $value = [$value];
        }
        $value = array_map(function ($item) use ($integer) {
            return $integer ? intval($item) : trim(strval($item));
        }, $value);
        $value = array_filter($value, function ($item) use ($integer) {
            return $integer ? $item > 0 : $item !== '';
        });
        return array_values(array_unique($value));
    }

    protected function findFaultReceiver($receiverId)
    {
        $receiverId = intval($receiverId);
        if ($receiverId <= 0) {
            return [];
        }
        return (array)Db::name('machine_fault_receiver')->where([
            'receiver_id' => $receiverId,
            'ao_id' => $this->getFaultReceiverAoId(),
        ])->find();
    }

    protected function findAndFormatFaultReceiver($receiverId)
    {
        $row = Db::name('machine_fault_receiver')
            ->alias('mfr')
            ->leftJoin('auth_manager am', 'am.manager_id = mfr.manager_id')
            ->leftJoin('auth_organization ao', 'ao.ao_id = am.ao_id')
            ->leftJoin('user u', 'u.user_id = am.user_id')
            ->leftJoin('wx_official wo', 'wo.id = am.wx_id')
            ->where('mfr.ao_id', $this->getFaultReceiverAoId())
            ->where('mfr.receiver_id', intval($receiverId))
            ->field(
                "mfr.receiver_id,mfr.manager_id,mfr.machine_scope,mfr.fault_scope," .
                "mfr.status,mfr.update_time,am.account,am.nickname,am.real_name," .
                "am.status AS account_status,am.openid,am.wx_id," .
                "COALESCE(u.mobile,'') AS mobile,COALESCE(ao.organization_name,'') AS organization_name," .
                "COALESCE(wo.wx_name,'') AS wx_name,COALESCE(wo.status,0) AS wx_status"
            )
            ->find();
        return $row ? $this->formatFaultReceiverRow($row) : [];
    }

    protected function formatFaultReceiverRow($row)
    {
        $receiverId = intval($row['receiver_id'] ?? 0);
        $scopes = Db::name('machine_fault_receiver_scope')
            ->where('receiver_id', $receiverId)
            ->field('scope_type,target_value')
            ->order('scope_type asc,target_value asc')
            ->select()
            ->toArray();
        $machineIds = [];
        $categoryIds = [];
        $errorCodes = [];
        foreach ($scopes as $scope) {
            if (intval($scope['scope_type']) === 1) {
                $machineIds[] = intval($scope['target_value']);
            } elseif (intval($scope['scope_type']) === 2) {
                $categoryIds[] = intval($scope['target_value']);
            } elseif (intval($scope['scope_type']) === 3) {
                $errorCodes[] = strval($scope['target_value']);
            }
        }

        $machineNames = $machineIds
            ? Db::name('machine')->whereIn('m_id', $machineIds)->order('m_id asc')->column('machine_name')
            : [];
        $categoryNames = $categoryIds
            ? Db::name('machine_fault_category')->whereIn('category_id', $categoryIds)
                ->order('sort asc,category_id asc')->column('category_name')
            : [];
        $codeNames = [];
        if ($errorCodes) {
            $codeRows = Db::name('machine_error_code_notice_rule')
                ->where('ao_id', $this->getFaultReceiverAoId())
                ->whereIn('error_code', $errorCodes)
                ->field('error_code,error_name')
                ->select()
                ->toArray();
            $codeNameMap = [];
            foreach ($codeRows as $codeRow) {
                $codeNameMap[strval($codeRow['error_code'])] = strval($codeRow['error_name']);
            }
            foreach ($errorCodes as $errorCode) {
                $codeNames[] = $codeNameMap[$errorCode] ?? $errorCode;
            }
        }

        $machineScope = intval($row['machine_scope'] ?? 1);
        $faultScope = intval($row['fault_scope'] ?? 2);
        $openid = trim(strval($row['openid'] ?? ''));
        $wechatBound = $openid !== '' && intval($row['wx_id'] ?? 0) > 0 ? 1 : 2;
        return [
            'receiver_id' => $receiverId,
            'manager_id' => intval($row['manager_id'] ?? 0),
            'account' => strval($row['account'] ?? ''),
            'nickname' => strval($row['nickname'] ?: ($row['real_name'] ?? '')),
            'mobile' => strval($row['mobile'] ?? ''),
            'mobile_masked' => $this->maskFaultReceiverMobile($row['mobile'] ?? ''),
            'organization_name' => strval($row['organization_name'] ?? ''),
            'account_status' => intval($row['account_status'] ?? 0),
            'wechat_bound' => $wechatBound,
            'wechat_bound_name' => $wechatBound === 1 ? '已绑定' : '未绑定',
            'wx_id' => intval($row['wx_id'] ?? 0),
            'wx_name' => strval($row['wx_name'] ?? ''),
            'wx_status' => intval($row['wx_status'] ?? 0),
            'receive_channel' => 'wechat',
            'receive_channel_name' => '微信公众号',
            'machine_scope' => $machineScope,
            'machine_ids' => $machineIds,
            'machine_names' => array_values(array_filter(array_map('strval', $machineNames))),
            'machine_scope_name' => $machineScope === 1
                ? '全部设备'
                : '指定设备（' . count($machineIds) . '台）',
            'fault_scope' => $faultScope,
            'category_ids' => $categoryIds,
            'category_names' => array_values(array_map('strval', $categoryNames)),
            'error_codes' => $errorCodes,
            'error_code_names' => $codeNames,
            'fault_scope_name' => $this->formatFaultReceiverFaultScopeName(
                $faultScope,
                count($categoryIds),
                count($errorCodes)
            ),
            'status' => intval($row['status'] ?? 1),
            'status_name' => intval($row['status'] ?? 1) === 1 ? '启用' : '停用',
            'update_time' => intval($row['update_time'] ?? 0),
            'update_time_text' => !empty($row['update_time'])
                ? date('Y-m-d H:i:s', intval($row['update_time']))
                : '',
        ];
    }

    protected function formatFaultReceiverFaultScopeName($faultScope, $categoryCount, $codeCount)
    {
        if (intval($faultScope) === 1) {
            return '全部故障';
        }
        if (intval($faultScope) === 2) {
            return '指定分类（' . intval($categoryCount) . '个）';
        }
        return '指定故障码（' . intval($codeCount) . '个）';
    }

    protected function maskFaultReceiverMobile($mobile)
    {
        $mobile = trim(strval($mobile));
        if (preg_match('/^(\d{3})\d{4}(\d{4})$/', $mobile, $matches)) {
            return $matches[1] . '****' . $matches[2];
        }
        return $mobile;
    }

    protected function getFaultReceiverAoId()
    {
        return intval($this->manager['ao_id'] ?? 0);
    }
}
