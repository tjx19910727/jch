<?php

namespace app\AppFactory\Management\Currency;

use app\AppFactory\Kernel\Service\Currency\CurrencyCatalogService;
use app\AppFactory\Management\ManagementClient;
use app\common\enum\CurrencyStatus;
use think\facade\Db;

class CurrencyClient extends ManagementClient
{
    protected $catalog;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->catalog = new CurrencyCatalogService();
    }

    public function getEnabledList()
    {
        return $this->r(200, $this->lang('query_success'), $this->catalog->getEnabledList());
    }

    public function getCurrencyList($postData)
    {
        $where = [];
        if (isset($postData['status']) && $postData['status'] !== '') {
            $where['status'] = intval($postData['status']);
        }
        return $this->r(200, $this->lang('query_success'), $this->catalog->getList($where));
    }

    public function addCurrency($postData)
    {
        try {
            $data = $this->normalizePayload($postData, true);
            $managerId = intval(isset($this->manager['manager_id']) ? $this->manager['manager_id'] : 0);
            $result = Db::transaction(function () use ($data, $managerId) {
                if ($this->catalog->getByCode($data['currency_code'])) {
                    throw new \InvalidArgumentException('币种编码已存在');
                }
                if ($data['is_default'] === 1) {
                    Db::name('currency_info')->where('is_default', 1)->lock(true)->select();
                    Db::name('currency_info')->where('is_default', 1)->update(['is_default' => 0, 'update_id' => $managerId]);
                }
                $data['creator'] = $managerId;
                $data['update_id'] = $managerId;
                return Db::name('currency_info')->insert($data);
            });
            return $this->rA($result);
        } catch (\Exception $e) {
            actionException($e, 1, 'addCurrency');
            return $this->rValidate($e->getMessage());
        }
    }

    public function updateCurrency($postData)
    {
        try {
            $currencyCode = $this->catalog->normalizeCode(isset($postData['currency_code']) ? $postData['currency_code'] : '');
            $old = $this->catalog->getByCode($currencyCode);
            if (!$old) {
                return $this->r(100, '币种不存在');
            }
            $data = $this->normalizePayload($postData, false);
            unset($data['currency_code']);
            if (intval($old['is_default']) === 1 && isset($data['is_default']) && intval($data['is_default']) === 0) {
                return $this->r(100, '默认币种不能直接取消，请将其他启用币种设为默认');
            }
            if (isset($data['is_default']) && intval($data['is_default']) === 1
                && intval(isset($data['status']) ? $data['status'] : $old['status']) !== CurrencyStatus::ENABLED) {
                return $this->r(100, '只有启用币种可以设为默认');
            }
            if (isset($data['decimal_places']) && intval($data['decimal_places']) !== intval($old['decimal_places'])) {
                $references = $this->catalog->getReferenceSummary($currencyCode);
                if (array_sum($references) > 0) {
                    return $this->r(100, '币种已被业务引用，不能修改小数位', $references);
                }
            }
            if (isset($data['status']) && intval($data['status']) === CurrencyStatus::DISABLED) {
                if (intval($old['is_default']) === 1) {
                    return $this->r(100, '默认币种不能停用');
                }
                $activeUse = $this->catalog->getActiveUseSummary($currencyCode);
                if (array_sum($activeUse) > 0) {
                    return $this->r(100, '币种仍被设备或未完成订单使用，不能停用', $activeUse);
                }
            }
            $managerId = intval(isset($this->manager['manager_id']) ? $this->manager['manager_id'] : 0);
            $result = Db::transaction(function () use ($currencyCode, $data, $managerId) {
                if (isset($data['is_default']) && intval($data['is_default']) === 1) {
                    Db::name('currency_info')->where('is_default', 1)->lock(true)->select();
                    Db::name('currency_info')->where('is_default', 1)->update(['is_default' => 0, 'update_id' => $managerId]);
                }
                $data['update_id'] = $managerId;
                return Db::name('currency_info')->where('currency_code', $currencyCode)->update($data);
            });
            return $this->rU($result);
        } catch (\Exception $e) {
            actionException($e, 1, 'updateCurrency');
            return $this->rValidate($e->getMessage());
        }
    }

    public function getReferences($currencyCode)
    {
        try {
            return $this->r(200, $this->lang('query_success'), $this->catalog->getReferenceSummary($currencyCode));
        } catch (\Exception $e) {
            return $this->rValidate($e->getMessage());
        }
    }

    protected function normalizePayload($postData, $requireCode)
    {
        $data = [];
        if ($requireCode || isset($postData['currency_code'])) {
            $data['currency_code'] = $this->catalog->normalizeCode(isset($postData['currency_code']) ? $postData['currency_code'] : '');
        }
        foreach (['currency_name', 'currency_symbol'] as $field) {
            if (isset($postData[$field])) {
                $data[$field] = trim((string)$postData[$field]);
            }
        }
        if ($requireCode && (empty($data['currency_name']) || !isset($data['currency_symbol']))) {
            throw new \InvalidArgumentException('币种名称和符号不能为空');
        }
        if (isset($postData['decimal_places']) || $requireCode) {
            $decimalPlaces = intval(isset($postData['decimal_places']) ? $postData['decimal_places'] : 2);
            if ($decimalPlaces < 0 || $decimalPlaces > 3) {
                throw new \InvalidArgumentException('小数位必须为0至3');
            }
            $data['decimal_places'] = $decimalPlaces;
        }
        if (isset($postData['status']) || $requireCode) {
            $status = intval(isset($postData['status']) ? $postData['status'] : CurrencyStatus::ENABLED);
            if (!in_array($status, [CurrencyStatus::ENABLED, CurrencyStatus::DISABLED], true)) {
                throw new \InvalidArgumentException('币种状态无效');
            }
            $data['status'] = $status;
        }
        if (isset($postData['is_default']) || $requireCode) {
            $data['is_default'] = intval(isset($postData['is_default']) ? $postData['is_default'] : 0) === 1 ? 1 : 0;
        }
        if (isset($postData['sort']) || $requireCode) {
            $data['sort'] = max(0, intval(isset($postData['sort']) ? $postData['sort'] : 0));
        }
        if (isset($data['is_default']) && $data['is_default'] === 1
            && isset($data['status']) && $data['status'] !== CurrencyStatus::ENABLED) {
            throw new \InvalidArgumentException('只有启用币种可以设为默认');
        }
        return $data;
    }
}
