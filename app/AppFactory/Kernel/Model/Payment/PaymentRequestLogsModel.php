<?php

namespace app\AppFactory\Kernel\Model\Payment;

use app\AppFactory\Kernel\Model\BaseModel;

class PaymentRequestLogsModel extends BaseModel
{
    protected $pk = 'prl_id';
    protected $name = 'payment_request_logs';
}
