<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/6/26
 * Time: 16:12
 */

namespace WeChatPayV3\Payment\Refund;


use WeChatPayV3\Payment\Kernel\BaseClient;

class RefundClient extends BaseClient
{
    /**
     * 退款申请
     * @param $params
     * @return bool|string
     */
    public function domestic($params)
    {
        return $this->httpPost("/v3/refund/domestic/refunds",$params);
    }

    /**
     * 查询单笔退款（通过商户退款单号）
     * @param $out_refund_no
     * @return bool|string
     */
    public function query($out_refund_no)
    {
        return $this->httpGet("/v3/refund/domestic/refunds/" . $out_refund_no);
    }
}