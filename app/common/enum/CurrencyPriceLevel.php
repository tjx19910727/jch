<?php

namespace app\common\enum;

class CurrencyPriceLevel
{
    const CORE_GOODS = 1;
    const MACHINE_GOODS = 2;
    const MACHINE_CHANNEL = 3;

    public static function labels()
    {
        return [
            self::CORE_GOODS => '核心商品',
            self::MACHINE_GOODS => '设备商品',
            self::MACHINE_CHANNEL => '设备货道',
        ];
    }
}
