<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021/12/31
 * Time: 18:01
 */

namespace AliPay\trade\order;


use AliPay\Kernel\BaseClient;

class OrderClient extends BaseClient
{

    /**
     * 用于在卖家交易成功之后，基于交易订单，进行卖家与第三方（如供应商或平台商）的资金再分配。
     * 接口调用要求：
     * （1）建议支付成功后间隔 30s 再发起该接口请求
     * （2）单个商户请求频率最高 30 qps
     * （3）基于同一笔交易订单，该接口多次调用请求建议间隔 3s。
     * @return int
     */
    public function settle($data)
    {
        $this->onceName = "AlipayTradeOrderSettleRequest";
        $this->bizContent += [
            "trade_no" => $data['order_mch_no'],
            "royalty_parameters" => [],
            "extend_params" => ["royalty_finish" => true],
        ];
        $parameters = [];
        foreach ($data as  $key => $value){
            $parameters[] = [
                "royalty_type" => "transfer",
                "trans_in_type" => "userId",
                "trans_in" => $value['user_openid'],
                "amount" => $value['amount'],
                "desc" => $value['desc'],
            ];
        }
        if ($parameters) $this->bizContent['royalty_parameters'] = $parameters;
        return $this->execute();
    }

    /**
     * 交易分账查询
     * 根据分账请求号查询交易分账结果
     * @param $settle_no
     * @return mixed
     */
    public function settleQuery($settle_no)
    {
        $this->onceName = "AlipayTradeOrderSettleQueryRequest";
        $this->requireOnce();
        $this->bizContent['settle_no'] = $settle_no;
        return $this->execute();
    }


}