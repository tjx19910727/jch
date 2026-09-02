<?php

namespace app\AppFactory\Kernel\Traits\Currency;

use app\AppFactory\Kernel\Model\Currency\GoodsCurrencyPriceModel;

trait GoodsCurrencyPriceTrait
{
    public function getGoodsCurrencyPriceList($where, $pageNum = 0, $field = '*', $order = '')
    {
        return GoodsCurrencyPriceModel::getList($where, $pageNum, $field, $order);
    }
}
