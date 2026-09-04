<?php

namespace app\AppFactory\Pay\Notify;

use app\AppFactory\Kernel\Model\Machine\MachineServiceFeeOrderModel;
use app\AppFactory\Kernel\Support\Machine\MachineServiceFeePayConfigService;
use app\AppFactory\Kernel\Support\Machine\MachineServiceFeeService;
use app\AppFactory\Pay\PayBaseClient;

class ServiceFeeAliClient extends PayBaseClient
{
    /**
     * 返回 true 时控制器应答 success；false 时应答 failure 让支付宝重试。
     */
    public function handlePayment(array $message)
    {
        try {
            $orderNo = trim((string)($message['out_trade_no'] ?? ''));
            $order = MachineServiceFeeOrderModel::where('order_no', $orderNo)->find();
            if (!$order || (string)$order['pay_channel'] !== 'ali') {
                throw new \RuntimeException('设备服务费支付宝订单不存在');
            }
            $payee = MachineServiceFeePayConfigService::getPayConfig('ali');
            if (!$this->verifySign($message, $payee)) {
                throw new \RuntimeException('支付宝回调验签失败');
            }
            if (!empty($message['app_id']) && (string)$message['app_id'] !== (string)($payee['app_id'] ?? '')) {
                throw new \RuntimeException('支付宝回调app_id不一致');
            }
            if (!empty($message['seller_id']) && !empty($payee['pid']) && (string)$message['seller_id'] !== (string)$payee['pid']) {
                throw new \RuntimeException('支付宝回调收款账号不一致');
            }
            if (!in_array((string)($message['trade_status'] ?? ''), ['TRADE_SUCCESS', 'TRADE_FINISHED'], true)) {
                throw new \RuntimeException('支付宝交易未成功');
            }
            $amountYuan = MachineServiceFeeService::normalizeYuan($message['total_amount'] ?? '');
            if (!MachineServiceFeeService::moneyEquals($amountYuan, $order['total_amount_cent'])) {
                throw new \RuntimeException('支付宝回调金额不一致');
            }
            $paidAt = !empty($message['gmt_payment']) ? strtotime((string)$message['gmt_payment']) : time();
            MachineServiceFeeService::completePayment(
                $orderNo,
                (string)($message['trade_no'] ?? ''),
                $paidAt,
                $amountYuan
            );
            return true;
        } catch (\Throwable $e) {
            actionException($e, 1);
            return false;
        }
    }

    private function verifySign(array $message, array $payee)
    {
        require_once root_path() . 'extend/AliPay/aop/AopCertClient.php';
        $client = new \AopCertClient();
        $client->alipayrsaPublicKey = $client->getPublicKey($payee['ali_public_key_path']);
        return $client->rsaCheckV1($message, $payee['ali_public_key_path'], (string)($message['sign_type'] ?? 'RSA2'));
    }

}
