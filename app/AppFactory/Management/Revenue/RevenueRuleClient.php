<?php

namespace app\AppFactory\Management\Revenue;

use app\AppFactory\Kernel\Service\Revenue\RevenueCouponService;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Model\Goods\GoodsModel;
use app\AppFactory\Kernel\Model\Machine\MachineGoodsModel;
use app\AppFactory\Kernel\Model\Machine\MachineModel;
use app\AppFactory\Kernel\Model\Revenue\RevenueRuleConfigModel;
use app\AppFactory\Kernel\Model\Revenue\RevenueRuleConfigScopeModel;
use app\AppFactory\Kernel\Traits\Revenue\RevenueAccountTrait;
use app\AppFactory\Kernel\Traits\Revenue\RevenueRuleTrait;
use app\AppFactory\Management\ManagementClient;

class RevenueRuleClient extends ManagementClient
{
    use RevenueRuleTrait;
    use RevenueAccountTrait;
    use MachineTrait;
    use RevenueOrganizationNameTrait;

    public function saveConfig($postData)
    {
        return $this->getConfigIdFromData($postData) > 0
            ? $this->updateData($postData)
            : $this->addData($postData);
    }

    public function saveScope($postData)
    {
        $rrcfgId = $this->getConfigIdFromData($postData);
        if ($rrcfgId <= 0) return $this->rFail("分账配置ID不能为空");
        if (!$this->getRevenueRuleConfigFind(['rrcfg_id' => $rrcfgId], 'rrcfg_id')) return $this->rFail("分账配置不存在");
        if (!isset($postData['scopes'])) return $this->rFail("生效范围不能为空");
        $this->startTrans();
        try {
            $this->replaceConfigScopes($rrcfgId, $postData['scopes']);
            $this->commitTrans();
            return $this->rU(true);
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    public function addData($postData)
    {
        unset($postData['payer_ao_id']);
        $check = $this->checkConfigData($postData);
        if ($check !== true) return $check;
        $this->startTrans();
        try {
            $config = $this->buildConfigSaveData($postData, false);
            $rrcfgId = $this->addRevenueRuleConfig($config);
            if (isset($postData['scopes'])) {
                $this->replaceConfigScopes($rrcfgId, $postData['scopes']);
            }
            $this->commitTrans();
            return $this->rA(['rrcfg_id' => $rrcfgId, 'rr_id' => $rrcfgId]);
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    public function updateData($postData)
    {
        unset($postData['payer_ao_id']);
        $rrcfgId = $this->getConfigIdFromData($postData);
        if ($rrcfgId <= 0) return $this->rFail("分账配置ID不能为空");
        $postData['rrcfg_id'] = $rrcfgId;
        $check = $this->checkConfigData($postData, true);
        if ($check !== true) return $check;
        $update = $this->buildConfigSaveData($postData, true);
        if (!$update) return $this->rFail("没有可更新的分账策略字段");
        $this->startTrans();
        try {
            $result = $this->updateRevenueRuleConfig($update, ['rrcfg_id' => $rrcfgId]);
            if (isset($postData['scopes'])) {
                $this->replaceConfigScopes($rrcfgId, $postData['scopes']);
            }
            $this->commitTrans();
            return $this->rU($result);
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    public function getList($where= [], $pageNum = 0, $field = "*", $order = "rr_id desc",$rQ = 1)
    {
        $where = $this->normalizeConfigWhere($where);
        $order = str_replace('rr_id', 'rrcfg_id', $order);
        return $this->rQ($this->appendRevenueOrganizationNames(
            $this->appendConfigScopeNums(
                $this->formatConfigRows($this->getRevenueRuleConfigList($where, $pageNum, "*", $order))
            )
        ));
    }

    public function getFind($where = [], $field = "*", $order = "rr_id desc",$rQ = 1)
    {
        $where = $this->normalizeConfigWhere($where);
        $order = str_replace('rr_id', 'rrcfg_id', $order);
        return $this->rQ($this->appendRevenueOrganizationNames(
            $this->formatConfigRows($this->getRevenueRuleConfigFind($where, "*", $order))
        ));
    }

    public function addItem($postData)
    {
        $check = $this->checkConfigItemData($postData);
        if ($check !== true) return $check;
        $rrcfgId = $this->getConfigIdFromData($postData);
        $items = $this->getConfigItems($rrcfgId);
        $itemKey = $this->nextConfigItemKey($items);
        $postData['item_key'] = $itemKey;
        $postData['rri_id'] = $itemKey;
        $items[] = $this->normalizeConfigItem($postData);
        $this->saveConfigItems($rrcfgId, $items);
        return $this->rA(['rri_id' => $itemKey, 'item_key' => $itemKey]);
    }

    public function addProductItem($postData)
    {
        return $this->addItem($postData);
    }

    public function saveCouponConfig($postData)
    {
        $rrcfgId = intval($postData['rrc_id'] ?? ($postData['rrcfg_id'] ?? ($postData['rr_id'] ?? 0)));
        if ($rrcfgId <= 0) return $this->rFail("优惠券分账配置ID不能为空");
        $postData['rrcfg_id'] = $rrcfgId;
        $postData['rule_mode'] = 5;
        $check = $this->checkConfigData($postData, true);
        if ($check !== true) return $check;
        $this->startTrans();
        try {
            $update = $this->buildConfigSaveData($postData, true);
            $this->updateRevenueRuleConfig($update, ['rrcfg_id' => $rrcfgId]);
            if (isset($postData['scopes'])) {
                $this->replaceConfigScopes($rrcfgId, $postData['scopes']);
            }
            $this->commitTrans();
            return $this->rA(['rrc_id' => $rrcfgId, 'rrcfg_id' => $rrcfgId]);
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    public function getCouponConfig($postData)
    {
        $rrId = intval($postData['rr_id'] ?? 0);
        $rrcId = intval($postData['rrc_id'] ?? 0);
        if ($rrId <= 0 && $rrcId <= 0) return $this->rFail("分账策略ID或优惠券配置ID不能为空");
        $where = ['rule_mode' => 5];
        if ($rrcId > 0) {
            $where['rrcfg_id'] = $rrcId;
            $coupon = $this->formatConfigRows($this->getRevenueRuleConfigFind($where));
            if (!$coupon) return $this->rNoData();
            $coupon['scopes'] = $this->getConfigScopes(intval($coupon['rrcfg_id']));
            return $this->rQ($coupon);
        }
        $where['rrcfg_id'] = $rrId;
        $coupon = $this->formatConfigRows($this->getRevenueRuleConfigFind($where));
        if (!$coupon) return $this->rNoData();
        $coupon['scopes'] = $this->getConfigScopes(intval($coupon['rrcfg_id']));
        return $this->rQ([$coupon]);
    }

    public function updateItem($postData)
    {
        if (empty($postData['rri_id'])) return $this->rFail("分账策略明细ID不能为空");
        $check = $this->checkConfigItemData($postData, true);
        if ($check !== true) return $check;
        $rrcfgId = $this->getConfigIdFromData($postData);
        $itemKey = intval($postData['rri_id']);
        $items = $this->getConfigItems($rrcfgId);
        $updated = false;
        foreach ($items as &$item) {
            if (intval($item['item_key'] ?? $item['rri_id'] ?? 0) !== $itemKey) continue;
            $item = $this->normalizeConfigItem(array_merge($item, $postData));
            $updated = true;
            break;
        }
        unset($item);
        if (!$updated) return $this->rFail("分账策略明细不存在");
        $this->saveConfigItems($rrcfgId, $items);
        return $this->rU(true);
    }

    public function getItemList($where, $pageNum = 0, $field = "*", $order = "sort asc,rri_id asc")
    {
        $rrcfgId = $this->extractWhereValue($where, ['rrcfg_id', 'rr_id']);
        if ($rrcfgId <= 0) return $this->rFail("分账配置ID不能为空");
        return $this->rQ($this->appendRevenueOrganizationNames($this->getConfigItems($rrcfgId)));
    }

    public function delItem($rriId)
    {
        $rrcfgId = intval(input('rrcfg_id') ?: input('rr_id'));
        if ($rrcfgId <= 0) return $this->rFail("分账配置ID不能为空");
        $items = array_values(array_filter($this->getConfigItems($rrcfgId), function ($item) use ($rriId) {
            return intval($item['item_key'] ?? $item['rri_id'] ?? 0) !== intval($rriId);
        }));
        $this->saveConfigItems($rrcfgId, $items);
        return $this->rD(true);
    }

    public function addTier($postData)
    {
        $rrcfgId = intval($postData['rrcfg_id'] ?? ($postData['rr_id'] ?? 0));
        if ($rrcfgId <= 0) return $this->rFail("分账配置ID不能为空");
        $itemKey = intval($postData['rri_id'] ?? ($postData['item_key'] ?? 0));
        if ($itemKey <= 0) return $this->rFail("分账策略明细ID不能为空");
        $items = $this->getConfigItems($rrcfgId);
        foreach ($items as &$item) {
            if (intval($item['item_key'] ?? $item['rri_id'] ?? 0) !== $itemKey) continue;
            $tiers = $item['tiers'] ?? [];
            $tierKey = $this->nextConfigTierKey($tiers);
            $postData['tier_key'] = $tierKey;
            $postData['rrit_id'] = $tierKey;
            $tiers[] = $this->normalizeConfigTier($postData);
            $item['tiers'] = $tiers;
            $this->saveConfigItems($rrcfgId, $items);
            return $this->rA(['rrit_id' => $tierKey, 'tier_key' => $tierKey]);
        }
        unset($item);
        return $this->rFail("分账策略明细不存在");
    }

    public function updateTier($postData)
    {
        if (empty($postData['rrit_id'])) return $this->rFail("阶梯分账明细ID不能为空");
        $rrcfgId = intval($postData['rrcfg_id'] ?? ($postData['rr_id'] ?? 0));
        if ($rrcfgId <= 0) return $this->rFail("分账配置ID不能为空");
        $itemKey = intval($postData['rri_id'] ?? ($postData['item_key'] ?? 0));
        if ($itemKey <= 0) return $this->rFail("分账策略明细ID不能为空");
        $tierKey = intval($postData['rrit_id']);
        $items = $this->getConfigItems($rrcfgId);
        foreach ($items as &$item) {
            if (intval($item['item_key'] ?? $item['rri_id'] ?? 0) !== $itemKey) continue;
            $tiers = $item['tiers'] ?? [];
            foreach ($tiers as &$tier) {
                if (intval($tier['tier_key'] ?? $tier['rrit_id'] ?? 0) !== $tierKey) continue;
                $tier = $this->normalizeConfigTier(array_merge($tier, $postData));
                $item['tiers'] = $tiers;
                $this->saveConfigItems($rrcfgId, $items);
                return $this->rU(true);
            }
            unset($tier);
        }
        unset($item);
        return $this->rFail("阶梯分账明细不存在");
    }

    public function getTierList($where, $pageNum = 0, $field = "*", $order = "threshold_min asc,rrit_id asc")
    {
        $rrcfgId = $this->extractWhereValue($where, ['rrcfg_id', 'rr_id']);
        $itemKey = $this->extractWhereValue($where, ['rri_id', 'item_key']);
        if ($rrcfgId <= 0 || $itemKey <= 0) return $this->rFail("分账配置ID和明细ID不能为空");
        foreach ($this->getConfigItems($rrcfgId) as $item) {
            if (intval($item['item_key'] ?? $item['rri_id'] ?? 0) === $itemKey) {
                return $this->rQ($item['tiers'] ?? []);
            }
        }
        return $this->rNoData();
    }

    public function delTier($rritId)
    {
        $rrcfgId = intval(input('rrcfg_id') ?: input('rr_id'));
        $itemKey = intval(input('rri_id') ?: input('item_key'));
        if ($rrcfgId <= 0 || $itemKey <= 0) return $this->rFail("分账配置ID和明细ID不能为空");
        $items = $this->getConfigItems($rrcfgId);
        foreach ($items as &$item) {
            if (intval($item['item_key'] ?? $item['rri_id'] ?? 0) !== $itemKey) continue;
            $item['tiers'] = array_values(array_filter($item['tiers'] ?? [], function ($tier) use ($rritId) {
                return intval($tier['tier_key'] ?? $tier['rrit_id'] ?? 0) !== intval($rritId);
            }));
            $this->saveConfigItems($rrcfgId, $items);
            return $this->rD(true);
        }
        unset($item);
        return $this->rFail("阶梯分账明细不存在");
    }

    public function bindMachine($postData)
    {
        $rrcfgId = $this->getConfigIdFromData($postData);
        if ($rrcfgId <= 0) return $this->rFail("分账配置ID不能为空");
        $machineIds = $this->normalizeMachineIds($postData['m_ids'] ?? ($postData['m_id'] ?? []));
        if (!$machineIds) return $this->rFail("设备ID列表不能为空");
        $status = intval($postData['status'] ?? 1);
        if (!in_array($status, [1, 2], true)) return $this->rFail("设备分账策略绑定状态不合法");
        $rule = $this->getRevenueRuleConfigFind(['rrcfg_id' => $rrcfgId, 'status' => 1], 'rrcfg_id,rule_mode');
        if (!$rule) return $this->rFail("分账策略不存在或未启用");

        $this->startTrans();
        try {
            $machineMap = MachineModel::where('m_id', 'in', $machineIds)
                ->lock(true)
                ->column('ao_id', 'm_id');
            $validMachineIds = array_map('intval', array_keys($machineMap));
            $missingMachineIds = array_values(array_diff($machineIds, $validMachineIds));
            if ($missingMachineIds) {
                throw new \Exception("设备不存在：" . implode(',', $missingMachineIds));
            }

            $existsMachineIds = RevenueRuleConfigScopeModel::where('rrcfg_id', $rrcfgId)
                ->where('m_id', 'in', $machineIds)
                ->where(['g_id' => 0, 'mg_id' => 0])
                ->column('m_id');
            if ($existsMachineIds) {
                throw new \Exception("设备已绑定当前分账策略：" . implode(',', array_unique($existsMachineIds)));
            }

            if ($status === 1) {
                $modeExistsMachineIds = RevenueRuleConfigScopeModel::alias('rrcs')
                    ->join('revenue_rule_config rrc', 'rrc.rrcfg_id = rrcs.rrcfg_id')
                    ->where('rrcs.m_id', 'in', $machineIds)
                    ->where([
                        'rrcs.status' => 1,
                        'rrcs.g_id' => 0,
                        'rrcs.mg_id' => 0,
                        'rrc.status' => 1,
                        'rrc.rule_mode' => intval($rule['rule_mode']),
                    ])
                    ->column('rrcs.m_id');
                if ($modeExistsMachineIds) {
                    throw new \Exception("设备已绑定同类型启用分账策略：" . implode(',', array_unique($modeExistsMachineIds)));
                }
            }

            $rrmIds = [];
            foreach ($machineIds as $machineId) {
                $machine = MachineModel::getFind(['m_id' => $machineId], 'm_id,machine_id,ao_id');
                $rrmIds[] = $this->addRevenueRuleConfigScope([
                    'rrcfg_id' => $rrcfgId,
                    'm_id' => $machineId,
                    'ao_id' => $machineMap[$machineId],
                    'machine_id' => $machine['machine_id'] ?? '',
                    'g_id' => 0,
                    'mg_id' => 0,
                    'sort' => max(0, intval($postData['sort'] ?? 0)),
                    'status' => $status,
                ]);
            }
            $this->commitTrans();
            return $this->rA(['rrm_ids' => $rrmIds, 'm_ids' => $machineIds]);
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    public function getMachineList($where, $pageNum = 0, $field = "*", $order = "rrm_id desc")
    {
        $where = $this->normalizeScopeWhere($where);
        $rows = $this->getRevenueRuleConfigScopeList($where, $pageNum, "*", str_replace('rrm_id', 'rrcs_id', $order));
        return $this->rQ($this->appendRevenueOrganizationNames($this->formatScopeRows($rows)));
    }

    public function getBoundMachineList($postData)
    {
        $rrcfgId = $this->getConfigIdFromData($postData);
        if ($rrcfgId <= 0) return $this->rFail("分账配置ID不能为空");
        if (!$this->getRevenueRuleConfigFind(['rrcfg_id' => $rrcfgId], 'rrcfg_id')) {
            return $this->rFail("分账策略不存在");
        }

        $query = RevenueRuleConfigScopeModel::alias('rrcs')
            ->join('machine m', 'm.m_id = rrcs.m_id')
            ->where('rrcs.rrcfg_id', $rrcfgId)
            ->where(['rrcs.g_id' => 0, 'rrcs.mg_id' => 0]);
        if (isset($postData['status']) && $postData['status'] !== '') {
            $query->where('rrcs.status', intval($postData['status']));
        }
        if (!empty($postData['m_id'])) {
            $query->where('rrcs.m_id', intval($postData['m_id']));
        }
        if (!empty($postData['machine_id'])) {
            $query->whereLike('m.machine_id', '%' . trim($postData['machine_id']) . '%');
        }
        if (!empty($postData['machine_name'])) {
            $query->whereLike('m.machine_name', '%' . trim($postData['machine_name']) . '%');
        }
        $query->field(
            'rrcs.rrcs_id rrm_id,rrcs.rrcfg_id rr_id,rrcs.rrcfg_id,rrcs.m_id,rrcs.ao_id,rrcs.sort,rrcs.status,'
            . 'rrcs.create_time,rrcs.update_time,m.machine_id,m.machine_name'
        )->order('rrcs.sort asc,rrcs.rrcs_id desc');

        $pageNum = intval($postData['pageNum'] ?? 0);
        $result = $pageNum > 0
            ? $query->paginate($pageNum, false, ['query' => request()->param()])
            : $query->select();
        return $this->rQ($this->appendRevenueOrganizationNames($result));
    }

    public function unbindMachine($rrmId)
    {
        return $this->rD($this->delRevenueRuleConfigScope(['rrcs_id' => $rrmId]));
    }

    protected function getConfigIdFromData(array $data)
    {
        return intval($data['rrcfg_id'] ?? ($data['rr_id'] ?? 0));
    }

    protected function normalizeConfigWhere($where)
    {
        if (!is_array($where)) return $where;
        foreach ($where as &$condition) {
            if (!is_array($condition) || count($condition) < 1) continue;
            if ($condition[0] === 'rr_id') $condition[0] = 'rrcfg_id';
            if ($condition[0] === 'rule_name') $condition[0] = 'config_name';
        }
        unset($condition);
        return $where;
    }

    protected function normalizeScopeWhere($where)
    {
        if (!is_array($where)) return $where;
        foreach ($where as &$condition) {
            if (!is_array($condition) || count($condition) < 1) continue;
            if ($condition[0] === 'rr_id') $condition[0] = 'rrcfg_id';
            if ($condition[0] === 'rrm_id') $condition[0] = 'rrcs_id';
        }
        unset($condition);
        return $where;
    }

    protected function checkConfigData(&$data, $isUpdate = false)
    {
        if (!$isUpdate || isset($data['rule_name']) || isset($data['config_name'])) {
            $name = trim($data['config_name'] ?? ($data['rule_name'] ?? ''));
            if ($name === '') return $this->rFail("分账配置名称不能为空");
            $data['config_name'] = $name;
        }
        if (!$isUpdate || isset($data['rule_mode'])) {
            if (empty($data['rule_mode']) || !in_array(intval($data['rule_mode']), [1, 2, 3, 4, 5], true)) {
                return $this->rFail("分账模式不合法");
            }
        }
        $current = [];
        if ($isUpdate) {
            $current = $this->getRevenueRuleConfigFind(['rrcfg_id' => $this->getConfigIdFromData($data)]);
            if (!$current) return $this->rFail("分账配置不存在");
            if (!is_array($current)) $current = $current->toArray();
        }
        $merged = array_merge($current, $data);
        foreach ([
            'base_type' => [[1, 2], '分账基数类型不合法'],
            'turnover_type' => [[1, 2], '阶梯营业额口径不合法'],
            'tier_calc_mode' => [[1, 2], '阶梯计算模式不合法'],
            'settlement_type' => [[1, 2], '分账时间类型不合法'],
        ] as $field => $rule) {
            if (!in_array(intval($merged[$field] ?? 1), $rule[0], true)) return $this->rFail($rule[1]);
        }
        if (intval($merged['settlement_days'] ?? 0) < 0) return $this->rFail("T+N 天数不能小于0");
        if (intval($merged['settlement_type'] ?? 1) === 2 && intval($merged['settlement_days'] ?? 0) < 1) {
            return $this->rFail("T+N 分账天数必须大于0");
        }
        if (intval($merged['rule_mode'] ?? 0) === 5) {
            $couponCheck = $this->normalizeConfigCouponData($data, $merged, $isUpdate);
            if ($couponCheck !== true) return $couponCheck;
        }
        return true;
    }

    protected function normalizeConfigCouponData(&$data, array $merged, $isUpdate = false)
    {
        $couponCode = trim($merged['coupon_code'] ?? '');
        if ($couponCode === '') {
            try {
                $couponCode = RevenueCouponService::generateUniqueCouponCode();
            } catch (\Exception $e) {
                return $this->rFail($e->getMessage());
            }
        }
        if (!preg_match('/^[1-9][0-9]{5}$/', $couponCode)) return $this->rFail("优惠券编码必须为非0开头的6位数字");
        if (!RevenueCouponService::isCouponCodeUnique($couponCode, $isUpdate ? $this->getConfigIdFromData($merged) : 0)) {
            return $this->rFail("优惠券编码已存在或已被活动优惠券使用");
        }
        $useLimit = intval($merged['use_limit'] ?? 0);
        if (!$isUpdate && $useLimit <= 0) return $this->rFail("优惠券使用次数必须大于0");
        if ($useLimit < intval($merged['used_count'] ?? 0)) return $this->rFail("优惠券使用次数不能小于已使用次数");
        $discountType = intval($merged['discount_type'] ?? 0);
        if (!in_array($discountType, [0, 1, 2], true)) return $this->rFail("优惠方式不合法");
        $discountValue = floatval($merged['discount_value'] ?? 0);
        if ($discountValue < 0) return $this->rFail("优惠金额或比例不能小于0");
        if ($discountType > 0 && $discountValue <= 0) return $this->rFail("优惠金额或比例必须大于0");
        if ($discountType === 2 && $discountValue > 100) return $this->rFail("优惠比例不能超过100%");
        $expireTime = intval($merged['expire_time'] ?? 0);
        if ($expireTime > 0 && $expireTime <= time()) return $this->rFail("优惠券过期时间必须大于当前时间");
        $data['coupon_code'] = $couponCode;
        $data['discount_type'] = $discountType;
        $data['discount_value'] = $discountType === 0 ? 0 : $discountValue;
        return true;
    }

    protected function buildConfigSaveData(array $data, $isUpdate = false)
    {
        $save = [];
        foreach ([
            'config_name', 'rule_mode', 'base_type', 'turnover_type', 'tier_calc_mode',
            'settlement_type', 'settlement_days', 'coupon_code', 'discount_type',
            'discount_value', 'use_limit', 'expire_time', 'receiver_config', 'status',
        ] as $field) {
            if (array_key_exists($field, $data)) $save[$field] = $data[$field];
        }
        if (isset($data['rule_name']) && !isset($save['config_name'])) $save['config_name'] = $data['rule_name'];
        if (isset($data['receivers']) && !isset($save['receiver_config'])) $save['receiver_config'] = $data['receivers'];
        if (isset($save['receiver_config'])) $save['receiver_config'] = $this->encodeReceiverConfig($save['receiver_config']);
        if (!$isUpdate) {
            foreach (['base_type' => 1, 'turnover_type' => 1, 'tier_calc_mode' => 1, 'settlement_type' => 1, 'settlement_days' => 0, 'status' => 1] as $field => $value) {
                if (!isset($save[$field])) $save[$field] = $value;
            }
            if (!isset($save['receiver_config'])) $save['receiver_config'] = '[]';
            if (intval($save['rule_mode'] ?? 0) === 5) {
                $save['used_count'] = 0;
                $save['remain_count'] = intval($save['use_limit'] ?? 0);
            }
        } elseif (isset($save['use_limit'])) {
            $current = $this->getRevenueRuleConfigFind(['rrcfg_id' => $this->getConfigIdFromData($data)], 'used_count');
            $usedCount = $current ? intval($current['used_count'] ?? 0) : 0;
            $save['remain_count'] = intval($save['use_limit']) - $usedCount;
        }
        if (isset($save['settlement_type']) && intval($save['settlement_type']) === 1) $save['settlement_days'] = 0;
        return $save;
    }

    protected function encodeReceiverConfig($config)
    {
        if (is_string($config)) {
            $decoded = json_decode($config, true);
            $config = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($config)) $config = [];
        $items = [];
        foreach ($config as $item) {
            if (is_array($item)) $items[] = $this->normalizeConfigItem($item);
        }
        return json_encode($items, JSON_UNESCAPED_UNICODE);
    }

    protected function getConfigItems($rrcfgId)
    {
        $config = $this->getRevenueRuleConfigFind(['rrcfg_id' => intval($rrcfgId)], 'receiver_config');
        if (!$config) return [];
        $receiverConfig = $config['receiver_config'] ?? '';
        $items = is_string($receiverConfig) ? json_decode($receiverConfig, true) : $receiverConfig;
        if (!is_array($items)) return [];
        return array_values(array_map([$this, 'normalizeConfigItem'], $items));
    }

    protected function saveConfigItems($rrcfgId, array $items)
    {
        return $this->updateRevenueRuleConfig([
            'receiver_config' => json_encode(array_values($items), JSON_UNESCAPED_UNICODE),
        ], ['rrcfg_id' => intval($rrcfgId)]);
    }

    protected function normalizeConfigItem(array $item)
    {
        $itemKey = intval($item['item_key'] ?? ($item['rri_id'] ?? 0));
        $item['item_key'] = $itemKey;
        $item['rri_id'] = $itemKey;
        $item['g_id'] = intval($item['g_id'] ?? 0);
        $item['mg_id'] = intval($item['mg_id'] ?? 0);
        $item['receiver_ao_id'] = intval($item['receiver_ao_id'] ?? 0);
        $item['ra_id'] = intval($item['ra_id'] ?? 0);
        $item['manager_id'] = intval($item['manager_id'] ?? 0);
        $item['calc_type'] = intval($item['calc_type'] ?? 0);
        $item['calc_value'] = $item['calc_value'] ?? 0;
        $item['sort'] = intval($item['sort'] ?? 0);
        $item['status'] = intval($item['status'] ?? 1) ?: 1;
        $tiers = $item['tiers'] ?? [];
        $item['tiers'] = is_array($tiers) ? array_values(array_map([$this, 'normalizeConfigTier'], $tiers)) : [];
        return $item;
    }

    protected function normalizeConfigTier(array $tier)
    {
        $tierKey = intval($tier['tier_key'] ?? ($tier['rrit_id'] ?? 0));
        $tier['tier_key'] = $tierKey;
        $tier['rrit_id'] = $tierKey;
        $tier['threshold_min'] = $tier['threshold_min'] ?? 0;
        $tier['threshold_max'] = $tier['threshold_max'] ?? null;
        $tier['calc_value'] = $tier['calc_value'] ?? 0;
        $tier['status'] = intval($tier['status'] ?? 1) ?: 1;
        return $tier;
    }

    protected function nextConfigItemKey(array $items)
    {
        $max = 0;
        foreach ($items as $item) $max = max($max, intval($item['item_key'] ?? $item['rri_id'] ?? 0));
        return $max + 1;
    }

    protected function nextConfigTierKey(array $tiers)
    {
        $max = 0;
        foreach ($tiers as $tier) $max = max($max, intval($tier['tier_key'] ?? $tier['rrit_id'] ?? 0));
        return $max + 1;
    }

    protected function checkConfigItemData(&$data, $isUpdate = false)
    {
        $rrcfgId = $this->getConfigIdFromData($data);
        if ($rrcfgId <= 0) return $this->rFail("分账配置ID不能为空");
        $config = $this->getRevenueRuleConfigFind(['rrcfg_id' => $rrcfgId], 'rrcfg_id,rule_mode');
        if (!$config) return $this->rFail("分账配置不存在");
        if (!$isUpdate || isset($data['receiver_ao_id'])) {
            if (empty($data['receiver_ao_id'])) return $this->rFail("分账接收组织不能为空");
        }
        if (!$isUpdate || isset($data['ra_id'])) {
            if (empty($data['ra_id'])) return $this->rFail("分账账户不能为空");
        }
        if (!empty($data['ra_id'])) {
            $account = $this->getRevenueAccountFind(['ra_id' => intval($data['ra_id']), 'status' => 1]);
            if (!$account) return $this->rFail("分账账户不存在或未启用");
            if (!isset($data['manager_id']) || !$data['manager_id']) $data['manager_id'] = $account['manager_id'];
        }
        if (!$isUpdate || isset($data['calc_type'])) {
            if (empty($data['calc_type']) || !in_array(intval($data['calc_type']), [1, 2, 3, 4], true)) return $this->rFail("分账计算方式不合法");
        }
        if (isset($data['calc_value']) && floatval($data['calc_value']) < 0) return $this->rFail("分账比例或金额不能小于0");
        return true;
    }

    protected function replaceConfigScopes($rrcfgId, $scopes)
    {
        $scopes = $this->normalizeConfigScopes($scopes);
        foreach ($scopes as $scope) {
            $check = $this->checkConfigScope($scope);
            if ($check !== true) {
                throw new \Exception(is_array($check) ? json_encode($check, JSON_UNESCAPED_UNICODE) : strval($check));
            }
        }
        $this->delRevenueRuleConfigScope(['rrcfg_id' => intval($rrcfgId)]);
        foreach ($scopes as $scope) {
            $scope['rrcfg_id'] = intval($rrcfgId);
            $this->addRevenueRuleConfigScope($scope);
        }
    }

    protected function normalizeConfigScopes($scopes)
    {
        if (is_string($scopes)) {
            $decoded = json_decode($scopes, true);
            $scopes = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($scopes)) return [];
        $result = [];
        foreach ($scopes as $scope) {
            if (!is_array($scope)) continue;
            $mId = intval($scope['m_id'] ?? 0);
            $machine = $mId > 0 ? MachineModel::getFind(['m_id' => $mId], 'm_id,machine_id,ao_id') : [];
            $result[] = [
                'm_id' => $mId,
                'machine_id' => trim($scope['machine_id'] ?? ($machine['machine_id'] ?? '')),
                'ao_id' => intval($scope['ao_id'] ?? ($machine['ao_id'] ?? 0)),
                'g_id' => intval($scope['g_id'] ?? 0),
                'mg_id' => intval($scope['mg_id'] ?? 0),
                'sort' => max(0, intval($scope['sort'] ?? 0)),
                'status' => intval($scope['status'] ?? 1) ?: 1,
            ];
        }
        return $result;
    }

    protected function checkConfigScope(array $scope)
    {
        $mId = intval($scope['m_id'] ?? 0);
        $gId = intval($scope['g_id'] ?? 0);
        $mgId = intval($scope['mg_id'] ?? 0);
        if ($mId > 0 && !MachineModel::getFind(['m_id' => $mId], 'm_id')) {
            return $this->rFail("生效范围设备不存在：" . $mId);
        }
        if ($gId > 0 && !GoodsModel::getFind(['g_id' => $gId], 'g_id')) {
            return $this->rFail("生效范围商品不存在：" . $gId);
        }
        if ($mgId > 0) {
            $where = ['mg_id' => $mgId];
            if ($mId > 0) $where['m_id'] = $mId;
            if ($gId > 0) $where['g_id'] = $gId;
            if (!MachineGoodsModel::getFind($where, 'mg_id')) {
                return $this->rFail("生效范围设备商品不存在：" . $mgId);
            }
        }
        return true;
    }

    protected function getConfigScopes($rrcfgId)
    {
        $rows = RevenueRuleConfigScopeModel::where(['rrcfg_id' => intval($rrcfgId)])
            ->order('sort asc,rrcs_id asc')
            ->select()
            ->toArray();
        return $this->formatScopeRows($rows);
    }

    protected function formatConfigRows($data)
    {
        if (is_object($data) && method_exists($data, 'toArray')) $data = $data->toArray();
        if (!is_array($data)) return $data;
        if (isset($data['rrcfg_id'])) return $this->formatConfigRow($data);
        if (isset($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as &$row) if (is_array($row)) $row = $this->formatConfigRow($row);
            unset($row);
            return $data;
        }
        foreach ($data as &$row) if (is_array($row)) $row = $this->formatConfigRow($row);
        unset($row);
        return $data;
    }

    protected function formatConfigRow(array $row)
    {
        $row['rr_id'] = intval($row['rrcfg_id'] ?? 0);
        $row['rrc_id'] = intval($row['rrcfg_id'] ?? 0);
        $row['rule_name'] = $row['config_name'] ?? '';
        $row['receivers'] = isset($row['receiver_config']) ? json_decode($row['receiver_config'], true) : [];
        if (!is_array($row['receivers'])) $row['receivers'] = [];
        return $row;
    }

    protected function formatScopeRows($data)
    {
        if (is_object($data) && method_exists($data, 'toArray')) $data = $data->toArray();
        if (!is_array($data)) return $data;
        if (isset($data['rrcs_id'])) return $this->formatScopeRow($data);
        if (isset($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as &$row) if (is_array($row)) $row = $this->formatScopeRow($row);
            unset($row);
            return $data;
        }
        foreach ($data as &$row) if (is_array($row)) $row = $this->formatScopeRow($row);
        unset($row);
        return $data;
    }

    protected function formatScopeRow(array $row)
    {
        $row['rrm_id'] = intval($row['rrcs_id'] ?? ($row['rrm_id'] ?? 0));
        $row['rr_id'] = intval($row['rrcfg_id'] ?? ($row['rr_id'] ?? 0));
        return $row;
    }

    protected function appendConfigScopeNums($data)
    {
        if (is_object($data) && method_exists($data, 'toArray')) $data = $data->toArray();
        if (!is_array($data)) return $data;
        $configIds = [];
        $this->collectConfigIds($data, $configIds);
        if (!$configIds) return $data;
        $rows = RevenueRuleConfigScopeModel::whereIn('rrcfg_id', array_keys($configIds))
            ->field('rrcfg_id,COUNT(DISTINCT m_id) machine_num')
            ->group('rrcfg_id')
            ->select()
            ->toArray();
        $nums = [];
        foreach ($rows as $row) $nums[intval($row['rrcfg_id'])] = intval($row['machine_num']);
        return $this->fillConfigScopeNums($data, $nums);
    }

    protected function collectConfigIds(array $data, array &$configIds)
    {
        foreach ($data as $field => $value) {
            if (($field === 'rrcfg_id' || $field === 'rr_id') && intval($value) > 0) $configIds[intval($value)] = true;
            if (is_array($value)) $this->collectConfigIds($value, $configIds);
        }
    }

    protected function fillConfigScopeNums(array $data, array $nums)
    {
        if (isset($data['rrcfg_id'])) $data['machine_num'] = $nums[intval($data['rrcfg_id'])] ?? 0;
        foreach ($data as &$value) if (is_array($value)) $value = $this->fillConfigScopeNums($value, $nums);
        unset($value);
        return $data;
    }

    protected function extractWhereValue($where, array $fields)
    {
        if (!is_array($where)) return 0;
        foreach ($where as $condition) {
            if (!is_array($condition) || count($condition) < 3) continue;
            if (in_array($condition[0], $fields, true)) return intval($condition[2]);
        }
        return 0;
    }

    protected function normalizeMachineIds($machineIds)
    {
        if (is_string($machineIds)) {
            $decoded = json_decode($machineIds, true);
            $machineIds = is_array($decoded) ? $decoded : explode(',', $machineIds);
        }
        if (!is_array($machineIds)) {
            $machineIds = [$machineIds];
        }
        return array_values(array_unique(array_filter(array_map('intval', $machineIds))));
    }

}
