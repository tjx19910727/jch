<?php

namespace app\AppFactory\Management\Revenue;

use app\AppFactory\Kernel\Model\Activity\Coupon\ActivityCouponModel;
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
            return $this->rA(['rrcfg_id' => $rrcfgId]);
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

    public function getList($where= [], $pageNum = 0, $field = "*", $order = "rrcfg_id desc",$rQ = 1)
    {
        $where = $this->normalizeConfigWhere($where);
        $data = $this->formatConfigRows($this->getRevenueRuleConfigList($where, $pageNum, "*", $order));
        $data = $this->appendConfigScopeNums($data);
        $data = $this->appendConfigScopes($data);
        return $this->rQ($this->appendRevenueOrganizationNames($data));
    }

    public function getFind($where = [], $field = "*", $order = "rrcfg_id desc",$rQ = 1)
    {
        $where = $this->normalizeConfigWhere($where);
        $data = $this->formatConfigRows($this->getRevenueRuleConfigFind($where, "*", $order));
        if (is_array($data) && isset($data['rrcfg_id'])) {
            $data['scopes'] = $this->getConfigScopes($data['rrcfg_id']);
        }
        return $this->rQ($this->appendRevenueOrganizationNames($data));
    }

    protected function getConfigIdFromData(array $data)
    {
        return intval($data['rrcfg_id'] ?? 0);
    }

    protected function normalizeConfigWhere($where)
    {
        if (!is_array($where)) return $where;
        foreach ($where as &$condition) {
            if (!is_array($condition) || count($condition) < 1) continue;
        }
        unset($condition);
        return $where;
    }

    protected function checkConfigData(&$data, $isUpdate = false)
    {
        if (!$isUpdate || isset($data['config_name'])) {
            $name = trim($data['config_name'] ?? '');
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
        $receiverCheck = $this->checkReceiverConfigData($merged);
        if ($receiverCheck !== true) return $receiverCheck;
        return true;
    }

    protected function normalizeConfigCouponData(&$data, array $merged, $isUpdate = false)
    {
        $couponId = intval($merged['coupon_id'] ?? 0);
        if ($couponId <= 0) {
            return $this->rFail("优惠券ID不能为空");
        }
        $coupon = ActivityCouponModel::where(['c_id' => $couponId])->find();
        if (!$coupon) {
            return $this->rFail("关联优惠券不存在");
        }
        $query = RevenueRuleConfigModel::where(['coupon_id' => $couponId, 'rule_mode' => 5]);
        if ($isUpdate) {
            $query->where('rrcfg_id', '<>', $this->getConfigIdFromData($merged));
        }
        if ($query->find()) {
            return $this->rFail("优惠券已关联分账配置");
        }
        $data['coupon_id'] = $couponId;
        return true;
    }

    protected function buildConfigSaveData(array $data, $isUpdate = false)
    {
        $save = [];
        foreach ([
            'config_name', 'rule_mode', 'base_type', 'turnover_type', 'tier_calc_mode',
            'settlement_type', 'settlement_days', 'coupon_id', 'receiver_config', 'status',
        ] as $field) {
            if (array_key_exists($field, $data)) $save[$field] = $data[$field];
        }
        if (isset($data['receivers']) && !isset($save['receiver_config'])) $save['receiver_config'] = $data['receivers'];
        if (isset($save['receiver_config'])) $save['receiver_config'] = $this->encodeReceiverConfig($save['receiver_config']);
        if (!$isUpdate) {
            foreach (['base_type' => 1, 'turnover_type' => 1, 'tier_calc_mode' => 1, 'settlement_type' => 1, 'settlement_days' => 0, 'status' => 1] as $field => $value) {
                if (!isset($save[$field])) $save[$field] = $value;
            }
            if (!isset($save['receiver_config'])) $save['receiver_config'] = '[]';
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

    protected function checkReceiverConfigData(array $data)
    {
        $config = $data['receivers'] ?? ($data['receiver_config'] ?? []);
        if (is_string($config)) {
            $decoded = json_decode($config, true);
            $config = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($config) || intval($data['rule_mode'] ?? 0) !== 4) {
            return true;
        }
        $percentTotals = [];
        foreach ($config as $item) {
            if (!is_array($item) || intval($item['status'] ?? 1) !== 1) continue;
            if (intval($item['calc_type'] ?? 0) !== 1) continue;
            $gId = intval($item['g_id'] ?? 0);
            $mgId = intval($item['mg_id'] ?? 0);
            $key = $gId . ':' . $mgId;
            $percentTotals[$key] = ($percentTotals[$key] ?? 0) + floatval($item['calc_value'] ?? 0);
            if ($percentTotals[$key] > 100) {
                return $this->rFail("同一商品固定比例分账合计不能超过100%");
            }
        }
        return true;
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

    protected function normalizeConfigItem(array $item)
    {
        $itemKey = intval($item['item_key'] ?? 0);
        $item['item_key'] = $itemKey;
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
        $tierKey = intval($tier['tier_key'] ?? 0);
        $tier['tier_key'] = $tierKey;
        $tier['threshold_min'] = $tier['threshold_min'] ?? 0;
        $tier['threshold_max'] = $tier['threshold_max'] ?? null;
        $tier['calc_value'] = $tier['calc_value'] ?? 0;
        $tier['status'] = intval($tier['status'] ?? 1) ?: 1;
        return $tier;
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
            $gId = intval($scope['g_id'] ?? 0);
            $mgId = intval($scope['mg_id'] ?? 0);
            $machineGoods = $mgId > 0
                ? MachineGoodsModel::getFind(['mg_id' => $mgId], 'mg_id,m_id,machine_id,g_id')
                : [];
            if ($machineGoods) {
                if ($mId <= 0) $mId = intval($machineGoods['m_id'] ?? 0);
                if ($gId <= 0) $gId = intval($machineGoods['g_id'] ?? 0);
            }
            $machine = $mId > 0 ? MachineModel::getFind(['m_id' => $mId], 'm_id,machine_id,ao_id') : [];
            $result[] = [
                'm_id' => $mId,
                'machine_id' => trim($scope['machine_id'] ?? ($machineGoods['machine_id'] ?? ($machine['machine_id'] ?? ''))),
                'ao_id' => intval($scope['ao_id'] ?? ($machine['ao_id'] ?? 0)),
                'g_id' => $gId,
                'mg_id' => $mgId,
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
        $status = intval($scope['status'] ?? 1);
        if (!in_array($status, [1, 2], true)) {
            return $this->rFail("生效范围状态不合法");
        }
        if ($mId > 0 && !MachineModel::getFind(['m_id' => $mId], 'm_id')) {
            return $this->rFail("生效范围设备不存在：" . $mId);
        }
        if ($gId > 0 && !GoodsModel::getFind(['g_id' => $gId], 'g_id')) {
            return $this->rFail("生效范围商品不存在：" . $gId);
        }
        if ($mgId > 0) {
            if ($mId <= 0) {
                return $this->rFail("指定设备商品时必须明确设备");
            }
            $where = ['mg_id' => $mgId];
            $where['m_id'] = $mId;
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
        $row['receivers'] = isset($row['receiver_config']) ? json_decode($row['receiver_config'], true) : [];
        if (!is_array($row['receivers'])) $row['receivers'] = [];
        if (intval($row['rule_mode'] ?? 0) === 5 && intval($row['coupon_id'] ?? 0) > 0) {
            $coupon = ActivityCouponModel::where(['c_id' => intval($row['coupon_id'])])
                ->field('c_id,code,c_type,pay_limit,reduction,status,start_date,end_date,used_limit')
                ->find();
            if ($coupon) {
                if (!is_array($coupon)) $coupon = $coupon->toArray();
                $row['coupon_info'] = $coupon;
            }
        }
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

    protected function appendConfigScopes($data)
    {
        if (is_object($data) && method_exists($data, 'toArray')) $data = $data->toArray();
        if (!is_array($data)) return $data;
        $configIds = [];
        $this->collectConfigIds($data, $configIds);
        if (!$configIds) return $data;
        $rows = RevenueRuleConfigScopeModel::whereIn('rrcfg_id', array_keys($configIds))
            ->order('sort asc,rrcs_id asc')
            ->select()
            ->toArray();
        $scopes = [];
        foreach ($this->formatScopeRows($rows) as $row) {
            $rrcfgId = intval($row['rrcfg_id'] ?? 0);
            if ($rrcfgId <= 0) continue;
            if (!isset($scopes[$rrcfgId])) $scopes[$rrcfgId] = [];
            $scopes[$rrcfgId][] = $row;
        }
        return $this->fillConfigScopes($data, $scopes);
    }

    protected function collectConfigIds(array $data, array &$configIds)
    {
        foreach ($data as $field => $value) {
            if ($field === 'rrcfg_id' && intval($value) > 0) $configIds[intval($value)] = true;
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

    protected function fillConfigScopes(array $data, array $scopes)
    {
        if (isset($data['rrcfg_id']) && !isset($data['rrcs_id'])) {
            $data['scopes'] = $scopes[intval($data['rrcfg_id'])] ?? [];
        }
        foreach ($data as $field => &$value) {
            if ($field === 'scopes') continue;
            if (is_array($value)) $value = $this->fillConfigScopes($value, $scopes);
        }
        unset($value);
        return $data;
    }

}
