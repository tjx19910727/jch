<?php

namespace app\common\enum;

class CurrencyManualSyncAction
{
    const CORE_TO_MACHINE_GOODS = 1;
    const MACHINE_GOODS_TO_CHANNEL = 2;

    public static function labels()
    {
        return [
            self::CORE_TO_MACHINE_GOODS => '核心商品同步到设备商品',
            self::MACHINE_GOODS_TO_CHANNEL => '设备商品同步到货道',
        ];
    }
}
