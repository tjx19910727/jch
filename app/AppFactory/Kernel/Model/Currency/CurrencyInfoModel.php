<?php

namespace app\AppFactory\Kernel\Model\Currency;

use app\AppFactory\Kernel\Model\BaseModel;

class CurrencyInfoModel extends BaseModel
{
    protected $name = 'currency_info';
    protected $pk = 'currency_code';
    protected $autoWriteTimestamp = false;
}
