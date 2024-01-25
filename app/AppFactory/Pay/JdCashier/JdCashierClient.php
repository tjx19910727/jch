<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/22
 * Time: 19:58
 */

namespace app\AppFactory\Pay\JdCashier;


use app\AppFactory\Kernel\Traits\Payment\JdCashierTrait;
use app\AppFactory\Pay\PayBaseClient;

class JdCashierClient extends PayBaseClient
{
    use JdCashierTrait;


}