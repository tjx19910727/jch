<?php

namespace app\AppFactory\Kernel\Support\Currency;

class CurrencyPriceSupport
{
    const PRICE_FIELDS = ['cost_price', 'market_price', 'retail_price'];

    public static function normalizeCurrencyCode($currencyCode)
    {
        $currencyCode = strtoupper(trim((string)$currencyCode));
        if (!preg_match('/^[A-Z]{3}$/', $currencyCode)) {
            throw new \InvalidArgumentException('币种编码必须为三位大写字母');
        }
        return $currencyCode;
    }

    /**
     * 人工同步必须明确选择一个或多个币种；统一转换为大写并按首次出现顺序去重。
     */
    public static function normalizeCurrencyCodes($currencyCodes)
    {
        if (!is_array($currencyCodes)) {
            throw new \InvalidArgumentException('币种代码必须是数组');
        }
        $result = [];
        foreach ($currencyCodes as $currencyCode) {
            if (!is_string($currencyCode)) {
                throw new \InvalidArgumentException('币种代码必须是字符串');
            }
            $currencyCode = self::normalizeCurrencyCode($currencyCode);
            $result[$currencyCode] = $currencyCode;
        }
        if (!$result) {
            throw new \InvalidArgumentException('至少选择一个币种');
        }
        return array_values($result);
    }

    public static function normalizePrice($value, $fieldName = 'price')
    {
        $value = trim((string)$value);
        if (!preg_match('/^\d{1,12}(?:\.\d{1,3})?$/', $value)) {
            throw new \InvalidArgumentException($fieldName . '必须是非负数且最多保留3位小数');
        }
        return bcadd($value, '0', 3);
    }

    public static function normalizePriceRow(array $input, array $existing = [])
    {
        $row = [];
        foreach (self::PRICE_FIELDS as $field) {
            if (array_key_exists($field, $input) && $input[$field] !== '') {
                $row[$field] = self::normalizePrice($input[$field], $field);
            } elseif (array_key_exists($field, $existing)) {
                $row[$field] = self::normalizePrice($existing[$field], $field);
            } else {
                throw new \InvalidArgumentException('缺少价格字段：' . $field);
            }
        }
        return $row;
    }

    /**
     * 统一清洗人工同步的选中记录，去重并限制单次处理量，避免大事务长时间持锁。
     */
    public static function normalizeIds($ids, $limit = 200)
    {
        if (!is_array($ids)) {
            $ids = explode(',', (string)$ids);
        }
        $result = [];
        foreach ($ids as $id) {
            $id = intval($id);
            if ($id > 0) {
                $result[$id] = $id;
            }
        }
        $result = array_values($result);
        if (!$result) {
            throw new \InvalidArgumentException('至少选择一条有效记录');
        }
        if (count($result) > intval($limit)) {
            throw new \InvalidArgumentException('单次最多处理' . intval($limit) . '条记录');
        }
        return $result;
    }

    public static function pricesEqual(array $left, array $right)
    {
        foreach (self::PRICE_FIELDS as $field) {
            if (!isset($left[$field]) || !isset($right[$field])) {
                return false;
            }
            if (bccomp((string)$left[$field], (string)$right[$field], 3) !== 0) {
                return false;
            }
        }
        return true;
    }

    /**
     * 判断一组三价是否全部为 0（默认占位价），用于首次保存保护。
     * @param array $row
     * @return bool
     */
    public static function isZeroPrice(array $row)
    {
        foreach (self::PRICE_FIELDS as $field) {
            if (!isset($row[$field]) || bccomp((string)$row[$field], '0', 3) !== 0) {
                return false;
            }
        }
        return true;
    }
}
