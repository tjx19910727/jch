<?php

namespace app\AppFactory\Management\Revenue;

use app\AppFactory\Kernel\Traits\Payment\PayTypeTrait;

trait RevenuePayTypeDescTrait
{
    use PayTypeTrait;

    protected function appendRevenuePayTypeDesc($data)
    {
        if (is_object($data) && method_exists($data, 'toArray')) {
            $data = $data->toArray();
        }

        if (!is_array($data)) {
            return $data;
        }

        return $this->fillRevenuePayTypeDesc(
            $data,
            $this->getRevenuePayTypeNameMap()
        );
    }

    protected function getRevenuePayTypeNameMap()
    {
        $tableMap = $this->getPayTypeNameMapFromTable(false);
        if ($tableMap) return $tableMap;
        return config('payment.pay_type_map') ?: [];
    }

    protected function fillRevenuePayTypeDesc(array $data, array $payTypeMap)
    {
        foreach ($data as $field => &$value) {
            if ($field === 'pay_type' && $value !== '' && $value !== null) {
                $payType = intval($value);
                $data['pay_type_desc'] = $payTypeMap[$payType] ?? ('支付类型#' . $payType);
            }

            if (is_array($value)) {
                $value = $this->fillRevenuePayTypeDesc($value, $payTypeMap);
            }
        }
        unset($value);

        return $data;
    }
}
