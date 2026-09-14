<?php

namespace app\AppFactory\Kernel\Traits\Currency;

use app\AppFactory\Kernel\Model\Currency\MachineGoodsCurrencyPriceModel;

trait MachineGoodsCurrencyPriceTrait
{
    public function getMachineGoodsCurrencyPriceList($where, $pageNum = 0, $field = '*', $order = '')
    {
        return MachineGoodsCurrencyPriceModel::getList($where, $pageNum, $field, $order);
    }
}
