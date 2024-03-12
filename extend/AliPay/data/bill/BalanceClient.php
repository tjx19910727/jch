<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/1/4
 * Time: 9:58
 */

namespace AliPay\data\bill;


use AliPay\Kernel\BaseClient;

class BalanceClient extends BaseClient
{


    /**
     * 支付宝商家账户当前余额查询
     * @return mixed
     * {
            "alipay_data_bill_balance_query_response": {
                "code": "10000",
                "msg": "Success",
                "total_amount": "10000.00",      支付宝账户余额
                "available_amount": "9000.00",   账户可用余额
                "freeze_amount": "1000.00"       冻结金额
            },
            "sign": "ERITJKEIJKJHKKKKKKKHJEREEEEEEEEEEE"
        }
     */
    public function query()
    {
        $this->onceName = "AlipayDataBillBalanceQueryRequest";
        return $this->execute();
    }


    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([$this['base'], $name], $arguments);
    }
}