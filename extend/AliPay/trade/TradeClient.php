<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/1/4
 * Time: 10:59
 */

namespace AliPay\trade;


use AliPay\Kernel\BaseClient;

class TradeClient extends BaseClient
{
    /**
     * 统一收单线下交易查询
     * @param $out_trade_no   //  订单支付时传入的商户订单号
     * @param string $query_options
     *                  trade_settle_info：返回的交易结算信息，包含分账、补差等信息。
     *                  fund_bill_list：   交易支付使用的资金渠道。
     * @return mixed
     * {
    "alipay_trade_query_response": {
    "code": "10000",
    "msg": "Success",
    "trade_no": "2013112011001004330000121536",
    "out_trade_no": "6823789339978248",
    "buyer_logon_id": "159****5620",
    "trade_status": "TRADE_CLOSED",
    "total_amount": 88.88,
    "trans_currency": "TWD",
    "settle_currency": "USD",
    "settle_amount": 2.96,
    "pay_currency": 1,
    "pay_amount": "8.88",
    "settle_trans_rate": "30.025",
    "trans_pay_rate": "0.264",
    "buyer_pay_amount": 8.88,
    "point_amount": 10,
    "invoice_amount": 12.11,
    "send_pay_date": "2014-11-27 15:45:57",
    "receipt_amount": "15.25",
    "store_id": "NJ_S_001",
    "terminal_id": "NJ_T_001",
    "fund_bill_list": [
    {
    "fund_channel": "ALIPAYACCOUNT",
    "amount": 10,
    "real_amount": 11.21
    }
    ],
    "store_name": "证大五道口店",
    "buyer_user_id": "2088101117955611",
    "industry_sepc_detail_gov": "{\"registration_order_pay\":{\"brlx\":\"1\",\"cblx\":\"1\"}}",
    "industry_sepc_detail_acc": "{\"registration_order_pay\":{\"brlx\":\"1\",\"cblx\":\"1\"}}",
    "charge_amount": "8.88",
    "charge_flags": "bluesea_1",
    "settlement_id": "2018101610032004620239146945",
    "trade_settle_info": {
    "trade_settle_detail_list": [
    {
    "operation_type": "replenish",
    "operation_serial_no": "2321232323232",
    "operation_dt": "2019-05-16 09:59:17",
    "trans_out": "208811****111111",
    "trans_in": "208811****111111",
    "amount": 10,
    "ori_trans_out": "2088111111111111",
    "ori_trans_in": "2088111111111111"
    }
    ]
    },
    "auth_trade_pay_mode": "CREDIT_PREAUTH_PAY",
    "buyer_user_type": "PRIVATE",
    "mdiscount_amount": "88.88",
    "discount_amount": "88.88",
    "subject": "Iphone6 16G",
    "body": "Iphone6 16G",
    "alipay_sub_merchant_id": "2088301372182171",
    "ext_infos": "{\"action\":\"cancel\"}",
    "passback_params": "merchantBizType%3d3C%26merchantBizNo%3d2016010101111",
    "hb_fq_pay_info": {
    "user_install_num": "3"
    },
    "credit_pay_mode": "creditAdvanceV2",
    "credit_biz_order_id": "ZMCB99202103310000450000041833",
    "enterprise_pay_info": {
    "invoice_amount": 80
    }
    },
    "sign": "ERITJKEIJKJHKKKKKKKHJEREEEEEEEEEEE"
    }
     * @return mixed
     * @throws \Exception
     */
    public function query($out_trade_no,$query_options = "trade_settle_info")
    {
        $this->onceName = "AlipayTradeQueryRequest";
        $this->bizContent['out_trade_no '] = $out_trade_no;
        $this->bizContent['trade_no '] = "";
        $this->bizContent['query_options'] = $query_options;
        return $this->execute();
    }

    /**
     *  统一收单交易支付接口
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    public function pay($data)
    {
        $this->onceName = "AlipayTradePayRequest";
        $this->bizContent = $data;
        return $this->execute();
    }

    /**
     *
     * 统一收单线下交易预创建，生成支付二维码
     * @param $data
     * @param array $optional
     * @return mixed
     * {
    "alipay_trade_precreate_response": {
    "code": "10000",
    "msg": "Success",
    "out_trade_no": "6823789339978248",
    "qr_code": "https://qr.alipay.com/bavh4wjlxf12tper3a"
    },
    "sign": "ERITJKEIJKJHKKKKKKKHJEREEEEEEEEEEE"
    }
     * @throws \Exception
     */
    public function preCreate($data,$optional = [])
    {
        $this->onceName = "AlipayTradePrecreateRequest";
        $this->bizContent = [
            "out_trade_no" => $data['out_trade_no'],
            "total_amount" => $data['total_amount'],
            "subject" => $data['subject'],
        ];
        if ($optional) $this->bizContent = array_merge($this->bizContent,$optional);
        $this->notifyUrl ? : ($this->notifyUrl = ($data['notifyUrl'] ?? ""));
        return $this->execute();
    }

    /**
     * 统一收单交易撤销接口
     * @param $out_trade_no // 系统生成的订单号
     * @return mixed
     * @throws \Exception
     */
    public function cancel($out_trade_no)
    {
        $this->onceName = "AlipayTradeCancel";
        $this->bizContent = [
            "out_trade_no" => $out_trade_no,
        ];
        return $this->execute();
    }

    /**
     * 统一收单交易退款接口
     * @param $data // out_trade_no    refund_amount
     * @return mixed
     * @throws \Exception
     */
    public function refund($data)
    {
        $this->onceName = "AlipayTradeRefundRequest";
        $this->bizContent = $data;
        return $this->execute();
    }

}