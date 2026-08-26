<?php

namespace app\AppFactory\Mobile\Machine;

use app\AppFactory\Kernel\BaseClient;
use app\AppFactory\Kernel\Traits\Inspection\InspectionStaffTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Util\SignUtil;
use app\AppFactory\Kernel\Util\TDESUtil;
use think\facade\Db;

class InspectionClient extends BaseClient
{
    use MachineTrait, InspectionStaffTrait;

    const QR_EXPIRES_IN = 120;
    const TOKEN_EXPIRES_IN = 86400;
    const TOKEN_TYPE = 'inspection';

    protected $staff = [];
    protected $machine = [];

    /**
     * 校验设备端生成的二维码，并使用巡检号登录。
     * 设备端签名字段：msg_id、machine_id、timestamp；staff_code 为扫码后输入，不参与签名。
     */
    public function login($postData)
    {
        $issuedAt = intval($postData['timestamp'] ?? 0);
        $machineId = trim(strval($postData['machine_id'] ?? ''));
        $msgId = trim(strval($postData['msg_id'] ?? ''));
        $staffCode = trim(strval($postData['staff_code'] ?? ''));
        $now = time();

        if ($machineId === '') {
            return $this->rValidate('设备ID不能为空');
        }
        if ($msgId === '') {
            return $this->rValidate('消息ID不能为空');
        }
        if (!preg_match('/^[1-9][0-9]{5}$/', $staffCode)) {
            return $this->rValidate('巡检账号必须为首位非0的6位数字');
        }
        if (trim(strval($postData['sign'] ?? '')) === '') {
            return $this->rValidate('签名不能为空');
        }
        if ($issuedAt <= 0 || $issuedAt > $now || $now - $issuedAt >= self::QR_EXPIRES_IN) {
            return $this->r(100, '巡检二维码已过期，请重新扫码');
        }

        $machine = $this->getMachineFind(
            ['machine_id' => $machineId],
            'm_id,machine_id,machine_name,ao_id,signKey'
        );
        if (!$machine) {
            return $this->r(100, '设备不存在');
        }
        if (is_object($machine) && method_exists($machine, 'toArray')) {
            $machine = $machine->toArray();
        }

        $scanData = [
            'msg_id' => $msgId,
            'machine_id' => $machineId,
            'timestamp' => $issuedAt,
            'sign' => trim(strval($postData['sign'] ?? '')),
        ];
        $signKey = cache($machineId . '.signKey');
        if (!$signKey) {
            $signKey = $machine['signKey'] ?? '';
        }
        if (!$signKey) {
            $signKey = env('api.md5Key');
        }
        if (SignUtil::checkSign($scanData, $signKey) !== true) {
            return $this->r(100, '巡检二维码验签失败，请重新扫码');
        }

        $staff = $this->getAvailableInspectionStaff(['staff_code' => $staffCode]);
        if (!$staff) {
            return $this->r(100, '巡检账号不存在、已禁用或已过期');
        }

        $tokenIssuedAt = time();
        $tokenExpiresAt = $tokenIssuedAt + self::TOKEN_EXPIRES_IN;
        $tokenData = [
            'token_type' => self::TOKEN_TYPE,
            'issued_at' => $tokenIssuedAt,
            'expires_at' => $tokenExpiresAt,
            'machine_id' => $machineId,
            'staff_id' => intval($staff['staff_id']),
            'staff_code' => strval($staff['staff_code']),
        ];
        $token = TDESUtil::encrypt(
            json_encode($tokenData, JSON_UNESCAPED_UNICODE),
            env('api.md5Key')
        );
        if (!$token) {
            return $this->r(100, '巡检登录失败，请重试');
        }

        return $this->r(200, '登录成功', [
            'token' => $token,
            'expires_in' => self::TOKEN_EXPIRES_IN,
            'expires_at' => $tokenExpiresAt,
            'machine' => [
                'machine_id' => $machineId,
                'machine_name' => $machine['machine_name'] ?? '',
            ],
            'staff' => [
                'staff_id' => intval($staff['staff_id']),
                'staff_code' => strval($staff['staff_code']),
                'account_name' => $staff['account_name'] ?? '',
            ],
        ]);
    }

    /**
     * 获取巡检清单。
     */
    public function getCheckListItems()
    {
        $auth = $this->authenticateInspectionToken();
        if ($auth !== true) {
            return $auth;
        }

        try {
            $items = Db::name('check_list_items')
                ->where(['is_active' => 1])
                ->field('id,parent_id,item_name,item_level,description,sort_order,is_active,updated_at')
                ->order('sort_order asc,id asc')
                ->select()
                ->toArray();

            return $this->r(200, 'SUCCESS', $this->mergeDefaultCheckListRoots(
                $this->buildCheckListTree($items)
            ));
        } catch (\Throwable $e) {
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 使用 Token 中绑定的设备和巡检人员提交检查记录。
     */
    public function submitCheckListRecord($postData)
    {
        $auth = $this->authenticateInspectionToken();
        if ($auth !== true) {
            return $auth;
        }

        $checkList = $postData['check_list'] ?? [];
        if (!is_array($checkList)) {
            $decoded = json_decode(strval($checkList), true);
            $checkList = is_array($decoded) ? $decoded : [];
        }
        if (!$checkList) {
            return $this->rValidate('check_list不能为空');
        }

        $transactionStarted = false;
        try {
            $enabledItems = Db::name('check_list_items')
                ->where(['is_active' => 1])
                ->field('id,item_name,item_level')
                ->order('sort_order asc,id asc')
                ->select()
                ->toArray();
            if (!$enabledItems) {
                return $this->rFail('暂无启用检查项');
            }

            $enabledMap = [];
            $requiredItemIds = [];
            foreach ($enabledItems as $item) {
                $itemId = intval($item['id']);
                $enabledMap[$itemId] = $item;
                if (intval($item['item_level'] ?? 0) !== 1) {
                    $requiredItemIds[] = $itemId;
                }
            }

            $submittedMap = [];
            $submittedIds = [];
            foreach ($checkList as $row) {
                if (!is_array($row)) {
                    return $this->rValidate('check_list格式错误');
                }
                $itemId = intval($row['item_id'] ?? ($row['id'] ?? 0));
                if ($itemId <= 0) {
                    return $this->rValidate('item_id不能为空');
                }
                $submittedIds[] = $itemId;
                if (!isset($enabledMap[$itemId])) {
                    continue;
                }
                // 一级节点仅用于分组，不生成巡检记录。
                if (intval($enabledMap[$itemId]['item_level'] ?? 0) === 1) {
                    continue;
                }

                $checkStatus = intval($row['check_status'] ?? 0);
                if (!in_array($checkStatus, [1, 2], true)) {
                    return $this->rValidate('check_status必须为1或2');
                }
                $submittedMap[$itemId] = [
                    'check_status' => $checkStatus,
                    'notes' => trim(strval($row['notes'] ?? '')),
                ];
            }

            $submittedIds = array_values(array_unique($submittedIds));
            $notExists = array_values(array_diff($submittedIds, array_keys($enabledMap)));
            if ($notExists) {
                return $this->rFail('检查项不存在或已禁用:' . implode(',', $notExists));
            }
            if (!$submittedMap) {
                return $this->rValidate('没有可提交的巡检项');
            }

            $missingIds = array_values(array_diff($requiredItemIds, array_keys($submittedMap)));
            if ($missingIds) {
                $missingItems = [];
                foreach ($missingIds as $itemId) {
                    $missingItems[] = [
                        'item_id' => $itemId,
                        'item_name' => $enabledMap[$itemId]['item_name'] ?? '',
                        'reason' => '未提交check_status',
                    ];
                }
                return $this->r(300, '存在未提交check_status的信息', [
                    'missing_check_status_items' => $missingItems,
                ]);
            }

            $recordsCode = date('YmdHi');
            $checkTime = date('Y-m-d H:i:s');
            $commonNotes = trim(strval($postData['notes'] ?? ''));
            $insertAll = [];
            foreach ($submittedMap as $itemId => $row) {
                $insertAll[] = [
                    'records_code' => $recordsCode,
                    'item_id' => intval($itemId),
                    'machine_id' => $this->machine['machine_id'],
                    'manager_id' => intval($this->staff['staff_id']),
                    'check_status' => intval($row['check_status']),
                    'check_time' => $checkTime,
                    'notes' => $row['notes'] !== '' ? $row['notes'] : $commonNotes,
                ];
            }

            Db::startTrans();
            $transactionStarted = true;
            $result = Db::name('check_list_records')->insertAll($insertAll);
            if (!$result) {
                Db::rollback();
                $transactionStarted = false;
                return $this->rFail('检查记录提交失败');
            }
            Db::commit();
            $transactionStarted = false;

            return $this->r(200, 'SUCCESS', [
                'records_code' => $recordsCode,
                'machine_id' => $this->machine['machine_id'],
                'staff_id' => intval($this->staff['staff_id']),
                'staff_code' => strval($this->staff['staff_code']),
                'count' => count($insertAll),
            ]);
        } catch (\Throwable $e) {
            if ($transactionStarted) {
                Db::rollback();
            }
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    protected function authenticateInspectionToken()
    {
        $token = request()->header('token');
        if (!$token) {
            $token = input('token');
        }
        if (!$token) {
            return $this->r(100, '令牌不能为空，请重新登录');
        }
        if (!is_string($token) || !preg_match('/^[a-f0-9]+$/i', $token) || strlen($token) % 2 !== 0) {
            return $this->r(100, '错误令牌，无法解析');
        }

        $tokenArr = json_decode(TDESUtil::decrypt($token, env('api.md5Key')), true);
        if (!$tokenArr || ($tokenArr['token_type'] ?? '') !== self::TOKEN_TYPE) {
            return $this->r(100, '巡检Token类型错误，请重新扫码登录');
        }

        $issuedAt = intval($tokenArr['issued_at'] ?? 0);
        $expiresAt = intval($tokenArr['expires_at'] ?? 0);
        $now = time();
        if (
            $issuedAt <= 0
            || $expiresAt !== $issuedAt + self::TOKEN_EXPIRES_IN
            || $issuedAt > $now
            || $now >= $expiresAt
        ) {
            return $this->r(100, '登录超时，请重新扫码登录');
        }
        $this->tokenArr = $tokenArr;

        $machineId = trim(strval($this->tokenArr['machine_id'] ?? ''));
        $staffId = intval($this->tokenArr['staff_id'] ?? 0);
        $staffCode = trim(strval($this->tokenArr['staff_code'] ?? ''));
        if ($machineId === '' || $staffId <= 0 || $staffCode === '') {
            return $this->r(100, '巡检Token信息不完整，请重新扫码登录');
        }

        $machine = $this->getMachineFind(
            ['machine_id' => $machineId],
            'm_id,machine_id,machine_name,ao_id'
        );
        if (!$machine) {
            return $this->r(100, '设备不存在');
        }
        if (is_object($machine) && method_exists($machine, 'toArray')) {
            $machine = $machine->toArray();
        }

        $staff = $this->getAvailableInspectionStaff(['staff_id' => $staffId]);
        if (!$staff || strval($staff['staff_code'] ?? '') !== $staffCode) {
            return $this->r(100, '巡检账号不存在、已禁用或已过期，请重新登录');
        }

        $this->machine = $machine;
        $this->staff = $staff;
        return true;
    }

    protected function getAvailableInspectionStaff($where)
    {
        $staff = $this->getInspectionStaffFind(
            $where,
            'staff_id,staff_code,account_name,mobile,expire_time,ao_id,status'
        );
        if (!$staff) {
            return [];
        }
        if (is_object($staff) && method_exists($staff, 'toArray')) {
            $staff = $staff->toArray();
        }

        $expireTime = intval($staff['expire_time'] ?? 0);
        if (intval($staff['status'] ?? 0) !== 1 || ($expireTime > 0 && $expireTime <= time())) {
            return [];
        }
        return $staff;
    }

    protected function buildCheckListTree($items)
    {
        $nodes = [];
        foreach ($items as $item) {
            $item['children'] = [];
            $nodes[intval($item['id'])] = $item;
        }

        $tree = [];
        foreach ($nodes as $id => $node) {
            $parentId = intval($node['parent_id'] ?? 0);
            if ($parentId > 0 && isset($nodes[$parentId])) {
                $nodes[$parentId]['children'][] = &$nodes[$id];
            } else {
                $tree[] = &$nodes[$id];
            }
        }
        unset($node);

        return $tree;
    }

    protected function mergeDefaultCheckListRoots($tree)
    {
        $defaultNames = ['基础状态', '商品陈列', '核心功能'];
        $rootByName = [];
        $otherRoots = [];
        foreach ($tree as $node) {
            $name = trim(strval($node['item_name'] ?? ''));
            if (in_array($name, $defaultNames, true)) {
                $rootByName[$name] = $node;
            } else {
                $otherRoots[] = $node;
            }
        }

        $result = [];
        foreach ($defaultNames as $index => $name) {
            $result[] = $rootByName[$name] ?? [
                'id' => 0,
                'parent_id' => null,
                'item_name' => $name,
                'item_level' => 1,
                'description' => '',
                'sort_order' => $index + 1,
                'is_active' => 1,
                'updated_at' => '',
                'children' => [],
            ];
        }

        return array_merge($result, $otherRoots);
    }
}
