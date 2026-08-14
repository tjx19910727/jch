<?php

namespace app\AppFactory\Kernel\Service\Payment;

use app\AppFactory\Kernel\Support\SubCarMixPolicy;
use think\facade\Db;

/**
 * 解析设备购物车可用收款策略。商品显式策略优先，未配置时保留设备现有策略集合。
 */
class CartPayeeStrategyResolver
{
    /**
     * 嘉潮汇平台组织ID：线上商品(Z10)默认归属于该组织的收款策略。
     */
    const JCH_ORG_AO_ID = 17;

    public static function resolve($machine, array $cartList, $payType = 0)
    {
        if (is_object($machine) && method_exists($machine, 'toArray')) $machine = $machine->toArray();
        if (!is_array($machine)) return self::fail('设备信息格式错误', 'machine_invalid');
        if (!$cartList) return self::fail('购物车不能为空', 'cart_empty');

        $machineId = intval($machine['m_id'] ?? 0);
        $machineAoId = intval($machine['ao_id'] ?? 0);
        $payType = intval($payType);
        $items = [];
        $candidateIds = null;
        $hasExplicit = false;
        $machineConfig = Db::name('machine_config')->where('m_id', $machineId)
            ->field('subcar_mix,subcar_offline_sp_ids,subcar_online_sp_ids')->find();
        $subCarMix = intval($machineConfig['subcar_mix'] ?? SubCarMixPolicy::MIX_ALLOWED);
        $goodsSource = SubCarMixPolicy::cartGoodsSource($cartList);
        if ($subCarMix === SubCarMixPolicy::MIX_FORBIDDEN && $goodsSource === SubCarMixPolicy::SOURCE_MIXED) {
            return self::fail('线上商品与线下商品不能同时下单，请分开结算', 'goods_source_conflict');
        }
        $offlineLegacyIds = null;
        $offlineFallbackToMachineOrg = false;
        if ($subCarMix === SubCarMixPolicy::MIX_FORBIDDEN) {
            $configuredOfflineIds = SubCarMixPolicy::parsePayeeIds(
                $machineConfig[SubCarMixPolicy::OFFLINE_SP_IDS_FIELD] ?? ''
            );
            // 禁止混合不等于禁止线下收款：空白名单完整回退设备所属组织的原收款策略。
            $offlineLegacyIds = $configuredOfflineIds ?: null;
            $offlineFallbackToMachineOrg = !$configuredOfflineIds;
        }
        $onlineLegacyIds = null;
        if ($subCarMix === SubCarMixPolicy::MIX_FORBIDDEN) {
            $configuredOnlineIds = SubCarMixPolicy::parsePayeeIds(
                $machineConfig[SubCarMixPolicy::ONLINE_SP_IDS_FIELD] ?? ''
            );
            // 空线上白名单使用嘉潮汇组织(ao_id=17)在当前设备上的原收款策略。
            $onlineLegacyIds = $configuredOnlineIds ?: null;
        }

        foreach ($cartList as $cartItem) {
            if (!is_array($cartItem)) return self::fail('购物车商品格式错误', 'cart_item_invalid');
            $channelCode = trim(strval($cartItem['channel_code'] ?? ''));
            if ($channelCode === 'Z10') {
                // 线上商品默认配置为嘉潮汇组织的收款策略
                $legacy = self::getLegacyStrategies(
                    $machineId,
                    self::JCH_ORG_AO_ID,
                    $machineAoId,
                    $payType,
                    $onlineLegacyIds,
                    false
                );
                if (!$legacy) return self::fail('当前线上商品未配置可用收款策略', 'legacy_strategy_unavailable');
                $itemCandidates = array_map('intval', array_column($legacy, 'sp_id'));
                $candidateIds = $candidateIds === null
                    ? $itemCandidates
                    : array_values(array_intersect($candidateIds, $itemCandidates));
                if (!$candidateIds) return self::fail('购物车商品属于不同收款策略，请分开结算', 'strategy_conflict');
                $items[] = [
                    'mc_id' => intval($cartItem['mc_id'] ?? 0),
                    'mg_id' => 0,
                    'goods_ao_id' => 0,
                    'source_sp_id' => 0,
                    'payee_source' => 'legacy',
                ];
                continue;
            }

            $mcId = intval($cartItem['mc_id'] ?? 0);
            $channel = Db::name('machine_channel')
                ->where(['mc_id' => $mcId, 'm_id' => $machineId])
                ->field('mc_id,mg_id,g_id,g_name,channel_code')
                ->find();
            if (!$channel) return self::fail('购物车货道不存在或不属于当前设备', 'channel_not_found');

            $mgId = intval($channel['mg_id'] ?? 0);
            $goods = $mgId > 0
                ? Db::name('machine_goods')->where(['mg_id' => $mgId, 'm_id' => $machineId])->field('mg_id,ao_id,g_id,g_name')->find()
                : null;
            if (!$goods) return self::fail('当前商品未配置设备商品信息', 'machine_goods_not_found');

            // 收款策略按商品库商品(goods)归属组织校验：商品g属于组织A，即使上架到组织B的设备，
            // 也应使用归属于商品组织A的收款策略
            $goodsAoId = intval($goods['g_id'] ?? 0) > 0
                ? intval(Db::name('goods')->where('g_id', intval($goods['g_id']))->value('ao_id'))
                : 0;
            if ($goodsAoId <= 0) $goodsAoId = intval($goods['ao_id'] ?? 0);
            $hasGoodsStrategyConfig = false;
            $explicitStrategies = self::getGoodsStrategies(
                $mgId,
                $payType,
                $hasGoodsStrategyConfig
            );
            if ($hasGoodsStrategyConfig && !$explicitStrategies) {
                return self::fail('商品独立收款策略不存在、已停用或与所选支付方式不匹配', 'goods_strategy_unavailable');
            }
            if ($explicitStrategies) {
                $hasExplicit = true;
                foreach ($explicitStrategies as $strategy) {
                    if (!self::organizationCompatible($strategy, $goodsAoId, $machineAoId)) {
                        return self::fail('商品独立收款策略与商品所属组织不匹配', 'goods_strategy_organization_mismatch');
                    }
                }
                $itemCandidates = array_map('intval', array_column($explicitStrategies, 'sp_id'));
                $source = 'goods_explicit';
            } else {
                $legacyGoodsAoId = $offlineFallbackToMachineOrg ? 0 : $goodsAoId;
                $legacy = self::getLegacyStrategies($machineId, $legacyGoodsAoId, $machineAoId, $payType, $offlineLegacyIds);
                if (!$legacy) return self::fail('当前商品未配置可用收款策略，请联系管理员', 'legacy_strategy_unavailable');
                $itemCandidates = array_map('intval', array_column($legacy, 'sp_id'));
                $source = 'legacy';
            }

            $candidateIds = $candidateIds === null
                ? $itemCandidates
                : array_values(array_intersect($candidateIds, $itemCandidates));
            if (!$candidateIds) return self::fail('购物车商品属于不同收款策略，请分开结算', 'strategy_conflict');

            $items[] = [
                'mc_id' => $mcId,
                'mg_id' => $mgId,
                'goods_ao_id' => $goodsAoId,
                'source_sp_id' => 0,
                'payee_source' => $source,
            ];
        }

        $strategies = self::getStrategiesByIds($candidateIds, $payType);
        if (!$strategies) return self::fail('设备及商品均未配置可用收款策略', 'strategy_unavailable');
        $effectiveSpId = ($payType > 0 || count($strategies) === 1)
            ? intval($strategies[0]['sp_id'])
            : 0;
        foreach ($items as &$item) {
            $item['effective_sp_id'] = $effectiveSpId;
            if ($item['payee_source'] === 'goods_explicit') $item['source_sp_id'] = $effectiveSpId;
        }
        unset($item);

        return [
            'state' => 200,
            'msg' => '收款策略解析成功',
            'effective_sp_id' => $effectiveSpId,
            'strategy_source' => $hasExplicit ? 'goods_explicit' : 'legacy',
            'pay_type_list' => array_values($strategies),
            'items' => $items,
        ];
    }

    private static function getLegacyStrategies($machineId, $goodsAoId, $machineAoId, $payType, $allowedSpIds = null, $fallbackToMachineOrg = true)
    {
        if (is_array($allowedSpIds) && !$allowedSpIds) return [];
        $query = Db::name('strategy_machine')->alias('sm')
            ->join('strategy_payee sp', 'sp.sp_id=sm.s_id')
            ->where(['sm.m_id' => $machineId, 'sm.s_type' => 1, 'sp.status' => 1]);
        if (is_array($allowedSpIds)) $query->where('sp.sp_id', 'in', $allowedSpIds);
        if ($payType > 0) $query->where('sp.payee_type', 'in', self::compatiblePayTypes($payType));
        if ($goodsAoId > 0) {
            $orgRows = (clone $query)->where('sm.ao_id', $goodsAoId)
                ->field('sp.sp_id,sp.sp_name,sp.title,sp.payee_type,sp.ico,sp.ao_id,sm.sort')->order('sm.sort asc')->select()->toArray();
            if ($orgRows) return $orgRows;
            if (!$fallbackToMachineOrg) return [];
        }
        if ($machineAoId > 0) $query->where('sm.ao_id', $machineAoId);
        return $query->field('sp.sp_id,sp.sp_name,sp.title,sp.payee_type,sp.ico,sp.ao_id,sm.sort')
            ->order('sm.sort asc')->select()->toArray();
    }

    private static function getGoodsStrategies($mgId, $payType, &$configured)
    {
        $configured = Db::name('machine_goods_payee_strategy')->where(['mg_id' => $mgId, 'status' => 1])->count() > 0;
        $query = Db::name('machine_goods_payee_strategy')->alias('mgps')
            ->join('strategy_payee sp', 'sp.sp_id=mgps.sp_id')
            ->where(['mgps.mg_id' => $mgId, 'mgps.status' => 1, 'sp.status' => 1]);
        if ($payType > 0) $query->where('sp.payee_type', 'in', self::compatiblePayTypes($payType));
        $rows = $query->field('sp.sp_id,sp.sp_name,sp.title,sp.payee_type,sp.ico,sp.ao_id,sp.status')
            ->order('mgps.sort asc,mgps.id asc')->select()->toArray();
        return $rows;
    }

    private static function getStrategiesByIds(array $ids, $payType)
    {
        if (!$ids) return [];
        $query = Db::name('strategy_payee')->where('sp_id', 'in', $ids)->where('status', 1);
        if ($payType > 0) $query->where('payee_type', 'in', self::compatiblePayTypes($payType));
        $rows = $query->field('sp_id,sp_name,title,payee_type,ico,ao_id')->select()->toArray();
        $order = array_flip($ids);
        usort($rows, function ($a, $b) use ($order) {
            return intval($order[intval($a['sp_id'])] ?? 999999) - intval($order[intval($b['sp_id'])] ?? 999999);
        });
        return $rows;
    }

    private static function organizationCompatible(array $strategy, $goodsAoId, $machineAoId)
    {
        $strategyAoId = intval($strategy['ao_id'] ?? 0);
        if ($strategyAoId <= 0) return true;
        if ($goodsAoId > 0) return $strategyAoId === $goodsAoId;
        return $strategyAoId === $machineAoId;
    }

    private static function compatiblePayTypes($payType)
    {
        $payType = intval($payType);
        if (in_array($payType, [11, 12], true)) return [$payType, 1];
        if (in_array($payType, [21, 22], true)) return [$payType, 2];
        return [$payType];
    }

    private static function fail($msg, $code)
    {
        return ['state' => 100, 'msg' => $msg, 'error_code' => $code];
    }
}
