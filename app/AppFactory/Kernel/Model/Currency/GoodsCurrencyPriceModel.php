<?php

namespace app\AppFactory\Kernel\Model\Currency;

use app\AppFactory\Kernel\Model\BaseModel;

class GoodsCurrencyPriceModel extends BaseModel
{
    protected $name = 'goods_currency_price';
    protected $pk = 'gcp_id';
    protected $autoWriteTimestamp = false;
}
