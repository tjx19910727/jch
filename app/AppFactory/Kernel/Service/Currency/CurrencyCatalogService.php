<?php

namespace app\AppFactory\Kernel\Service\Currency;

use app\AppFactory\Kernel\Support\Currency\CurrencyPriceSupport;
use app\common\enum\CurrencyStatus;
use think\facade\Db;

class CurrencyCatalogService
{
    public function normalizeCode($currencyCode)
    {
        return CurrencyPriceSupport::normalizeCurrencyCode($currencyCode);
    }

    public function getEnabledList()
    {
        return Db::name('currency_info')
            ->where('status', CurrencyStatus::ENABLED)
            ->field('currency_code,currency_name,currency_symbol,decimal_places,is_default,sort')
            ->order('sort asc,currency_code asc')
            ->select()
            ->toArray();
    }

    public function getList($where = [])
    {
        return Db::name('currency_info')->where($where)->order('sort asc,currency_code asc')->select()->toArray();
    }

    public function getByCode($currencyCode, $enabledOnly = false)
    {
        $where = ['currency_code' => $this->normalizeCode($currencyCode)];
        if ($enabledOnly) {
            $where['status'] = CurrencyStatus::ENABLED;
        }
        return Db::name('currency_info')->where($where)->find();
    }

    public function assertEnabled($currencyCode)
    {
        $currency = $this->getByCode($currencyCode, true);
        if (!$currency) {
            throw new \InvalidArgumentException('币种不存在或未启用');
        }
        return $currency;
    }

    public function getDefaultCode()
    {
        $currencyCode = Db::name('currency_info')
            ->where(['status' => CurrencyStatus::ENABLED, 'is_default' => 1])
            ->order('sort asc')
            ->value('currency_code');
        return $currencyCode ? $this->normalizeCode($currencyCode) : (string)config('currency.default_code');
    }

    public function getReferenceSummary($currencyCode)
    {
        $currencyCode = $this->normalizeCode($currencyCode);
        $tables = [
            'goods_currency_price',
            'machine_goods_currency_price',
            'machine_channel_currency_price',
            'machine_config',
            'sale_orders',
            'sale_orders_details',
        ];
        $summary = [];
        foreach ($tables as $table) {
            $summary[$table] = Db::name($table)->where('currency_code', $currencyCode)->count();
        }
        return $summary;
    }

    public function getActiveUseSummary($currencyCode)
    {
        $currencyCode = $this->normalizeCode($currencyCode);
        return [
            'machine_config' => Db::name('machine_config')->where('currency_code', $currencyCode)->count(),
            'pending_orders' => Db::name('sale_orders')
                ->where('currency_code', $currencyCode)
                ->where('create_time', '>=', time() - 86400)
                ->where(function ($query) {
                    $query->whereIn('pay_status', [1, 2])
                        ->whereOr(function ($subQuery) {
                            $subQuery->where('pay_status', 3)->whereIn('out_status', [1, 2, 6]);
                        });
                })
                ->count(),
        ];
    }
}
