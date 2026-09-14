<?php

namespace app\AppFactory\Kernel\Model\Currency;

use app\AppFactory\Kernel\Model\BaseModel;

class MachineChannelCurrencyPriceModel extends BaseModel
{
    protected $name = 'machine_channel_currency_price';
    protected $pk = 'mccp_id';
    protected $autoWriteTimestamp = false;
}
