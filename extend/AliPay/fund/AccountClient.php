<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/1/4
 * Time: 9:38
 */

namespace AliPay\fund;


use AliPay\Kernel\BaseClient;

class AccountClient extends BaseClient
{

    /**
     * 支付宝资金账户资产查询
     * @param $AliPayUserId
     * @return mixed
     * {
            "alipay_fund_account_query_response": {
                "code": "10000",
                "msg": "Success",
                "available_amount": "26.45",    账户可用余额，单位元，精确到小数点后两位。
                "freeze_amount": "11.11"        当前支付宝账户的实时冻结余额
            },
            "sign": "ERITJKEIJKJHKKKKKKKHJEREEEEEEEEEEE"
        }
     */
    public function query($AliPayUserId)
    {
        $this->onceName = "AlipayFundAccountQueryRequest";
        $this->bizContent['alipay_user_id'] = $AliPayUserId;
        $this->bizContent['account_type'] = "ACCTRANS_ACCOUNT";
        return $this->execute();
    }


}