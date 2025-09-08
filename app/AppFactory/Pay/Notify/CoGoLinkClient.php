<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2025/7/14
 * Time: 11:16
 */

namespace app\AppFactory\Pay\Notify;


use app\AppFactory\Kernel\Support\Rsa;
use app\AppFactory\Kernel\Traits\Payment\AfterOrderRefundTrait;
use app\AppFactory\Kernel\Traits\Payment\CoGoLinkTrait;
use app\AppFactory\Pay\PayBaseClient;

class CoGoLinkClient extends PayBaseClient
{
    use AfterOrderRefundTrait;
    use CoGoLinkTrait;

    protected $header = [];

    public function handleNotify()
    {
        $this->header = $this->config['header'];
        $this->data = json2arr($this->data);
        dump($this->data);
        $configType = "CoGoLink";
        if (env("CglPay.is_test")) $configType = "CoGoLinkTest";
        $config = config("co_go_link.$configType");
        if (!$config) return $this->r(100, "查无CoGoLink配置");
        $public_key = "-----BEGIN PUBLIC KEY-----\n" . chunk_split(file_get_contents(root_path() . $config['public_key_path']), 64, "\n") . "-----END PUBLIC KEY-----";
        $checkResult = Rsa::verify(json_encode($this->data, 320), $this->header['signature'], $public_key);
        if ($checkResult !== true) return $this->rFail("验签失败");
        if (isset($this->data['respCode']) && $this->data['respCode'] == 200) {
            if (isset($this->data['data']['transaction']['transactionType'])) {
                // 退款
                if ($this->data['data']['transaction']['transactionType'] == "08") {
                    $this->handleRefund();
                }
            }
        }
        return true;
    }

    public function handleRefund()
    {
        try {
            $this->refundTradeNo = $this->data['data']['transaction']['merchantSerialNo'];
            // 用户是否支付退款成功
            if ($this->data['data']['transaction']['status'] == "00") {
                try {
                    $this->startTrans();
                    $this->data['refundAmount'] = $this->data['data']['transaction']['refundAmount'];
                    $result = $this->refundSuccess();
                    if ($result === true) {
                        $this->commitTrans();
                    } else {
                        $this->rollbackTrans();
                    }
                } catch (\Exception $e) {
                    $this->rollbackTrans();
                    actionException($e, 1);
                }
            }
            if ($this->data['data']['transaction']['status'] == "01") {
                $this->refundFail();
            }
        } catch (\Exception $e) {
            actionException($e, 1);
        }
    }
}