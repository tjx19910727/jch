<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2025/7/14
 * Time: 14:03
 */

namespace app\AppFactory\Kernel\Traits\Payment;

use app\AppFactory\Kernel\Support\Rsa;

trait CoGoLinkTrait
{
    public function CoGoRefund()
    {
        $configType = "CoGoLink";
        if (env("CglPay.is_test")) $configType = "CoGoLinkTest";
        $config = config("co_go_link.$configType");
        if (!$config) return $this->r(100, "查无CoGoLink配置");
        $url = 'https://payment-sg-gw.cogolinks.com/saas/pos';
        if (env("CglPay.is_test"))$url = 'https://129.226.223.13/pf-api-test/saas/pos';
        $header = [
            "interfaceVersion:1.0.0",
            "interfaceName:refund",
            "businessType:pos",
            "orgNo:" . $config['orgNo'],
            "orgCertId:" . $config['orgCertId'],
            "signMethod:RSA-SHA256",
        ];
        $private_key = file_get_contents(root_path() . $config['private_key_path']);
        if (!$private_key) return $this->r(100, '私钥不能为空');
        $private_key = "-----BEGIN PRIVATE KEY-----\n" . chunk_split($private_key, 64, "\n") . "-----END PRIVATE KEY-----";
        $data = [
            "merchant" => [
                "merchantNo" => $config['merchantNo']
            ],
            "transaction" => [
                "transactionNo" => $this->order['mch_no'],
                "merchantSerialNo" => $this->refundTradeNo,
                "amount" => $this->totalRefundMoney,
                "externalAdditionalData" => json_encode(['refundNo' => $this->refundData['remark'], 'order_id' => 111])
            ],
        ];
        $params = [
            "requestId" => $this->refundTradeNo,
            "requestTime" => date("Y-m-d\TH:i:s.vP"),
            "nonceStr" => $this->get_rand_string(32),
            "data" => $data,
        ];
        $sign = Rsa::sign(json_encode($params, 320), $private_key);
        $header[] = 'signature:' . $sign;
        $header[] = "Content-Type:application/json";
//        dump($url, $params, $header);
//        dump(json_encode($params, 320), json_encode($header, 320));
//        $public_key = "-----BEGIN PUBLIC KEY-----\n" . chunk_split(file_get_contents(root_path() . $config['self_public_key_path']), 64, "\n") . "-----END PUBLIC KEY-----";
//        $checkResult = Rsa::verify(json_encode($params, 320), $sign, $public_key);
//        dump($checkResult);
//        die();
        $result = $this->curl_request($url, 'POST', json_encode($params), $header);
        actionLog($result, '发起CoGoLink退款申请结果');
        $result = json2arr($result);
        if (isset($result['error_msg']) && $result['error_msg']) {
            return $this->rFail($result['error_msg']);
        }
        // 申请成功
        if ($result['respCode'] == 200) {
            return $this->r(200,'退款申请成功',$result);
        }
        // 退款失败
        return $this->r(100,"退款失败：" . $result['respMsg'],$result);
    }
}