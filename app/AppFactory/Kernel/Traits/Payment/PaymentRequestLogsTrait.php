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
     * 将payment_data中的GoLink字段映射到支付请求日志表。
     */
    protected function buildPaymentRequestLog(array $message, array $order, array $paymentData)
    {
        $success = $message['payment_status'] === 'TRADE_SUCCESS'
            || strstr($message['payment_status'], 'ERR_STATUS=21');
        $currency = $this->paymentLogValue($paymentData, 'currency');

        return [
            'order_id' => $order['order_id'] ?? 0,
            'trade_no' => $message['trade_no'] ?? '',
            'msg_id' => $message['msg_id'] ?? '',
            'machine_id' => $message['machine_id'] ?? '',
            'provider_code' => 'cogolinks',
            'device_payment_status' => $message['payment_status'] ?? '',
            'request_id' => $this->paymentLogValue($paymentData, 'requestId'),
            'terminal_sn' => $this->paymentLogValue($paymentData, 'sn'),
            'api' => $this->paymentLogValue($paymentData, 'api'),
            'merchant_order_no' => $this->paymentLogValue($paymentData, 'merchantOrderNo'),
            'merchant_serial_no' => $this->paymentLogValue($paymentData, 'merchantSerialNo'),
            'provider_order_no' => $this->paymentLogValue($paymentData, 'orderNo'),
            'transaction_no' => $this->paymentLogValue($paymentData, 'transactionNo'),
            'currency' => $currency === null ? null : strtoupper($currency),
            'total_amount' => $this->paymentLogValue($paymentData, 'totalAmount'),
            'order_amount' => $this->paymentLogValue($paymentData, 'orderAmount'),
            'tip_amount' => $this->paymentLogValue($paymentData, 'tipAmount'),
            'payer_fee' => $this->paymentLogValue($paymentData, 'payerFee'),
            'transaction_status' => $this->paymentLogValue($paymentData, 'transactionStatus'),
            'payment_way' => $this->paymentLogValue($paymentData, 'paymentWay'),
            'payment_type' => $this->paymentLogValue($paymentData, 'paymentType'),
            'payment_sub_type' => $this->paymentLogValue($paymentData, 'paymentSubType'),
            'card_brand' => $this->paymentLogValue($paymentData, 'cardBrand'),
            'card_no_mask' => $this->paymentLogValue($paymentData, 'cardNoMask'),
            'pos_batch_no' => $this->paymentLogValue($paymentData, 'posBatchNo'),
            'pos_serial_no' => $this->paymentLogValue($paymentData, 'posSerialNo'),
            'retrieval_no' => $this->paymentLogValue($paymentData, 'retrievalNo'),
            'auth_code' => $this->paymentLogValue($paymentData, 'authCode'),
            'transaction_time' => $this->paymentLogTime($paymentData['transactionTime'] ?? null),
            'completion_time' => $this->paymentLogTime($paymentData['completionTime'] ?? null),
            'process_status' => $success ? 2 : 3,
        ];
    }

    protected function paymentLogValue(array $message, $key)
    {
        if (!isset($message[$key]) || $message[$key] === '' || !is_scalar($message[$key])) return null;
        return $message[$key];
    }

    protected function paymentLogTime($value)
    {
        if (!$value) return null;
        $timestamp = strtotime($value);
        return $timestamp === false ? null : gmdate('Y-m-d H:i:s', $timestamp);
    }
}
