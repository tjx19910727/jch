<?php

namespace app\AppFactory\Kernel\Support;

/**
 * 设备购物车商品来源与收款策略配置规则。
 */
class SubCarMixPolicy
{
    const MIX_ALLOWED = 1;
    const MIX_FORBIDDEN = 2;

    const SOURCE_EMPTY = 'empty';
    const SOURCE_OFFLINE = 'offline';
    const SOURCE_ONLINE = 'online';
    const SOURCE_MIXED = 'mixed';

    const OFFLINE_SP_IDS_FIELD = 'subcar_offline_sp_ids';
    const ONLINE_SP_IDS_FIELD = 'subcar_online_sp_ids';

    /**
     * 校验收款策略 ID 列表。兼容数组、JSON 数组字符串和逗号分隔字符串。
     *
     * @param mixed $value
     * @return bool
     */
    public static function validatePayeeIds($value)
    {
        $valid = true;
        $items = self::payeeIdItems($value, $valid);
        if (!$valid) return false;

        foreach ($items as $item) {
            if (is_int($item)) {
                if ($item <= 0) return false;
                continue;
            }
            if (!is_string($item) || !preg_match('/^[1-9][0-9]*$/', trim($item))) {
                return false;
            }
        }
        return true;
    }

    /**
     * 将收款策略 ID 列表解析为去重后的正整数数组。
     *
     * @param mixed $value
     * @return array
     */
    public static function parsePayeeIds($value)
    {
        $valid = true;
        $items = self::payeeIdItems($value, $valid);
        if (!$valid) return [];

        $ids = [];
        foreach ($items as $item) {
            $item = is_string($item) ? trim($item) : $item;
            if ((is_int($item) && $item > 0)
                || (is_string($item) && preg_match('/^[1-9][0-9]*$/', $item))) {
                $ids[] = intval($item);
            }
        }
        return array_values(array_unique($ids));
    }

    /**
     * 将收款策略 ID 列表规范为逗号分隔字符串保存。
     *
     * @param mixed $value
     * @return string
     */
    public static function normalizePayeeIds($value)
    {
        return implode(',', self::parsePayeeIds($value));
    }

    /**
     * 按 subCar 现有的 Z10 分支规则识别购物车商品来源。
     *
     * @param mixed $cartList
     * @return string
     */
    public static function cartGoodsSource($cartList)
    {
        $hasOnline = false;
        $hasOffline = false;
        if (!is_array($cartList)) return self::SOURCE_EMPTY;

        foreach ($cartList as $item) {
            if (is_object($item) && method_exists($item, 'toArray')) $item = $item->toArray();
            if (!is_array($item)) continue;
            if (isset($item['channel_code']) && $item['channel_code'] === 'Z10') {
                $hasOnline = true;
            } else {
                $hasOffline = true;
            }
            if ($hasOnline && $hasOffline) return self::SOURCE_MIXED;
        }
        return self::goodsSource($hasOnline, $hasOffline);
    }

    /**
     * 按订单明细中的微程快照识别已落单商品来源。
     *
     * @param mixed $details
     * @return string
     */
    public static function orderGoodsSource($details)
    {
        $hasOnline = false;
        $hasOffline = false;
        if (!is_array($details)) return self::SOURCE_EMPTY;

        foreach ($details as $detail) {
            if (is_object($detail) && method_exists($detail, 'toArray')) $detail = $detail->toArray();
            if (!is_array($detail)) continue;
            $wcOrderNo = json_decode($detail['wc_order_no'] ?? '', true);
            if (is_array($wcOrderNo) && !empty($wcOrderNo)) {
                $hasOnline = true;
            } else {
                $hasOffline = true;
            }
            if ($hasOnline && $hasOffline) return self::SOURCE_MIXED;
        }
        return self::goodsSource($hasOnline, $hasOffline);
    }

    /**
     * @param mixed $value
     * @param bool $valid
     * @return array
     */
    private static function payeeIdItems($value, &$valid)
    {
        $valid = true;
        if ($value === null || $value === '' || $value === []) return [];
        if (is_array($value)) return array_values($value);
        if (!is_string($value) && !is_int($value)) {
            $valid = false;
            return [];
        }
        if (is_int($value)) return [$value];

        $value = trim($value);
        if ($value === '') return [];
        if (substr($value, 0, 1) === '[') {
            $decoded = json_decode($value, true);
            if (!is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
                $valid = false;
                return [];
            }
            return array_values($decoded);
        }
        return array_map('trim', explode(',', $value));
    }

    private static function goodsSource($hasOnline, $hasOffline)
    {
        if ($hasOnline && $hasOffline) return self::SOURCE_MIXED;
        if ($hasOnline) return self::SOURCE_ONLINE;
        if ($hasOffline) return self::SOURCE_OFFLINE;
        return self::SOURCE_EMPTY;
    }
}
