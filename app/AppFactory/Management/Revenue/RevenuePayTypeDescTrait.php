<?php

namespace app\AppFactory\Management\Revenue;

trait RevenuePayTypeDescTrait
{
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
            config('payment.pay_type_map') ?: [],
            config('payment.pay_channel_map') ?: []
        );
    }

    protected function fillRevenuePayTypeDesc(array $data, array $payTypeMap, array $payChannelMap)
    {
        foreach ($data as $field => &$value) {
            if ($field === 'pay_type' && $value !== '' && $value !== null) {
                $payType = intval($value);
                $data['pay_type_desc'] = $payTypeMap[$payType] ?? ('支付类型#' . $payType);
            }

            if ($field === 'pay_channel' && $value !== '' && $value !== null) {
                $payChannel = intval($value);
                $data['pay_channel_desc'] = $payChannelMap[$payChannel] ?? ('支付渠道#' . $payChannel);
            }

            if (is_array($value)) {
                $value = $this->fillRevenuePayTypeDesc($value, $payTypeMap, $payChannelMap);
            }
        }
        unset($value);

        return $data;
    }
}
