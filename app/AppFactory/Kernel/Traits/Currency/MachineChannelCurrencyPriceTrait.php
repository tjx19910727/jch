<?php

namespace app\AppFactory\Kernel\Traits\Currency;

use app\AppFactory\Kernel\Model\Currency\MachineChannelCurrencyPriceModel;

trait MachineChannelCurrencyPriceTrait
{
    public function getMachineChannelCurrencyPriceList($where, $pageNum = 0, $field = '*', $order = '')
    {
        return MachineChannelCurrencyPriceModel::getList($where, $pageNum, $field, $order);
    }
}
