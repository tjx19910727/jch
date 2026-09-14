<?php

namespace app\AppFactory\Kernel\Model\Currency;

use app\AppFactory\Kernel\Model\BaseModel;

class MachineGoodsCurrencyPriceModel extends BaseModel
{
    protected $name = 'machine_goods_currency_price';
    protected $pk = 'mgcp_id';
    protected $autoWriteTimestamp = false;
}
