<?php

namespace app\AppFactory\Kernel\Traits\Currency;

use app\AppFactory\Kernel\Model\Currency\CurrencyInfoModel;

trait CurrencyInfoTrait
{
    public function getCurrencyInfoFind($where, $field = '*', $order = '')
    {
        return CurrencyInfoModel::getFind($where, $field, $order);
    }

    public function getCurrencyInfoList($where = [], $pageNum = 0, $field = '*', $order = '')
    {
        return CurrencyInfoModel::getList($where, $pageNum, $field, $order);
    }
}
