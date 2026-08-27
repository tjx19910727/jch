<?php

namespace app\AppFactory\Kernel\Traits\Payment;

use app\AppFactory\Kernel\Model\Payment\PaymentRequestLogsModel;

trait PaymentRequestLogsTrait
{
    public function addPaymentRequestLogs(array $data)
    {
        $model = PaymentRequestLogsModel::create($data);
        return intval($model->prl_id);
    }

    /**
     * 将设备顶层上报的 GoLink 字段映射到支付请求日志表。
     */
    protected function buildPaymentRequestLog(array $message, array $order)
    {
        $success = $message['payment_status'] === 'TRADE_SUCCESS'
            || strstr($message['payment_status'], 'ERR_STATUS=21');

        return [
            'order_id' => $order['order_id'] ?? 0,
            'trade_no' => $message['trade_no'] ?? '',
            'msg_id' => $message['msg_id'] ?? '',
            'machine_id' => $message['machine_id'] ?? '',
            'provider_code' => 'cogolinks',
            'device_payment_status' => $message['payment_status'] ?? '',
            'request_id' => $this->paymentLogValue($message, 'requestId'),
            'terminal_sn' => $this->paymentLogValue($message, 'sn'),
            'api' => $this->paymentLogValue($message, 'api'),
            'merchant_order_no' => $this->paymentLogValue($message, 'merchantOrderNo'),
            'merchant_serial_no' => $this->paymentLogValue($message, 'merchantSerialNo'),
            'provider_order_no' => $this->paymentLogValue($message, 'orderNo'),
            'transaction_no' => $this->paymentLogValue($message, 'transactionNo'),
            'currency' => strtoupper($message['currency']),
            'total_amount' => $message['totalAmount'],
            'order_amount' => $this->paymentLogValue($message, 'orderAmount'),
            'tip_amount' => $this->paymentLogValue($message, 'tipAmount'),
            'payer_fee' => $this->paymentLogValue($message, 'payerFee'),
            'transaction_status' => $this->paymentLogValue($message, 'transactionStatus'),
            'payment_way' => $this->paymentLogValue($message, 'paymentWay'),
            'payment_type' => $this->paymentLogValue($message, 'paymentType'),
            'payment_sub_type' => $this->paymentLogValue($message, 'paymentSubType'),
            'card_brand' => $this->paymentLogValue($message, 'cardBrand'),
            'card_no_mask' => $this->paymentLogValue($message, 'cardNoMask'),
            'pos_batch_no' => $this->paymentLogValue($message, 'posBatchNo'),
            'pos_serial_no' => $this->paymentLogValue($message, 'posSerialNo'),
            'retrieval_no' => $this->paymentLogValue($message, 'retrievalNo'),
            'auth_code' => $this->paymentLogValue($message, 'authCode'),
            'transaction_time' => $this->paymentLogTime($message['transactionTime'] ?? null),
            'completion_time' => $this->paymentLogTime($message['completionTime'] ?? null),
            'process_status' => $success ? 2 : 3,
        ];
    }

    protected function paymentLogValue(array $message, $key)
    {
        if (!isset($message[$key]) || $message[$key] === '') return null;
        return $message[$key];
    }

    protected function paymentLogTime($value)
    {
        if (!$value) return null;
        $timestamp = strtotime($value);
        return $timestamp === false ? null : gmdate('Y-m-d H:i:s', $timestamp);
    }
}
