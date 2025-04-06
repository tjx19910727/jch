<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/1/3
 * Time: 16:30
 */

namespace AliPay\fund\trans;


use AliPay\Kernel\BaseClient;

class CommonClient extends BaseClient
{

    /**
     * 转账业务单据查询
     * @param $out_biz_no
     * @return mixed
     * {
            "alipay_fund_trans_common_query_response": {
                "code": "10000",
                "msg": "Success",
                "order_id": "20190801110070000006380000250621",
                "pay_fund_order_id": "20190801110070001506380000251556",
                "out_biz_no": "201808080001",
                "status": "SUCCESS",
                "pay_date": "2013-01-01 08:08:08"
            },
            "sign": "ERITJKEIJKJHKKKKKKKHJEREEEEEEEEEEE"
        }
     */
    public function query($out_biz_no)
    {
        $this->onceName = "AlipayFundTransCommonQueryRequest";
        $this->bizContent['product_code'] = "TRANS_ACCOUNT_NO_PWD";
        $this->bizContent['biz_scene'] = "DIRECT_TRANSFER";
        $this->bizContent['out_biz_no'] = $out_biz_no;
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