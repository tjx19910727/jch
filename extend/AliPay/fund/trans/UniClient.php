<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021/12/31
 * Time: 18:29
 */

namespace AliPay\fund\trans;


use AliPay\Kernel\BaseClient;

class UniClient extends BaseClient
{

    /**
     * 单笔转账接口
     * @param $data
     * out_biz_no    转账订单编号，date("YmdHis").get_rand_string(6)
     * trans_amount  金额，单位元，小数点后两位，
     * order_title   转账业务的标题，在支付宝用户的账单里显示
     * identity      支付宝登录账号
     * name          参与方真实姓名
     * @return mixed
     * {
            "alipay_fund_trans_uni_transfer_response": {
                "code": "10000",
                "msg": "Success",
                "out_biz_no": "201808080001",
                "order_id": "20190801110070000006380000250621",
                "pay_fund_order_id": "20190801110070001506380000251556",
                "status": "SUCCESS",
                "trans_date": "2019-08-21 00:00:00"
            },
            "sign": "ERITJKEIJKJHKKKKKKKHJEREEEEEEEEEEE"
        }
     */
    public function transfer($data)
    {
        $this->onceName = "AlipayFundTransUniTransferRequest";
        $this->bizContent = $data;
//        $this->bizContent['out_biz_no'] = $data['out_biz_no'];
//        $this->bizContent['trans_amount'] = $data['trans_amount'];
//        $this->bizContent['product_code'] = "TRANS_ACCOUNT_NO_PWD";
//        $this->bizContent['biz_scene'] = "DIRECT_TRANSFER";
//        $this->bizContent['order_title'] = $data['order_title'];
//        $payee_info = [
//            "identity" => $data['identity'],
//            "identity_type" => $type,
//            "name" => $data['name'],
//        ];
//        $this->bizContent['payee_info'] = $payee_info;
//        $this->bizContent['remark'] = $data['remark'];
//        !isset($data['payer_show_name']) ? :$this->bizContent['business_params'] = json_encode(["payer_show_name" => $data['payer_show_name']],JSON_UNESCAPED_UNICODE);
        return $this->execute();
    }
}