<?php

namespace app\AppFactory\Management\Revenue;

use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Traits\Revenue\RevenueAccountTrait;
use app\AppFactory\Kernel\Traits\Revenue\RevenueRuleTrait;
use app\AppFactory\Management\ManagementClient;
use think\facade\Db;

class RevenueRuleClient extends ManagementClient
{
    use RevenueRuleTrait;
    use RevenueAccountTrait;
    use MachineTrait;

    public function addData($postData)
    {
        $check = $this->checkRuleData($postData);
        if ($check !== true) return $check;
        if (!isset($postData['status'])) $postData['status'] = 1;
        return $this->rA($this->addRevenueRule($postData));
    }

    public function updateData($postData)
    {
        if (empty($postData['rr_id'])) return $this->rFail("分账策略ID不能为空");
        $check = $this->checkRuleData($postData, true);
        if ($check !== true) return $check;
        return $this->rU($this->updateRevenueRule($postData, [], ['rr_id']));
    }

    public function getList($where= [], $pageNum = 0, $field = "*", $order = "rr_id desc",$rQ = 1)
    {
        return $this->rQ($this->getRevenueRuleList($where, $pageNum, $field, $order));
    }

    public function getFind($where = [], $field = "*", $order = "rr_id desc",$rQ = 1)
    {
        return $this->rQ($this->getRevenueRuleFind($where, $field, $order));
    }

    public function addItem($postData)
    {
        $check = $this->checkItemData($postData);
        if ($check !== true) return $check;
        if (!isset($postData['status'])) $postData['status'] = 1;
        return $this->rA($this->addRevenueRuleItem($postData));
    }

    public function addProductItem($postData)
    {
        return $this->addItem($postData);
    }

    public function updateItem($postData)
    {
        if (empty($postData['rri_id'])) return $this->rFail("分账策略明细ID不能为空");
        $check = $this->checkItemData($postData, true);
        if ($check !== true) return $check;
        return $this->rU($this->updateRevenueRuleItem($postData, [], ['rri_id']));
    }

    public function getItemList($where, $pageNum = 0, $field = "*", $order = "sort asc,rri_id asc")
    {
        return $this->rQ($this->getRevenueRuleItemList($where, $pageNum, $field, $order));
    }

    public function delItem($rriId)
    {
        return $this->rD($this->delRevenueRuleItem(['rri_id' => $rriId]));
    }

    public function addTier($postData)
    {
        $check = $this->checkTierData($postData);
        if ($check !== true) return $check;
        if (!isset($postData['status'])) $postData['status'] = 1;
        return $this->rA($this->addRevenueRuleItemTier($postData));
    }

    public function updateTier($postData)
    {
        if (empty($postData['rrit_id'])) return $this->rFail("阶梯分账明细ID不能为空");
        $check = $this->checkTierData($postData, true);
        if ($check !== true) return $check;
        return $this->rU($this->updateRevenueRuleItemTier($postData, [], ['rrit_id']));
    }

    public function getTierList($where, $pageNum = 0, $field = "*", $order = "threshold_min asc,rrit_id asc")
    {
        return $this->rQ($this->getRevenueRuleItemTierList($where, $pageNum, $field, $order));
    }

    public function delTier($rritId)
    {
        return $this->rD($this->delRevenueRuleItemTier(['rrit_id' => $rritId]));
    }

    public function bindMachine($postData)
    {
        if (empty($postData['rr_id'])) return $this->rFail("分账策略ID不能为空");
        if (empty($postData['m_id'])) return $this->rFail("设备ID不能为空");
        $rule = $this->getRevenueRuleFind(['rr_id' => $postData['rr_id'], 'status' => 1], 'rr_id,rule_mode');
        if (!$rule) return $this->rFail("分账策略不存在或未启用");
        $machine = $this->getMachineFind(['m_id' => $postData['m_id']], 'm_id,ao_id');
        if (!$machine) return $this->rFail("设备不存在");
        $exists = $this->getRevenueRuleMachineFind(['rr_id' => $postData['rr_id'], 'm_id' => $postData['m_id']], 'rrm_id');
        if ($exists) return $this->rFail("该设备已绑定当前分账策略");
        $modeExists = Db::name('revenue_rule_machine')
            ->alias('rrm')
            ->join('revenue_rule rr', 'rr.rr_id = rrm.rr_id')
            ->where([
                'rrm.m_id' => $postData['m_id'],
                'rrm.status' => 1,
                'rr.status' => 1,
                'rr.rule_mode' => intval($rule['rule_mode']),
            ])
            ->find();
        if ($modeExists) return $this->rFail("该设备已绑定同类型启用分账策略");
        $postData['ao_id'] = $postData['ao_id'] ?? $machine['ao_id'];
        if (!isset($postData['status'])) $postData['status'] = 1;
        return $this->rA($this->addRevenueRuleMachine($postData));
    }

    public function getMachineList($where, $pageNum = 0, $field = "*", $order = "rrm_id desc")
    {
        return $this->rQ($this->getRevenueRuleMachineList($where, $pageNum, $field, $order));
    }

    public function unbindMachine($rrmId)
    {
        return $this->rD($this->delRevenueRuleMachine(['rrm_id' => $rrmId]));
    }

    protected function checkRuleData($data, $isUpdate = false)
    {
        if (!$isUpdate || isset($data['rule_name'])) {
            if (empty($data['rule_name'])) return $this->rFail("分账策略名称不能为空");
        }
        if (!$isUpdate || isset($data['rule_mode'])) {
            if (empty($data['rule_mode']) || !in_array(intval($data['rule_mode']), [1, 2, 3, 4], true)) {
                return $this->rFail("分账策略模式不合法");
            }
        }
        return true;
    }

    protected function checkItemData($data, $isUpdate = false)
    {
        $rule = null;
        $oldItem = null;
        if (!$isUpdate || isset($data['rr_id'])) {
            if (empty($data['rr_id'])) return $this->rFail("分账策略ID不能为空");
            $rule = $this->getRevenueRuleFind(['rr_id' => $data['rr_id']], 'rr_id,rule_mode');
            if (!$rule) return $this->rFail("分账策略不存在");
        } elseif (!empty($data['rri_id'])) {
            $oldItem = $this->getRevenueRuleItemFind(['rri_id' => $data['rri_id']], 'rri_id,rr_id,g_id,calc_type,calc_value,status');
            if ($oldItem) {
                $rule = $this->getRevenueRuleFind(['rr_id' => $oldItem['rr_id']], 'rr_id,rule_mode');
            }
        }
        if (!$isUpdate || isset($data['receiver_ao_id'])) {
            if (empty($data['receiver_ao_id'])) return $this->rFail("分账接收组织不能为空");
        }
        if (!$isUpdate || isset($data['ra_id'])) {
            if (empty($data['ra_id'])) return $this->rFail("分账账户不能为空");
            $account = $this->getRevenueAccountFind(['ra_id' => $data['ra_id'], 'status' => 1]);
            if (!$account) return $this->rFail("分账账户不存在或未启用");
            if (isset($data['receiver_ao_id']) && intval($account['ao_id']) !== intval($data['receiver_ao_id'])) {
                return $this->rFail("分账账户所属组织与接收组织不一致");
            }
            if (!isset($data['manager_id']) || !$data['manager_id']) {
                $data['manager_id'] = $account['manager_id'];
            }
            if (isset($data['manager_id']) && intval($data['manager_id']) !== intval($account['manager_id'])) {
                return $this->rFail("账户管理人与分账账户绑定管理人不一致");
            }
        }
        if (!$isUpdate || isset($data['calc_type'])) {
            if (empty($data['calc_type']) || !in_array(intval($data['calc_type']), [1, 2, 3, 4], true)) {
                return $this->rFail("分账计算方式不合法");
            }
        }
        if (isset($data['calc_value']) && floatval($data['calc_value']) < 0) {
            return $this->rFail("分账比例或金额不能小于0");
        }
        $calcType = intval($data['calc_type'] ?? ($oldItem['calc_type'] ?? 0));
        if (in_array($calcType, [1, 2], true) && !isset($data['calc_value'])) {
            return $this->rFail("百分比或固定金额不能为空");
        }
        if ($calcType === 1 && floatval($data['calc_value']) > 100) {
            return $this->rFail("分账比例不能超过100");
        }
        if ($rule && intval($rule['rule_mode']) === 2) {
            if ($calcType === 4) return $this->rFail("设备出租分账不支持阶梯分账");
            if (in_array($calcType, [1, 2], true) && floatval($data['calc_value'] ?? 0) <= 0) {
                return $this->rFail("设备出租分账比例或金额必须大于0");
            }
        }
        if ($rule && intval($rule['rule_mode']) === 3) {
            $percentCheck = $this->checkRulePercentLimit($data, $oldItem, $calcType);
            if ($percentCheck !== true) return $percentCheck;
        }
        if ($rule && intval($rule['rule_mode']) === 4) {
            $productCheck = $this->checkProductItemData($data, $oldItem, $calcType);
            if ($productCheck !== true) return $productCheck;
        }
        return true;
    }

    protected function checkProductItemData($data, $oldItem, $calcType)
    {
        $gId = intval($data['g_id'] ?? ($oldItem['g_id'] ?? 0));
        if ($gId <= 0) return $this->rFail("设备商品分账必须配置商品");
        if (!Db::name('goods')->where(['g_id' => $gId])->find()) {
            return $this->rFail("商品不存在");
        }
        if (!in_array(intval($calcType), [1, 2], true)) {
            return $this->rFail("设备商品分账仅支持按比例或按件固定金额");
        }
        if (floatval($data['calc_value'] ?? ($oldItem['calc_value'] ?? 0)) <= 0) {
            return $this->rFail("设备商品分账比例或金额必须大于0");
        }
        if (intval($calcType) === 1) {
            $rrId = intval($data['rr_id'] ?? ($oldItem['rr_id'] ?? 0));
            $query = Db::name('revenue_rule_item')->where([
                'rr_id' => $rrId,
                'g_id' => $gId,
                'status' => 1,
                'calc_type' => 1,
            ]);
            if (!empty($data['rri_id'])) {
                $query->where('rri_id', '<>', intval($data['rri_id']));
            }
            $total = floatval($query->sum('calc_value')) + floatval($data['calc_value'] ?? ($oldItem['calc_value'] ?? 0));
            if ($total > 100) return $this->rFail("同一商品固定比例分账合计不能超过100%");
        }
        return true;
    }

    protected function checkRulePercentLimit($data, $oldItem, $calcType)
    {
        $rrId = intval($data['rr_id'] ?? ($oldItem['rr_id'] ?? 0));
        if ($rrId <= 0) return true;
        $status = intval($data['status'] ?? ($oldItem['status'] ?? 1));
        $query = Db::name('revenue_rule_item')->where(['rr_id' => $rrId, 'status' => 1, 'calc_type' => 1]);
        if (!empty($data['rri_id'])) {
            $query->where('rri_id', '<>', intval($data['rri_id']));
        }
        $total = $query->sum('calc_value');
        $total = floatval($total);
        if ($status === 1 && intval($calcType) === 1) {
            $total += floatval($data['calc_value'] ?? ($oldItem['calc_value'] ?? 0));
        }
        if ($total > 100) {
            return $this->rFail("设备分账策略固定比例合计不能超过100%");
        }
        return true;
    }

    protected function checkTierData($data, $isUpdate = false)
    {
        if (!$isUpdate || isset($data['rri_id'])) {
            if (empty($data['rri_id'])) return $this->rFail("分账策略明细ID不能为空");
        }
        if (!$isUpdate || isset($data['threshold_min'])) {
            if (!isset($data['threshold_min']) || floatval($data['threshold_min']) < 0) {
                return $this->rFail("营业额下限不能小于0");
            }
        }
        if (isset($data['threshold_max']) && $data['threshold_max'] !== '' && $data['threshold_max'] !== null) {
            if (floatval($data['threshold_max']) <= floatval($data['threshold_min'] ?? 0)) {
                return $this->rFail("营业额上限必须大于下限");
            }
        }
        if (!$isUpdate || isset($data['calc_value'])) {
            if (!isset($data['calc_value']) || floatval($data['calc_value']) < 0 || floatval($data['calc_value']) > 100) {
                return $this->rFail("阶梯分账比例必须在0到100之间");
            }
        }
        if (isset($data['rri_id']) && isset($data['threshold_min'])) {
            $overlap = $this->checkTierOverlap($data, $isUpdate);
            if ($overlap !== true) return $overlap;
        }
        return true;
    }

    protected function checkTierOverlap($data, $isUpdate = false)
    {
        $min = floatval($data['threshold_min']);
        $max = null;
        if (isset($data['threshold_max']) && $data['threshold_max'] !== '' && $data['threshold_max'] !== null) {
            $max = floatval($data['threshold_max']);
        }
        $tiers = $this->getRevenueRuleItemTierList(['rri_id' => $data['rri_id'], 'status' => 1], 0, 'rrit_id,threshold_min,threshold_max');
        if (!$tiers) return true;
        foreach ($tiers as $tier) {
            if ($isUpdate && isset($data['rrit_id']) && intval($tier['rrit_id']) === intval($data['rrit_id'])) {
                continue;
            }
            $tierMin = floatval($tier['threshold_min']);
            $tierMax = $tier['threshold_max'] === null || $tier['threshold_max'] === '' ? null : floatval($tier['threshold_max']);
            $leftLessThanRight = $max === null || $tierMin < $max;
            $rightGreaterThanLeft = $tierMax === null || $tierMax > $min;
            if ($leftLessThanRight && $rightGreaterThanLeft) {
                return $this->rFail("阶梯营业额区间不能重叠");
            }
        }
        return true;
    }
}
