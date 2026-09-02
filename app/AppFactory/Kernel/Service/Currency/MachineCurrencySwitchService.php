<?php

namespace app\AppFactory\Kernel\Service\Currency;

use app\AppFactory\Kernel\Support\Currency\CurrencyPriceSupport;
use think\facade\Db;

class MachineCurrencySwitchService
{
    protected $catalog;
    protected $stateTtl;

    public function __construct()
    {
        $this->catalog = new CurrencyCatalogService();
        $this->stateTtl = intval(config('currency.switch_state_ttl')) ?: 120;
    }

    /**
     * 缓存设备短时空闲状态；切币时必须使用有效期内的设备现场状态，不能只看后台在线标记。
     */
    public function reportDeviceState($mId, array $state)
    {
        $supportedCodes = isset($state['supported_currency_codes']) ? $state['supported_currency_codes'] : [];
        if (is_string($supportedCodes)) {
            $decoded = json_decode($supportedCodes, true);
            $supportedCodes = is_array($decoded) ? $decoded : explode(',', $supportedCodes);
        }
        $codes = [];
        foreach ((array)$supportedCodes as $code) {
            try {
                $codes[] = $this->catalog->normalizeCode($code);
            } catch (\InvalidArgumentException $e) {
                continue;
            }
        }
        $reportTime = intval(isset($state['report_time']) ? $state['report_time'] : time());
        if ($reportTime <= 0 || abs(time() - $reportTime) > 300) {
            throw new \InvalidArgumentException('设备币种状态时间无效');
        }
        $payload = [
            'm_id' => intval($mId),
            'can_switch_currency' => intval(isset($state['can_switch_currency']) ? $state['can_switch_currency'] : 0) === 1 ? 1 : 0,
            'cart_count' => max(0, intval(isset($state['cart_count']) ? $state['cart_count'] : 0)),
            'pending_order_count' => max(0, intval(isset($state['pending_order_count']) ? $state['pending_order_count'] : 0)),
            'supported_currency_codes' => array_values(array_unique($codes)),
            'report_time' => $reportTime,
            'received_at' => time(),
        ];
        cache($this->stateCacheKey($mId), $payload, $this->stateTtl);
        return $payload;
    }

    /**
     * 汇总切币阻断项。缺价、串价、未完成订单或设备能力不足时均不允许进入切换事务。
     */
    public function readiness($mId, $targetCurrencyCode)
    {
        $mId = intval($mId);
        $targetCurrencyCode = $this->catalog->normalizeCode($targetCurrencyCode);
        $currency = $this->catalog->assertEnabled($targetCurrencyCode);
        $config = Db::name('machine_config')->where('m_id', $mId)
            ->field('mc_id,m_id,machine_id,currency_code,currency_version,is_multi_goods')->find();
        $machine = Db::name('machine')->where('m_id', $mId)
            ->field('m_id,machine_id,online,http_online,current_status,version,status')->find();
        if (!$config || !$machine) {
            throw new \InvalidArgumentException('设备或设备配置不存在');
        }
        $currentCurrencyCode = $this->catalog->normalizeCode($config['currency_code'] ?: 'CNY');
        $result = [
            'm_id' => $mId,
            'machine_id' => $machine['machine_id'],
            'current_currency_code' => $currentCurrencyCode,
            'target_currency_code' => $targetCurrencyCode,
            'target_currency' => $currency,
            'currency_version' => max(1, intval($config['currency_version'])),
            'missing_mg_ids' => [],
            'missing_mc_ids' => [],
            'stale_mc_ids' => [],
            'multi_goods_mc_ids' => [],
            'blockers' => [],
            'ready' => 0,
        ];
        if ($currentCurrencyCode === $targetCurrencyCode) {
            $result['ready'] = 1;
            $result['idempotent'] = 1;
            return $result;
        }

        if (intval($machine['status']) !== 1 || (intval($machine['online']) !== 1 && intval($machine['http_online']) !== 1)) {
            $this->addBlocker($result, 'MACHINE_OFFLINE', '设备不在线，不能切换币种');
        }
        if (!empty($machine['current_status']) && $machine['current_status'] !== 'normal') {
            $this->addBlocker($result, 'MACHINE_BUSY', '设备当前不处于空闲状态');
        }

        $state = cache($this->stateCacheKey($mId));
        if (!is_array($state) || time() - intval(isset($state['received_at']) ? $state['received_at'] : 0) > $this->stateTtl) {
            $this->addBlocker($result, 'DEVICE_STATE_STALE', '设备未上报有效期内的币种切换状态');
        } else {
            $result['device_state'] = $state;
            if (intval($state['can_switch_currency']) !== 1 || intval($state['cart_count']) > 0 || intval($state['pending_order_count']) > 0) {
                $this->addBlocker($result, 'DEVICE_LOCAL_BUSY', '设备存在购物车或本地未完成订单');
            }
            if (!in_array($targetCurrencyCode, (array)$state['supported_currency_codes'], true)) {
                $this->addBlocker($result, 'DEVICE_CURRENCY_UNSUPPORTED', '当前设备版本未声明支持目标币种');
            }
        }

        $serverCodes = array_map('strtoupper', (array)config('currency.server_switch_currency_codes'));
        if (!in_array($targetCurrencyCode, $serverCodes, true)) {
            $this->addBlocker($result, 'SERVER_CAPABILITY_DISABLED', '服务端支付、营销或外部业务尚未启用目标币种能力');
        }

        if ($this->hasPendingServerOrder($mId)) {
            $this->addBlocker($result, 'SERVER_ORDER_PENDING', '设备存在待支付、支付中或出货中的服务端订单');
        }

        $scope = $this->getSnapshotScope($mId, false);
        foreach ($scope['multi_goods'] as $row) {
            $result['multi_goods_mc_ids'][] = intval($row['mc_id']);
        }
        if ($result['multi_goods_mc_ids']) {
            $this->addBlocker($result, 'MULTI_GOODS_UNSUPPORTED', '设备存在单货道多商品，本期不支持币种切换');
        }

        $mgMap = $this->loadMachineGoodsPriceMap($scope['machine_goods'], $targetCurrencyCode);
        foreach ($scope['machine_goods'] as $mg) {
            $price = isset($mgMap[intval($mg['mg_id'])]) ? $mgMap[intval($mg['mg_id'])] : null;
            if (!$price || intval($price['m_id']) !== $mId || intval($price['g_id']) !== intval($mg['g_id'])) {
                $result['missing_mg_ids'][] = intval($mg['mg_id']);
            }
        }
        if ($result['missing_mg_ids']) {
            $this->addBlocker($result, 'MACHINE_GOODS_PRICE_MISSING', '设备商品目标币种价格未准备完整');
        }

        $mcMap = $this->loadMachineChannelPriceMap($scope['machine_channels'], $targetCurrencyCode);
        foreach ($scope['machine_channels'] as $mc) {
            $price = isset($mcMap[intval($mc['mc_id'])]) ? $mcMap[intval($mc['mc_id'])] : null;
            if (!$price) {
                $result['missing_mc_ids'][] = intval($mc['mc_id']);
            } elseif (intval($price['m_id']) !== $mId || intval($price['mg_id']) !== intval($mc['mg_id']) || intval($price['g_id']) !== intval($mc['g_id'])) {
                $result['stale_mc_ids'][] = intval($mc['mc_id']);
            }
        }
        if ($result['missing_mc_ids']) {
            $this->addBlocker($result, 'MACHINE_CHANNEL_PRICE_MISSING', '货道目标币种价格未准备完整');
        }
        if ($result['stale_mc_ids']) {
            $this->addBlocker($result, 'MACHINE_CHANNEL_PRICE_STALE', '货道目标币种价格属于旧商品，请重新同步');
        }

        $result['ready'] = $result['blockers'] ? 0 : 1;
        return $result;
    }

    /**
     * 在单设备事务中锁定配置、设备商品和货道，并把目标币种事实价格投影为活跃售卖快照。
     */
    public function switchCurrency($mId, $targetCurrencyCode)
    {
        $mId = intval($mId);
        $targetCurrencyCode = $this->catalog->normalizeCode($targetCurrencyCode);
        return Db::transaction(function () use ($mId, $targetCurrencyCode) {
            $config = Db::name('machine_config')->where('m_id', $mId)->lock(true)->find();
            if (!$config) {
                throw new \InvalidArgumentException('设备配置不存在');
            }
            $currentCurrencyCode = $this->catalog->normalizeCode($config['currency_code'] ?: 'CNY');
            if ($currentCurrencyCode === $targetCurrencyCode) {
                return [
                    'success' => 1,
                    'idempotent' => 1,
                    'm_id' => $mId,
                    'machine_id' => $config['machine_id'],
                    'currency_code' => $currentCurrencyCode,
                    'currency_version' => max(1, intval($config['currency_version'])),
                ];
            }

            $readiness = $this->readiness($mId, $targetCurrencyCode);
            if (!$readiness['ready']) {
                return ['success' => 0, 'readiness' => $readiness];
            }

            $scope = $this->getSnapshotScope($mId, true);
            // 先确认旧三价仍等于当前币种事实，禁止把被其他链路污染的快照反向当作正确价格。
            $sourceProblem = $this->validateSourceSnapshot($scope, $currentCurrencyCode);
            if ($sourceProblem['mg_ids'] || $sourceProblem['mc_ids']) {
                $readiness['source_mismatch_mg_ids'] = $sourceProblem['mg_ids'];
                $readiness['source_mismatch_mc_ids'] = $sourceProblem['mc_ids'];
                $this->addBlocker($readiness, 'SOURCE_SNAPSHOT_MISMATCH', '当前旧表快照与币种事实价格不一致，禁止反向覆盖');
                $readiness['ready'] = 0;
                return ['success' => 0, 'readiness' => $readiness];
            }

            $mgMap = $this->loadMachineGoodsPriceMap($scope['machine_goods'], $targetCurrencyCode);
            foreach ($scope['machine_goods'] as $mg) {
                $price = $mgMap[intval($mg['mg_id'])];
                Db::name('machine_goods')->where('mg_id', intval($mg['mg_id']))->update($this->priceFields($price));
            }
            $mcMap = $this->loadMachineChannelPriceMap($scope['machine_channels'], $targetCurrencyCode);
            foreach ($scope['machine_channels'] as $mc) {
                $price = $mcMap[intval($mc['mc_id'])];
                Db::name('machine_channel')->where('mc_id', intval($mc['mc_id']))->update($this->priceFields($price));
            }
            Db::name('machine_config')->where('m_id', $mId)->update([
                'currency_code' => $targetCurrencyCode,
                'currency_version' => Db::raw('currency_version + 1'),
            ]);
            $version = intval(Db::name('machine_config')->where('m_id', $mId)->value('currency_version'));
            return [
                'success' => 1,
                'idempotent' => 0,
                'm_id' => $mId,
                'machine_id' => $config['machine_id'],
                'currency_code' => $targetCurrencyCode,
                'currency_version' => $version,
                'machine_goods_count' => count($scope['machine_goods']),
                'machine_channel_count' => count($scope['machine_channels']),
            ];
        });
    }

    /**
     * 返回同一 currency_version 下的完整配置、设备商品和普通货道快照，供设备原子应用。
     */
    public function getActiveSnapshot($mId)
    {
        $mId = intval($mId);
        $config = Db::name('machine_config')->where('m_id', $mId)->find();
        if (!$config) {
            throw new \InvalidArgumentException('设备配置不存在');
        }
        $currencyCode = $this->catalog->normalizeCode($config['currency_code'] ?: 'CNY');
        $currency = $this->catalog->assertEnabled($currencyCode);
        $scope = $this->getSnapshotScope($mId, false);
        return [
            'currency_code' => $currencyCode,
            'currency_version' => max(1, intval($config['currency_version'])),
            'currency' => $currency,
            'machine_config' => $config,
            'machine_goods' => array_values($scope['machine_goods']),
            'machine_channels' => array_values($scope['machine_channels']),
            'counts' => [
                'machine_goods' => count($scope['machine_goods']),
                'machine_channels' => count($scope['machine_channels']),
            ],
        ];
    }

    protected function getSnapshotScope($mId, $lock)
    {
        $mgQuery = Db::name('machine_goods')->where('m_id', intval($mId));
        $mcQuery = Db::name('machine_channel')->where('m_id', intval($mId))
            ->where('status', '<>', 2)
            ->where('g_id', '>', 0);
        if ($lock) {
            $mgQuery->lock(true);
            $mcQuery->lock(true);
        }
        $machineGoods = $mgQuery->select()->toArray();
        $allChannels = $mcQuery->select()->toArray();
        $ordinary = [];
        $multi = [];
        foreach ($allChannels as $mc) {
            // 多商品货道缺少 batch_id 价格维度，本期只识别并阻断，不参与快照投影。
            if (intval(isset($mc['is_multi_goods']) ? $mc['is_multi_goods'] : 2) === 1) {
                $multi[] = $mc;
            } else {
                $ordinary[] = $mc;
            }
        }
        return [
            'machine_goods' => $machineGoods,
            'machine_channels' => $ordinary,
            'multi_goods' => $multi,
        ];
    }

    protected function loadMachineGoodsPriceMap(array $machineGoods, $currencyCode)
    {
        $ids = array_map(function ($row) {
            return intval($row['mg_id']);
        }, $machineGoods);
        if (!$ids) {
            return [];
        }
        $rows = Db::name('machine_goods_currency_price')->whereIn('mg_id', $ids)->where('currency_code', $currencyCode)->select()->toArray();
        $map = [];
        foreach ($rows as $row) {
            $map[intval($row['mg_id'])] = $row;
        }
        return $map;
    }

    protected function loadMachineChannelPriceMap(array $channels, $currencyCode)
    {
        $ids = array_map(function ($row) {
            return intval($row['mc_id']);
        }, $channels);
        if (!$ids) {
            return [];
        }
        $rows = Db::name('machine_channel_currency_price')->whereIn('mc_id', $ids)->where('currency_code', $currencyCode)->select()->toArray();
        $map = [];
        foreach ($rows as $row) {
            $map[intval($row['mc_id'])] = $row;
        }
        return $map;
    }

    protected function validateSourceSnapshot(array $scope, $currencyCode)
    {
        $mgMap = $this->loadMachineGoodsPriceMap($scope['machine_goods'], $currencyCode);
        $mcMap = $this->loadMachineChannelPriceMap($scope['machine_channels'], $currencyCode);
        $result = ['mg_ids' => [], 'mc_ids' => []];
        foreach ($scope['machine_goods'] as $mg) {
            $price = isset($mgMap[intval($mg['mg_id'])]) ? $mgMap[intval($mg['mg_id'])] : null;
            if (!$price || intval($price['g_id']) !== intval($mg['g_id']) || !CurrencyPriceSupport::pricesEqual($mg, $price)) {
                $result['mg_ids'][] = intval($mg['mg_id']);
            }
        }
        foreach ($scope['machine_channels'] as $mc) {
            $price = isset($mcMap[intval($mc['mc_id'])]) ? $mcMap[intval($mc['mc_id'])] : null;
            if (!$price || intval($price['mg_id']) !== intval($mc['mg_id']) || intval($price['g_id']) !== intval($mc['g_id']) || !CurrencyPriceSupport::pricesEqual($mc, $price)) {
                $result['mc_ids'][] = intval($mc['mc_id']);
            }
        }
        return $result;
    }

    protected function hasPendingServerOrder($mId)
    {
        return Db::name('sale_orders')
            ->where('m_id', intval($mId))
            ->where('create_time', '>=', time() - 86400)
            ->where(function ($query) {
                $query->whereIn('pay_status', [1, 2])
                    ->whereOr(function ($subQuery) {
                        $subQuery->where('pay_status', 3)->whereIn('out_status', [1, 2, 6]);
                    });
            })
            ->count() > 0;
    }

    protected function priceFields(array $row)
    {
        return [
            'cost_price' => $row['cost_price'],
            'market_price' => $row['market_price'],
            'retail_price' => $row['retail_price'],
        ];
    }

    protected function addBlocker(array &$result, $code, $message)
    {
        $result['blockers'][] = ['code' => $code, 'message' => $message];
    }

    protected function stateCacheKey($mId)
    {
        return 'currency_switch_state:' . intval($mId);
    }
}
