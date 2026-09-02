<?php

namespace app\common\enum;

class CurrencyStatus
{
    const ENABLED = 1;
    const DISABLED = 2;

    public static function labels()
    {
        return [
            self::ENABLED => '启用',
            self::DISABLED => '停用',
        ];
    }
}
