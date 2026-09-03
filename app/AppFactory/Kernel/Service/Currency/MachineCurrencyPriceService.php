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
        $this->syncLimit = 200; // 单次人工同步条数上限（manual_sync_limit 配置随弃用接口一并移除）
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
            if (!$existing && CurrencyPriceSupport::isZeroPrice($price)) {
                throw new \InvalidArgumentException('该币种价格尚未配置，不能直接保存全为0的三价，请填写真实三价后提交');
            }
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
            if (!$existing && CurrencyPriceSupport::isZeroPrice($price)) {
                throw new \InvalidArgumentException('该币种价格尚未配置，不能直接保存全为0的三价，请填写真实三价后提交');
            }
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
        $result = $this->syncMachineGoodsCurrencies($mId, [$currencyCode], $mgIds, $operatorId, $manageTransaction);
        return $result['currency_results'][$currencyCode];
    }

    /**
     * 将选中设备商品的多个币种价格一次性从核心商品同步。
     * 所有“设备商品 × 币种”组合共用一个事务，任一来源缺价都会整批回滚。
     */
    public function syncMachineGoodsCurrencies($mId, $currencyCodes, $mgIds, $operatorId = 0, $manageTransaction = true)
    {
        $currencyCodes = $this->normalizeEnabledCurrencyCodes($currencyCodes);
        $mgIds = CurrencyPriceSupport::normalizeIds($mgIds, $this->syncLimit);
        $save = function () use ($mId, $currencyCodes, $mgIds, $operatorId) {
            $config = $this->getMachineCurrency($mId, true);
            $rows = Db::name('machine_goods')->where('m_id', intval($mId))->whereIn('mg_id', $mgIds)->lock(true)->select()->toArray();
            if (count($rows) !== count($mgIds)) {
                throw new \InvalidArgumentException('选中的设备商品包含不存在或不属于当前设备的记录');
            }
            $gIds = array_values(array_unique(array_map(function ($row) {
                return intval($row['g_id']);
            }, $rows)));
            $sourceRows = Db::name('goods_currency_price')
                ->whereIn('g_id', $gIds)
                ->whereIn('currency_code', $currencyCodes)
                ->lock(true)
                ->select()
                ->toArray();
            $sourceMap = [];
            foreach ($sourceRows as $row) {
                $sourceMap[$row['currency_code']][intval($row['g_id'])] = $row;
            }
            $missing = [];
            foreach ($currencyCodes as $currencyCode) {
                $missingIds = array_values(array_diff($gIds, array_keys(isset($sourceMap[$currencyCode]) ? $sourceMap[$currencyCode] : [])));
                if ($missingIds) {
                    $missing[] = $currencyCode . '(g_id:' . implode(',', $missingIds) . ')';
                }
            }
            if ($missing) {
                throw new \InvalidArgumentException('核心商品缺少币种价格：' . implode('；', $missing));
            }

            $existingRows = Db::name('machine_goods_currency_price')
                ->whereIn('mg_id', $mgIds)
                ->whereIn('currency_code', $currencyCodes)
                ->lock(true)
                ->select()
                ->toArray();
            $existingMap = [];
            foreach ($existingRows as $existing) {
                $existingMap[$existing['currency_code']][intval($existing['mg_id'])] = $existing;
            }

            $counts = [];
            $snapshotChanged = false;
            foreach ($currencyCodes as $currencyCode) {
                $counts[$currencyCode] = ['changed' => 0, 'unchanged' => 0, 'snapshot_changed' => false];
                foreach ($rows as $mg) {
                    $mgId = intval($mg['mg_id']);
                    $price = CurrencyPriceSupport::normalizePriceRow($sourceMap[$currencyCode][intval($mg['g_id'])]);
                    $existing = isset($existingMap[$currencyCode][$mgId]) ? $existingMap[$currencyCode][$mgId] : null;
                    $factChanged = $this->upsertMachineGoodsPrice($mg, $currencyCode, $price, $operatorId, $existing);
                    if ($factChanged) {
                        $counts[$currencyCode]['changed']++;
                    } else {
                        $counts[$currencyCode]['unchanged']++;
                    }
                    if ($currencyCode === $config['currency_code'] && !CurrencyPriceSupport::pricesEqual($mg, $price)) {
                        Db::name('machine_goods')->where('mg_id', $mgId)->update($price);
                        $counts[$currencyCode]['snapshot_changed'] = true;
                        $snapshotChanged = true;
                    }
                }
            }
            $version = $snapshotChanged ? $this->bumpCurrencyVersion($mId) : $config['currency_version'];
            return $this->buildBatchResult($config, $currencyCodes, $version, $counts, $snapshotChanged);
        };
        return $manageTransaction ? Db::transaction($save) : $save();
    }

    /**
     * 将选中货道的一个币种价格从设备商品同步（兼容薄壳）。
     */
    public function syncMachineChannels($mId, $currencyCode, $mcIds, $operatorId = 0, $manageTransaction = true)
    {
        $currencyCode = $this->catalog->normalizeCode($currencyCode);
        $result = $this->syncMachineChannelCurrencies($mId, [$currencyCode], $mcIds, $operatorId, $manageTransaction);
        return $result['currency_results'][$currencyCode];
    }

    /**
     * 将选中普通单商品货道的多个币种价格一次性从设备商品同步。
     * 数据源必须是 machine_goods_currency_price 对应币种行，禁止回退 goods 旧三价；
     * 全部“货道 × 币种”组合共用一个事务，任一缺价或存在多商品货道都整批回滚。
     */
    public function syncMachineChannelCurrencies($mId, $currencyCodes, $mcIds, $operatorId = 0, $manageTransaction = true)
    {
        $currencyCodes = $this->normalizeEnabledCurrencyCodes($currencyCodes);
        $mcIds = CurrencyPriceSupport::normalizeIds($mcIds, $this->syncLimit);
        $save = function () use ($mId, $currencyCodes, $mcIds, $operatorId) {
            $config = $this->getMachineCurrency($mId, true);
            $rows = Db::name('machine_channel')->where('m_id', intval($mId))->whereIn('mc_id', $mcIds)->lock(true)->select()->toArray();
            if (count($rows) !== count($mcIds)) {
                throw new \InvalidArgumentException('选中的货道包含不存在或不属于当前设备的记录');
            }
            $this->assertOrdinaryChannels($rows);
            $mgIds = array_values(array_unique(array_map(function ($row) {
                return intval($row['mg_id']);
            }, $rows)));
            $sourceRows = Db::name('machine_goods_currency_price')
                ->whereIn('mg_id', $mgIds)
                ->where('m_id', intval($mId))
                ->whereIn('currency_code', $currencyCodes)
                ->lock(true)
                ->select()
                ->toArray();
            $sourceMap = [];
            foreach ($sourceRows as $row) {
                $sourceMap[$row['currency_code']][intval($row['mg_id'])] = $row;
            }
            $missing = [];
            foreach ($rows as $mc) {
                foreach ($currencyCodes as $currencyCode) {
                    $source = isset($sourceMap[$currencyCode][intval($mc['mg_id'])]) ? $sourceMap[$currencyCode][intval($mc['mg_id'])] : null;
                    if (!$source || intval($source['g_id']) !== intval($mc['g_id'])) {
                        $missing[] = $currencyCode . '(mc_id:' . intval($mc['mc_id']) . ')';
                    }
                }
            }
            if ($missing) {
                throw new \InvalidArgumentException('货道缺少对应设备商品币种价格：' . implode('；', $missing));
            }

            $existingRows = Db::name('machine_channel_currency_price')
                ->whereIn('mc_id', $mcIds)
                ->whereIn('currency_code', $currencyCodes)
                ->lock(true)
                ->select()
                ->toArray();
            $existingMap = [];
            foreach ($existingRows as $existing) {
                $existingMap[$existing['currency_code']][intval($existing['mc_id'])] = $existing;
            }

            $counts = [];
            $snapshotChanged = false;
            foreach ($currencyCodes as $currencyCode) {
                $counts[$currencyCode] = ['changed' => 0, 'unchanged' => 0, 'snapshot_changed' => false];
                foreach ($rows as $mc) {
                    $mcId = intval($mc['mc_id']);
                    $price = CurrencyPriceSupport::normalizePriceRow($sourceMap[$currencyCode][intval($mc['mg_id'])]);
                    $existing = isset($existingMap[$currencyCode][$mcId]) ? $existingMap[$currencyCode][$mcId] : null;
                    $factChanged = $this->upsertMachineChannelPrice($mc, $currencyCode, $price, $operatorId, $existing);
                    if ($factChanged) {
                        $counts[$currencyCode]['changed']++;
                    } else {
                        $counts[$currencyCode]['unchanged']++;
                    }
                    if ($currencyCode === $config['currency_code'] && !CurrencyPriceSupport::pricesEqual($mc, $price)) {
                        Db::name('machine_channel')->where('mc_id', $mcId)->update($price);
                        $counts[$currencyCode]['snapshot_changed'] = true;
                        $snapshotChanged = true;
                    }
                }
            }
            $version = $snapshotChanged ? $this->bumpCurrencyVersion($mId) : $config['currency_version'];
            return $this->buildBatchResult($config, $currencyCodes, $version, $counts, $snapshotChanged);
        };
        return $manageTransaction ? Db::transaction($save) : $save();
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

    /**
     * 按设备当前币种自动同步：核心商品三价 → 选中设备商品 → 其在本机的普通单商品货道。
     * 两级共用同一事务，只以设备当前币种（machine_config.currency_code，空则按 CNY）为同步币种，
     * 设备商品/货道任一缺价或多商品货道时整批回滚；当前币种活跃快照变化时版本只递增一次、通知一次。
     */
    public function syncMachineGoodsChannelsByDeviceCurrency($mId, $mgIds, $operatorId = 0, $manageTransaction = true)
    {
        $mgIds = CurrencyPriceSupport::normalizeIds($mgIds, $this->syncLimit);
        $save = function () use ($mId, $mgIds, $operatorId) {
            $config = $this->getMachineCurrency($mId, true);
            $currencyCode = $config['currency_code'];
            $this->catalog->assertEnabled($currencyCode);

            $rows = Db::name('machine_goods')->where('m_id', intval($mId))->whereIn('mg_id', $mgIds)->lock(true)->select()->toArray();
            if (count($rows) !== count($mgIds)) {
                throw new \InvalidArgumentException('选中的设备商品包含不存在或不属于当前设备的记录');
            }
            $gIds = array_values(array_unique(array_map(function ($row) {
                return intval($row['g_id']);
            }, $rows)));
            $sourceRows = Db::name('goods_currency_price')
                ->whereIn('g_id', $gIds)
                ->where('currency_code', $currencyCode)
                ->lock(true)
                ->select()
                ->toArray();
            $sourceMap = [];
            foreach ($sourceRows as $row) {
                $sourceMap[intval($row['g_id'])] = $row;
            }
            $missing = array_values(array_diff($gIds, array_keys($sourceMap)));
            if ($missing) {
                throw new \InvalidArgumentException('核心商品缺少' . $currencyCode . '价格：' . implode(',', $missing));
            }

            // 第一级：核心 → 设备商品
            $existingRows = Db::name('machine_goods_currency_price')
                ->whereIn('mg_id', $mgIds)
                ->where('currency_code', $currencyCode)
                ->lock(true)
                ->select()
                ->toArray();
            $existingMap = [];
            foreach ($existingRows as $existing) {
                $existingMap[intval($existing['mg_id'])] = $existing;
            }
            $devicePriceByMg = [];
            $snapshotChanged = false;
            $mgChanged = 0;
            $mgUnchanged = 0;
            foreach ($rows as $mg) {
                $mgId = intval($mg['mg_id']);
                $price = CurrencyPriceSupport::normalizePriceRow($sourceMap[intval($mg['g_id'])]);
                $existing = isset($existingMap[$mgId]) ? $existingMap[$mgId] : null;
                if ($this->upsertMachineGoodsPrice($mg, $currencyCode, $price, $operatorId, $existing)) {
                    $mgChanged++;
                } else {
                    $mgUnchanged++;
                }
                $devicePriceByMg[$mgId] = $price;
                if (!CurrencyPriceSupport::pricesEqual($mg, $price)) {
                    Db::name('machine_goods')->where('mg_id', $mgId)->update($price);
                    $snapshotChanged = true;
                }
            }

            // 第二级：设备商品 → 其在本机的普通单商品货道
            $channels = Db::name('machine_channel')
                ->where('m_id', intval($mId))
                ->whereIn('mg_id', $mgIds)
                ->lock(true)
                ->select()
                ->toArray();
            $mcChanged = 0;
            $mcUnchanged = 0;
            if ($channels) {
                $this->assertOrdinaryChannels($channels);
                $mcIds = array_values(array_unique(array_map(function ($row) {
                    return intval($row['mc_id']);
                }, $channels)));
                $channelExistingRows = Db::name('machine_channel_currency_price')
                    ->whereIn('mc_id', $mcIds)
                    ->where('currency_code', $currencyCode)
                    ->lock(true)
                    ->select()
                    ->toArray();
                $channelExistingMap = [];
                foreach ($channelExistingRows as $existing) {
                    $channelExistingMap[intval($existing['mc_id'])] = $existing;
                }
                foreach ($channels as $mc) {
                    $mcId = intval($mc['mc_id']);
                    $price = isset($devicePriceByMg[intval($mc['mg_id'])]) ? $devicePriceByMg[intval($mc['mg_id'])] : null;
                    if (!$price) {
                        throw new \InvalidArgumentException('货道缺少对应设备商品' . $currencyCode . '价格：' . $mcId);
                    }
                    $existing = isset($channelExistingMap[$mcId]) ? $channelExistingMap[$mcId] : null;
                    if ($this->upsertMachineChannelPrice($mc, $currencyCode, $price, $operatorId, $existing)) {
                        $mcChanged++;
                    } else {
                        $mcUnchanged++;
                    }
                    if (!CurrencyPriceSupport::pricesEqual($mc, $price)) {
                        Db::name('machine_channel')->where('mc_id', $mcId)->update($price);
                        $snapshotChanged = true;
                    }
                }
            }

            $version = $snapshotChanged ? $this->bumpCurrencyVersion($mId) : $config['currency_version'];
            $currency = $this->catalog->getByCode($currencyCode);
            return [
                'm_id' => intval($config['m_id']),
                'machine_id' => $config['machine_id'],
                'currency_code' => $currencyCode,
                'currency_symbol' => isset($currency['currency_symbol']) ? $currency['currency_symbol'] : '',
                'active_currency_code' => $currencyCode,
                'currency_version' => intval($version),
                'machine_goods_changed' => $mgChanged,
                'machine_goods_unchanged' => $mgUnchanged,
                'machine_channel_changed' => $mcChanged,
                'machine_channel_unchanged' => $mcUnchanged,
                'active_snapshot_changed' => $snapshotChanged ? 1 : 0,
            ];
        };
        return $manageTransaction ? Db::transaction($save) : $save();
    }
    /**
     * 批量校验货道均为普通单商品货道：绑定有效设备商品、非多商品模式、无历史多商品批次。
     */
    protected function assertOrdinaryChannels(array $rows)
    {
        foreach ($rows as $mc) {
            if (intval($mc['g_id']) <= 0 || intval($mc['mg_id']) <= 0) {
                throw new \InvalidArgumentException('货道未绑定有效设备商品');
            }
            if (intval(isset($mc['is_multi_goods']) ? $mc['is_multi_goods'] : 2) === 1) {
                throw new \InvalidArgumentException('本期不支持单货道多商品的多币种价格');
            }
        }
        $mcIds = array_values(array_unique(array_map(function ($row) {
            return intval($row['mc_id']);
        }, $rows)));
        $batchCount = Db::name('channel_goods_batch')->whereIn('mc_id', $mcIds)->count();
        if ($batchCount > 0) {
            throw new \InvalidArgumentException('货道存在多商品批次，本期不支持同步币种价格');
        }
    }
    protected function normalizeEnabledCurrencyCodes($currencyCodes)
    {
        $currencyCodes = CurrencyPriceSupport::normalizeCurrencyCodes($currencyCodes);
        foreach ($currencyCodes as $currencyCode) {
            $this->catalog->assertEnabled($currencyCode);
        }
        return $currencyCodes;
    }

    protected function buildBatchResult(array $config, array $currencyCodes, $version, array $counts, $snapshotChanged)
    {
        $currencyResults = [];
        $changed = 0;
        $unchanged = 0;
        foreach ($currencyCodes as $currencyCode) {
            $currencyChanged = intval($counts[$currencyCode]['changed']);
            $currencyUnchanged = intval($counts[$currencyCode]['unchanged']);
            $currencySnapshotChanged = !empty($counts[$currencyCode]['snapshot_changed']);
            $currencyResults[$currencyCode] = $this->buildResult(
                $config,
                $currencyCode,
                $version,
                $currencyChanged,
                $currencyUnchanged,
                $currencySnapshotChanged
            );
            $changed += $currencyChanged;
            $unchanged += $currencyUnchanged;
        }
        return [
            'm_id' => intval($config['m_id']),
            'machine_id' => $config['machine_id'],
            'currency_codes' => $currencyCodes,
            'active_currency_code' => $config['currency_code'],
            'currency_version' => intval($version),
            'changed_count' => $changed,
            'unchanged_count' => $unchanged,
            'active_snapshot_changed' => $snapshotChanged ? 1 : 0,
            'currency_results' => $currencyResults,
        ];
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
