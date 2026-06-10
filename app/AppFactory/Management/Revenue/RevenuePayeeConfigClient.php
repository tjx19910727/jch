<?php

namespace app\AppFactory\Management\Revenue;

use app\AppFactory\Kernel\Traits\Revenue\RevenueAccountTrait;
use app\AppFactory\Kernel\Traits\Revenue\RevenuePayChannelTrait;
use app\AppFactory\Kernel\Traits\Revenue\RevenuePayeeConfigTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyPayeeTrait;
use app\AppFactory\Management\ManagementClient;
use think\facade\Db;

class RevenuePayeeConfigClient extends ManagementClient
{
    use RevenuePayeeConfigTrait;
    use RevenueAccountTrait;
    use RevenuePayChannelTrait;
    use StrategyPayeeTrait;

    public function saveByPayee($postData)
    {
        $exists = null;
        if (!empty($postData['sp_id'])) {
            $exists = $this->getRevenuePayeeConfigFind(['sp_id' => $postData['sp_id']]);
        }
        if (!$exists) {
            if (!isset($postData['settlement_type'])) $postData['settlement_type'] = 1;
            if (!isset($postData['settlement_days'])) $postData['settlement_days'] = 0;
        }
        $check = $this->checkData($postData);
        if ($check !== true) return $check;
        if ($exists) {
            $postData['rpcfg_id'] = $exists['rpcfg_id'];
            return $this->rU($this->updateRevenuePayeeConfig($postData, [], ['rpcfg_id']));
        }
        if (!isset($postData['status'])) $postData['status'] = 1;
        return $this->rA($this->addRevenuePayeeConfig($postData));
    }

    public function getList($where= [], $pageNum = 0, $field = "*", $order = "rpcfg_id desc",$rQ = 1)
    {
        return $this->rQ($this->getRevenuePayeeConfigList($where, $pageNum, $field, $order));
    }
    public function getFind($where = [], $field = "*", $order = "rpcfg_id desc",$rQ = 1)
    {
        return $this->rQ($this->getRevenuePayeeConfigFind($where, $field, $order));
    }

    public function checkConfig()
    {
        $result = [
            'enabled_channels' => Db::name('revenue_pay_channel')->where(['status' => 1])->order('rpc_id desc')->select()->toArray(),
            'payee_without_config' => $this->getPayeeWithoutConfig(),
            'payee_without_default_account' => $this->getPayeeWithoutDefaultAccount(),
            'invalid_default_account' => $this->getInvalidDefaultAccount(),
            'invalid_rule_account' => $this->getInvalidRuleAccount(),
            'invalid_rental_item' => $this->getInvalidRentalItem(),
            'device_rule_percent_overflow' => $this->getDeviceRulePercentOverflow(),
            'invalid_tier' => $this->getInvalidTier(),
        ];
        $result['has_error'] = false;
        foreach ($result as $key => $value) {
            if ($key !== 'enabled_channels' && $key !== 'has_error' && !empty($value)) {
                $result['has_error'] = true;
                break;
            }
        }
        return $this->rQ($result);
    }

    protected function checkData(&$data)
    {
        if (empty($data['sp_id'])) return $this->rFail("收款策略ID不能为空");
        $payee = $this->getStrategyPayeeFind(['sp_id' => $data['sp_id']], 'sp_id,payee_type,ao_id');
        if (!$payee) return $this->rFail("收款策略不存在");
        $data['payee_type'] = $data['payee_type'] ?? $payee['payee_type'];
        $data['ao_id'] = $data['ao_id'] ?? $payee['ao_id'];
        if (!isset($data['enable_revenue'])) $data['enable_revenue'] = 1;
        $settlementData = $data;
        if (!empty($data['sp_id']) && (!isset($data['settlement_type']) || !isset($data['settlement_days']))) {
            $current = $this->getRevenuePayeeConfigFind(['sp_id' => $data['sp_id']]);
            if ($current) {
                $settlementData = array_merge(is_array($current) ? $current : $current->toArray(), $data);
            }
        }
        $settlementType = intval($settlementData['settlement_type'] ?? 1);
        $settlementDays = intval($settlementData['settlement_days'] ?? 0);
        if (!in_array($settlementType, [1, 2], true)) return $this->rFail("分账时间类型不合法");
        if ($settlementDays < 0) return $this->rFail("T+N 天数不能小于0");
        if ($settlementType === 2 && $settlementDays < 1) return $this->rFail("T+N 分账天数必须大于0");
        if ($settlementType === 1 && (isset($data['settlement_type']) || isset($data['settlement_days']))) {
            $data['settlement_days'] = 0;
        }
        if (!$this->shouldEnableRevenue($data)) {
            if (empty($data['default_ra_id'])) $data['default_manager_id'] = 0;
            return true;
        }
        if (empty($data['default_ra_id'])) return $this->rFail("当前收款渠道已启用分账，默认分账账户不能为空");
        $account = $this->getRevenueAccountFind(['ra_id' => $data['default_ra_id'], 'status' => 1]);
        if (!$account) return $this->rFail("默认分账账户不存在或未启用");
        if (intval($account['ao_id']) !== intval($data['ao_id'])) {
            return $this->rFail("默认分账账户所属组织与收款策略组织不一致");
        }
        $data['default_manager_id'] = $account['manager_id'];
        return true;
    }

    protected function shouldEnableRevenue($data)
    {
        if (intval($data['enable_revenue'] ?? 1) !== 1) return false;
        $payeeType = intval($data['payee_type'] ?? 0);
        if ($payeeType <= 0) return false;
        if ($this->getRevenuePayChannelFind(['pay_type' => $payeeType, 'status' => 1], 'rpc_id')) return true;
        if ($this->getRevenuePayChannelFind(['payee_type' => $payeeType, 'status' => 1], 'rpc_id')) return true;
        return false;
    }

    protected function getPayeeWithoutConfig()
    {
        return Db::name('strategy_payee')
            ->alias('sp')
            ->join('revenue_pay_channel rpc', 'rpc.status = 1 AND (rpc.pay_type = sp.payee_type OR rpc.payee_type = sp.payee_type)')
            ->leftJoin('revenue_payee_config rpcfg', 'rpcfg.sp_id = sp.sp_id AND rpcfg.status = 1')
            ->where('sp.status', 1)
            ->whereNull('rpcfg.rpcfg_id')
            ->field('sp.sp_id,sp.sp_name,sp.payee_type,sp.ao_id')
            ->order('sp.sp_id desc')
            ->select()
            ->toArray();
    }

    protected function getPayeeWithoutDefaultAccount()
    {
        return Db::name('revenue_payee_config')
            ->alias('rpcfg')
            ->leftJoin('strategy_payee sp', 'sp.sp_id = rpcfg.sp_id')
            ->where(['rpcfg.status' => 1, 'rpcfg.enable_revenue' => 1])
            ->where(function ($query) {
                $query->whereNull('rpcfg.default_ra_id')->whereOr('rpcfg.default_ra_id', 0);
            })
            ->field('rpcfg.rpcfg_id,rpcfg.sp_id,sp.sp_name,rpcfg.payee_type,rpcfg.ao_id,rpcfg.default_ra_id,rpcfg.default_manager_id,rpcfg.enable_revenue')
            ->select()
            ->toArray();
    }

    protected function getInvalidDefaultAccount()
    {
        return Db::name('revenue_payee_config')
            ->alias('rpcfg')
            ->leftJoin('strategy_payee sp', 'sp.sp_id = rpcfg.sp_id')
            ->leftJoin('revenue_account ra', 'ra.ra_id = rpcfg.default_ra_id')
            ->where(['rpcfg.status' => 1, 'rpcfg.enable_revenue' => 1])
            ->where(function ($query) {
                $query->whereNull('ra.ra_id')->whereOr('ra.status', '<>', 1)->whereOrRaw('ra.ao_id <> rpcfg.ao_id');
            })
            ->field('rpcfg.rpcfg_id,rpcfg.sp_id,sp.sp_name,rpcfg.ao_id payee_ao_id,rpcfg.default_ra_id,ra.ao_id account_ao_id,ra.status account_status')
            ->select()
            ->toArray();
    }

    protected function getInvalidRuleAccount()
    {
        return Db::name('revenue_rule')
            ->alias('rr')
            ->join('revenue_rule_item rri', 'rri.rr_id = rr.rr_id AND rri.status = 1')
            ->leftJoin('revenue_account ra', 'ra.ra_id = rri.ra_id')
            ->where('rr.status', 1)
            ->where(function ($query) {
                $query->whereNull('ra.ra_id')->whereOr('ra.status', '<>', 1)->whereOrRaw('ra.ao_id <> rri.receiver_ao_id');
            })
            ->field('rr.rr_id,rr.rule_name,rr.rule_mode,rri.rri_id,rri.receiver_ao_id,rri.ra_id,ra.ao_id account_ao_id,ra.status account_status')
            ->select()
            ->toArray();
    }

    protected function getInvalidRentalItem()
    {
        return Db::name('revenue_rule')
            ->alias('rr')
            ->join('revenue_rule_item rri', 'rri.rr_id = rr.rr_id AND rri.status = 1')
            ->where(['rr.status' => 1, 'rr.rule_mode' => 2])
            ->where(function ($query) {
                $query->whereNotIn('rri.calc_type', [1, 2, 3])
                    ->whereOr(function ($q) {
                        $q->whereIn('rri.calc_type', [1, 2])->where('rri.calc_value', '<=', 0);
                    })
                    ->whereOr(function ($q) {
                        $q->where('rri.calc_type', 1)->where('rri.calc_value', '>', 100);
                    });
            })
            ->field('rr.rr_id,rr.rule_name,rri.rri_id,rri.receiver_ao_id,rri.calc_type,rri.calc_value')
            ->select()
            ->toArray();
    }

    protected function getDeviceRulePercentOverflow()
    {
        return Db::name('revenue_rule')
            ->alias('rr')
            ->join('revenue_rule_item rri', 'rri.rr_id = rr.rr_id AND rri.status = 1')
            ->where(['rr.status' => 1, 'rr.rule_mode' => 3, 'rri.calc_type' => 1])
            ->group('rr.rr_id,rr.rule_name')
            ->having('total_percent > 100')
            ->field('rr.rr_id,rr.rule_name,SUM(rri.calc_value) total_percent')
            ->select()
            ->toArray();
    }

    protected function getInvalidTier()
    {
        return Db::name('revenue_rule_item_tier')
            ->where('status', 1)
            ->where(function ($query) {
                $query->where('threshold_min', '<', 0)
                    ->whereOrRaw('(threshold_max IS NOT NULL AND threshold_max <= threshold_min)')
                    ->whereOr('calc_value', '<', 0)
                    ->whereOr('calc_value', '>', 100);
            })
            ->field('rrit_id,rri_id,threshold_min,threshold_max,calc_value')
            ->select()
            ->toArray();
    }
}
