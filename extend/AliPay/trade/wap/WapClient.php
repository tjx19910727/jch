<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/1/4
 * Time: 11:27
 */

namespace AliPay\trade\wap;


use AliPay\Kernel\BaseClient;

class WapClient extends BaseClient
{

    /**
     * 手机网站支付接口2.0
     * @param $data
     * @return mixed
     */
    public function Pay($data)
    {
        $this->onceName = "AlipayTradeWapPayRequest";
        $this->bizContent = [
            "body"         => $data['body'],
            'out_trade_no' => $data['out_trade_no'],
            'total_amount' => $data['total_amount'],
            'subject'      => $data['subject'],
            "product_code" => 'QUICK_WAP_PAY',
            "timeout_express" => "2m",
        ];
        return $this->pageExecute();
    }
}