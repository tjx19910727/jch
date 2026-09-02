<?php

namespace app\AppFactory\Kernel\Service\Currency;

use app\AppFactory\Kernel\Support\Currency\CurrencyPriceSupport;
use think\facade\Db;

class MachineCurrencyPriceService
{
    protected $catalog;
    protected $syncLimit;

    public function __construct()
    {
        $this->catalog = new CurrencyCatalogService();
        $this->syncLimit = intval(config('currency.manual_sync_limit')) ?: 200;
    }

    public function getMachineCurrency($mId, $lock = false)
    {
        $query = Db::name('machine_config')->where('m_id', intval($mId));
        if ($lock) {
            $query->lock(true);
        }
        $config = $query->field('mc_id,m_id,machine_id,currency_code,currency_version,is_multi_goods')->find();
        if (!$config) {
            throw new \InvalidArgumentException('设备配置不存在');
        }
        $config['currency_code'] = $this->catalog->normalizeCode($config['currency_code'] ?: 'CNY');
        $config['currency_version'] = max(1, intval($config['currency_version']));
        return $config;
    }

    public function getMachineGoodsPriceMap(array $mgIds, $currencyCode)
    {
        $currencyCode = $this->catalog->normalizeCode($currencyCode);
        $mgIds = array_values(array_unique(array_filter(array_map('intval', $mgIds))));
        if (!$mgIds) {
            return [];
        }
        $rows = Db::name('machine_goods_currency_price')
            ->whereIn('mg_id', $mgIds)
            ->where('currency_code', $currencyCode)
            ->select()
            ->toArray();
        $map = [];
        foreach ($rows as $row) {
            $map[intval($row['mg_id'])] = $row;
        }
        return $map;
    }

    public function getMachineChannelPriceMap(array $mcIds, $currencyCode)
    {
        $currencyCode = $this->catalog->normalizeCode($currencyCode);
        $mcIds = array_values(array_unique(array_filter(array_map('intval', $mcIds))));
        if (!$mcIds) {
            return [];
        }
        $rows = Db::name('machine_channel_currency_price')
            ->whereIn('mc_id', $mcIds)
            ->where('currency_code', $currencyCode)
            ->select()
            ->toArray();
        $map = [];
        foreach ($rows as $row) {
            $map[intval($row['mc_id'])] = $row;
        }
        return $map;
    }

    /**
     * 保存设备商品指定币种价格。
     * 非当前币种只写事实表；当前币种还需更新活跃快照，并仅递增一次快照版本。
     */
    public function saveMachineGoodsPrice($mId, $mgId, $currencyCode, array $priceInput, $operatorId = 0)
    {
        $currencyCode = $this->catalog->normalizeCode($currencyCode);
        $this->catalog->assertEnabled($currencyCode);
        return Db::transaction(function () use ($mId, $mgId, $currencyCode, $priceInput, $operatorId) {
            $config = $this->getMachineCurrency($mId, true);
            $mg = Db::name('machine_goods')->where(['m_id' => intval($mId), 'mg_id' => intval($mgId)])->lock(true)->find();
            if (!$mg) {
                throw new \InvalidArgumentException('设备商品不存在或不属于当前设备');
            }
            $existing = Db::name('machine_goods_currency_price')
                ->where(['mg_id' => intval($mgId), 'currency_code' => $currencyCode])
                ->lock(true)
                ->find();
            $price = CurrencyPriceSupport::normalizePriceRow($priceInput, $existing ?: []);
            $factChanged = $this->upsertMachineGoodsPrice($mg, $currencyCode, $price, $operatorId, $existing);
            $snapshotChanged = false;
            if ($currencyCode === $config['currency_code'] && !CurrencyPriceSupport::pricesEqual($mg, $price)) {
                Db::name('machine_goods')->where('mg_id', intval($mgId))->update($price);
                $snapshotChanged = true;
            }
            $version = $config['currency_version'];
            if ($snapshotChanged) {
                $version = $this->bumpCurrencyVersion($mId);
            }
            return $this->buildResult($config, $currencyCode, $version, $factChanged ? 1 : 0, $factChanged ? 0 : 1, $snapshotChanged);
        });
    }

    /**
     * 保存普通单商品货道指定币种价格，货道之间保持独立，不联动同商品的其他货道。
     */
    public function saveMachineChannelPrice($mId, $mcId, $currencyCode, array $priceInput, $operatorId = 0, $manageTransaction = true)
    {
        $currencyCode = $this->catalog->normalizeCode($currencyCode);
        $this->catalog->assertEnabled($currencyCode);
        $save = function () use ($mId, $mcId, $currencyCode, $priceInput, $operatorId) {
            $config = $this->getMachineCurrency($mId, true);
            $mc = Db::name('machine_channel')->where(['m_id' => intval($mId), 'mc_id' => intval($mcId)])->lock(true)->find();
            $this->assertOrdinaryChannel($mc);
            $existing = Db::name('machine_channel_currency_price')
                ->where(['mc_id' => intval($mcId), 'currency_code' => $currencyCode])
                ->lock(true)
                ->find();
            $price = CurrencyPriceSupport::normalizePriceRow($priceInput, $existing ?: []);
            $factChanged = $this->upsertMachineChannelPrice($mc, $currencyCode, $price, $operatorId, $existing);
            $snapshotChanged = false;
            if ($currencyCode === $config['currency_code'] && !CurrencyPriceSupport::pricesEqual($mc, $price)) {
                Db::name('machine_channel')->where('mc_id', intval($mcId))->update($price);
                $snapshotChanged = true;
            }
            $version = $config['currency_version'];
            if ($snapshotChanged) {
                $version = $this->bumpCurrencyVersion($mId);
            }
            return $this->buildResult($config, $currencyCode, $version, $factChanged ? 1 : 0, $factChanged ? 0 : 1, $snapshotChanged);
        };
        return $manageTransaction ? Db::transaction($save) : $save();
    }

    /**
     * 将选中的核心商品币种价格人工同步到设备商品，不通过定时任务或消息队列自动传播。
     */
    public function syncMachineGoods($mId, $currencyCode, $mgIds, $operatorId = 0, $manageTransaction = true)
    {
        $currencyCode = $this->catalog->normalizeCode($currencyCode);
        $this->catalog->assertEnabled($currencyCode);
        $mgIds = CurrencyPriceSupport::normalizeIds($mgIds, $this->syncLimit);
        $save = function () use ($mId, $currencyCode, $mgIds, $operatorId) {
            $config = $this->getMachineCurrency($mId, true);
            $rows = Db::name('machine_goods')->where('m_id', intval($mId))->whereIn('mg_id', $mgIds)->lock(true)->select()->toArray();
            if (count($rows) !== count($mgIds)) {
                throw new \InvalidArgumentException('选中的设备商品包含不存在或不属于当前设备的记录');
            }
            $gIds = array_values(array_unique(array_map(function ($row) {
                return intval($row['g_id']);
            }, $rows)));
            $sourceRows = Db::name('goods_currency_price')->whereIn('g_id', $gIds)->where('currency_code', $currencyCode)->lock(true)->select()->toArray();
            $sourceMap = [];
            foreach ($sourceRows as $row) {
                $sourceMap[intval($row['g_id'])] = $row;
            }
            $missing = array_values(array_diff($gIds, array_keys($sourceMap)));
            if ($missing) {
                throw new \InvalidArgumentException('核心商品缺少' . $currencyCode . '价格：' . implode(',', $missing));
            }

            $changed = 0;
            $unchanged = 0;
            $snapshotChanged = false;
            foreach ($rows as $mg) {
                $price = CurrencyPriceSupport::normalizePriceRow($sourceMap[intval($mg['g_id'])]);
                $existing = Db::name('machine_goods_currency_price')
                    ->where(['mg_id' => intval($mg['mg_id']), 'currency_code' => $currencyCode])
                    ->lock(true)
                    ->find();
                $factChanged = $this->upsertMachineGoodsPrice($mg, $currencyCode, $price, $operatorId, $existing);
                if ($factChanged) {
                    $changed++;
                } else {
                    $unchanged++;
                }
                if ($currencyCode === $config['currency_code'] && !CurrencyPriceSupport::pricesEqual($mg, $price)) {
                    Db::name('machine_goods')->where('mg_id', intval($mg['mg_id']))->update($price);
                    $snapshotChanged = true;
                }
            }
            $version = $snapshotChanged ? $this->bumpCurrencyVersion($mId) : $config['currency_version'];
            return $this->buildResult($config, $currencyCode, $version, $changed, $unchanged, $snapshotChanged);
        };
        return $manageTransaction ? Db::transaction($save) : $save();
    }

    /**
     * 将选中设备商品的币种价格人工同步到对应普通货道。
     */
    public function syncMachineChannels($mId, $currencyCode, $mcIds, $operatorId = 0)
    {
        $currencyCode = $this->catalog->normalizeCode($currencyCode);
        $this->catalog->assertEnabled($currencyCode);
        $mcIds = CurrencyPriceSupport::normalizeIds($mcIds, $this->syncLimit);
        return Db::transaction(function () use ($mId, $currencyCode, $mcIds, $operatorId) {
            $config = $this->getMachineCurrency($mId, true);
            $rows = Db::name('machine_channel')->where('m_id', intval($mId))->whereIn('mc_id', $mcIds)->lock(true)->select()->toArray();
            if (count($rows) !== count($mcIds)) {
                throw new \InvalidArgumentException('选中的货道包含不存在或不属于当前设备的记录');
            }
            $mgIds = [];
            foreach ($rows as $mc) {
                $this->assertOrdinaryChannel($mc);
                $mgIds[] = intval($mc['mg_id']);
            }
            $sourceRows = Db::name('machine_goods_currency_price')
                ->whereIn('mg_id', array_values(array_unique($mgIds)))
                ->where(['m_id' => intval($mId), 'currency_code' => $currencyCode])
                ->lock(true)
                ->select()
                ->toArray();
            $sourceMap = [];
            foreach ($sourceRows as $row) {
                $sourceMap[intval($row['mg_id'])] = $row;
            }

            $missing = [];
            foreach ($rows as $mc) {
                $source = isset($sourceMap[intval($mc['mg_id'])]) ? $sourceMap[intval($mc['mg_id'])] : null;
                if (!$source || intval($source['g_id']) !== intval($mc['g_id'])) {
                    $missing[] = intval($mc['mc_id']);
                }
            }
            if ($missing) {
                throw new \InvalidArgumentException('货道缺少对应设备商品' . $currencyCode . '价格：' . implode(',', $missing));
            }

            $changed = 0;
            $unchanged = 0;
            $snapshotChanged = false;
            foreach ($rows as $mc) {
                $price = CurrencyPriceSupport::normalizePriceRow($sourceMap[intval($mc['mg_id'])]);
                $existing = Db::name('machine_channel_currency_price')
                    ->where(['mc_id' => intval($mc['mc_id']), 'currency_code' => $currencyCode])
                    ->lock(true)
                    ->find();
                $factChanged = $this->upsertMachineChannelPrice($mc, $currencyCode, $price, $operatorId, $existing);
                if ($factChanged) {
                    $changed++;
                } else {
                    $unchanged++;
                }
                if ($currencyCode === $config['currency_code'] && !CurrencyPriceSupport::pricesEqual($mc, $price)) {
                    Db::name('machine_channel')->where('mc_id', intval($mc['mc_id']))->update($price);
                    $snapshotChanged = true;
                }
            }
            $version = $snapshotChanged ? $this->bumpCurrencyVersion($mId) : $config['currency_version'];
            return $this->buildResult($config, $currencyCode, $version, $changed, $unchanged, $snapshotChanged);
        });
    }

    /**
     * 一个事务内无论更新多少条当前币种价格，设备快照版本只递增一次。
     */
    public function bumpCurrencyVersion($mId)
    {
        Db::name('machine_config')->where('m_id', intval($mId))->inc('currency_version', 1)->update();
        return intval(Db::name('machine_config')->where('m_id', intval($mId))->value('currency_version'));
    }

    protected function upsertMachineGoodsPrice(array $mg, $currencyCode, array $price, $operatorId, $existing = null)
    {
        $identityChanged = $existing && (intval($existing['m_id']) !== intval($mg['m_id']) || intval($existing['g_id']) !== intval($mg['g_id']));
        $priceChanged = !$existing || !CurrencyPriceSupport::pricesEqual($existing, $price);
        if (!$identityChanged && !$priceChanged) {
            return false;
        }
        $data = array_merge($price, [
            'mg_id' => intval($mg['mg_id']),
            'm_id' => intval($mg['m_id']),
            'g_id' => intval($mg['g_id']),
            'currency_code' => $currencyCode,
            'update_id' => intval($operatorId),
        ]);
        if ($existing) {
            Db::name('machine_goods_currency_price')->where('mgcp_id', $existing['mgcp_id'])->update($data);
        } else {
            $data['creator'] = intval($operatorId);
            Db::name('machine_goods_currency_price')->insert($data);
        }
        return true;
    }

    protected function upsertMachineChannelPrice(array $mc, $currencyCode, array $price, $operatorId, $existing = null)
    {
        $identityChanged = $existing && (
            intval($existing['m_id']) !== intval($mc['m_id'])
            || intval($existing['mg_id']) !== intval($mc['mg_id'])
            || intval($existing['g_id']) !== intval($mc['g_id'])
        );
        $priceChanged = !$existing || !CurrencyPriceSupport::pricesEqual($existing, $price);
        if (!$identityChanged && !$priceChanged) {
            return false;
        }
        $data = array_merge($price, [
            'mc_id' => intval($mc['mc_id']),
            'm_id' => intval($mc['m_id']),
            'mg_id' => intval($mc['mg_id']),
            'g_id' => intval($mc['g_id']),
            'currency_code' => $currencyCode,
            'update_id' => intval($operatorId),
        ]);
        if ($existing) {
            Db::name('machine_channel_currency_price')->where('mccp_id', $existing['mccp_id'])->update($data);
        } else {
            $data['creator'] = intval($operatorId);
            Db::name('machine_channel_currency_price')->insert($data);
        }
        return true;
    }

    protected function assertOrdinaryChannel($mc)
    {
        if (!$mc) {
            throw new \InvalidArgumentException('货道不存在或不属于当前设备');
        }
        if (intval($mc['g_id']) <= 0 || intval($mc['mg_id']) <= 0) {
            throw new \InvalidArgumentException('货道未绑定有效设备商品');
        }
        if (intval(isset($mc['is_multi_goods']) ? $mc['is_multi_goods'] : 2) === 1) {
            throw new \InvalidArgumentException('本期不支持单货道多商品的多币种价格');
        }
        // 历史批次数据也视为多商品货道，避免缺少 batch_id 维度时发生串价。
        $batchCount = Db::name('channel_goods_batch')->where('mc_id', intval($mc['mc_id']))->count();
        if ($batchCount > 0) {
            throw new \InvalidArgumentException('货道存在多商品批次，本期不支持同步币种价格');
        }
    }

    protected function buildResult(array $config, $currencyCode, $version, $changed, $unchanged, $snapshotChanged)
    {
        return [
            'm_id' => intval($config['m_id']),
            'machine_id' => $config['machine_id'],
            'currency_code' => $currencyCode,
            'active_currency_code' => $config['currency_code'],
            'currency_version' => intval($version),
            'changed_count' => intval($changed),
            'unchanged_count' => intval($unchanged),
            'active_snapshot_changed' => $snapshotChanged ? 1 : 0,
        ];
    }
}
