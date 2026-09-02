<?php

namespace app\AppFactory\Kernel\Service\Currency;

use app\AppFactory\Kernel\Support\Currency\CurrencyPriceSupport;
use think\facade\Db;

class GoodsCurrencyPriceService
{
    protected $catalog;

    public function __construct()
    {
        $this->catalog = new CurrencyCatalogService();
    }

    public function normalizePriceCollection($prices)
    {
        if (is_string($prices)) {
            $decoded = json_decode($prices, true);
            if (!is_array($decoded)) {
                throw new \InvalidArgumentException('currency_prices格式错误');
            }
            $prices = $decoded;
        }
        if (!is_array($prices)) {
            throw new \InvalidArgumentException('currency_prices必须是数组');
        }

        $result = [];
        foreach ($prices as $key => $price) {
            if (!is_array($price)) {
                throw new \InvalidArgumentException('币种价格项格式错误');
            }
            $currencyCode = isset($price['currency_code']) ? $price['currency_code'] : $key;
            $currencyCode = $this->catalog->normalizeCode($currencyCode);
            unset($price['currency_code']);
            $result[$currencyCode] = $price;
        }
        return $result;
    }

    public function getPrices($gId)
    {
        return Db::name('goods_currency_price')->alias('p')
            ->leftJoin('currency_info c', 'c.currency_code=p.currency_code')
            ->where('p.g_id', intval($gId))
            ->field('p.g_id,p.currency_code,p.cost_price,p.market_price,p.retail_price,p.created_at,p.updated_at,c.currency_name,c.currency_symbol,c.decimal_places,c.status')
            ->order('c.sort asc,p.currency_code asc')
            ->select()
            ->toArray();
    }

    public function getPriceMapByGoodsIds(array $gIds)
    {
        $gIds = array_values(array_unique(array_filter(array_map('intval', $gIds))));
        $map = [];
        if (!$gIds) {
            return $map;
        }
        $rows = Db::name('goods_currency_price')->alias('p')
            ->leftJoin('currency_info c', 'c.currency_code=p.currency_code')
            ->whereIn('p.g_id', $gIds)
            ->field('p.g_id,p.currency_code,p.cost_price,p.market_price,p.retail_price,p.created_at,p.updated_at,c.currency_name,c.currency_symbol,c.decimal_places,c.status')
            ->order('c.sort asc,p.currency_code asc')
            ->select()
            ->toArray();
        foreach ($rows as $row) {
            $map[intval($row['g_id'])][] = $row;
        }
        return $map;
    }

    /**
     * 保存核心商品各币种价格事实；本方法不会自动下发到设备商品或货道。
     * CNY 同时回写 goods 旧三价，以兼容尚未改造的内部查询链路。
     */
    public function savePrices($gId, $prices, $operatorId = 0, $requireCny = false, $manageTransaction = true)
    {
        $gId = intval($gId);
        if ($gId <= 0 || !Db::name('goods')->where('g_id', $gId)->value('g_id')) {
            throw new \InvalidArgumentException('商品不存在');
        }
        $prices = $this->normalizePriceCollection($prices);
        if (!$prices) {
            throw new \InvalidArgumentException('至少提交一个币种价格');
        }
        if ($requireCny && !isset($prices['CNY'])) {
            throw new \InvalidArgumentException('新商品必须配置CNY三价');
        }

        $save = function () use ($gId, $prices, $operatorId) {
            foreach ($prices as $currencyCode => $priceInput) {
                $this->catalog->assertEnabled($currencyCode);
                $existing = Db::name('goods_currency_price')
                    ->where(['g_id' => $gId, 'currency_code' => $currencyCode])
                    ->lock(true)
                    ->find();
                $price = CurrencyPriceSupport::normalizePriceRow($priceInput, $existing ?: []);
                $data = array_merge($price, [
                    'g_id' => $gId,
                    'currency_code' => $currencyCode,
                    'update_id' => intval($operatorId),
                ]);
                if ($existing) {
                    Db::name('goods_currency_price')->where('gcp_id', $existing['gcp_id'])->update($data);
                } else {
                    $data['creator'] = intval($operatorId);
                    Db::name('goods_currency_price')->insert($data);
                }
                if ($currencyCode === 'CNY') {
                    Db::name('goods')->where('g_id', $gId)->update(array_merge($price, ['update_id' => intval($operatorId)]));
                }
            }
            return $this->getPrices($gId);
        };

        if ($manageTransaction) {
            return Db::transaction($save);
        }
        return $save();
    }
}
