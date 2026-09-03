<?php

namespace app\AppFactory\Pay\Notify;

use app\AppFactory\Kernel\Model\Machine\MachineServiceFeeOrderModel;
use app\AppFactory\Kernel\Model\Machine\MachineServiceFeeRefundModel;
use app\AppFactory\Kernel\Support\Machine\MachineServiceFeePayConfigService;
use app\AppFactory\Kernel\Support\Machine\MachineServiceFeeService;
use app\AppFactory\Pay\PayBaseClient;
use EasyWeChat\Factory;

class ServiceFeeWxClient extends PayBaseClient
{
    public function handlePayment(array $message)
    {
        $orderNo = trim((string)($message['out_trade_no'] ?? ''));
        $order = MachineServiceFeeOrderModel::where('order_no', $orderNo)->find();
        if (!$order || (string)$order['pay_channel'] !== 'wx') {
            throw new \RuntimeException('设备服务费微信订单不存在');
        }
        $app = Factory::payment(MachineServiceFeePayConfigService::getPayConfig('wx'));
        $response = $app->handlePaidNotify(function ($notify, $fail) use ($order) {
            try {
                if (($notify['return_code'] ?? '') !== 'SUCCESS' || ($notify['result_code'] ?? '') !== 'SUCCESS') {
                    return $fail('微信支付结果未成功');
                }
                if ((string)($notify['out_trade_no'] ?? '') !== (string)$order['order_no']) {
                    return $fail('订单号不一致');
                }
                if (!isset($notify['total_fee']) || intval($notify['total_fee']) !== MachineServiceFeeService::yuanToWxCent($order['total_amount_cent'])) {
                    return $fail('支付金额不一致');
                }
                if (!empty($notify['attach'])) {
                    $attach = explode('|', (string)$notify['attach']);
                    if (($attach[0] ?? '') !== 'service_fee' || ($attach[1] ?? '') !== (string)$order['order_no']) {
                        return $fail('支付附加数据不一致');
                    }
                }
                $paidAt = time();
                if (!empty($notify['time_end'])) {
                    $paidTime = \DateTime::createFromFormat('YmdHis', (string)$notify['time_end']);
                    if ($paidTime) {
                        $paidAt = $paidTime->getTimestamp();
                    }
                }
                MachineServiceFeeService::completePayment(
                    (string)$order['order_no'],
                    (string)($notify['transaction_id'] ?? ''),
                    $paidAt,
                    MachineServiceFeeService::wxCentToYuan($notify['total_fee'])
                );
                return true;
            } catch (\Throwable $e) {
                actionException($e, 1);
                return $fail($e->getMessage());
            }
        });
        $response->send();
    }

    public function handleRefund(array $query)
    {
        $refundNo = trim((string)($query['refund_no'] ?? ''));
        $refund = MachineServiceFeeRefundModel::where('refund_no', $refundNo)->find();
        if (!$refund || (string)$refund['pay_channel'] !== 'wx') {
            throw new \RuntimeException('设备服务费退款记录不存在');
        }
        $app = Factory::payment(MachineServiceFeePayConfigService::getPayConfig('wx'));
        $response = $app->handleRefundedNotify(function ($result, $notify, $alert) use ($refundNo, $refund) {
            try {
                actionLog(['result' => $result, 'notify' => $notify, 'alert' => $alert], '设备服务费微信退款回调');
                if (($result['return_code'] ?? '') !== 'SUCCESS') {
                    return false;
                }
                if ((string)($notify['out_refund_no'] ?? '') !== $refundNo) {
                    return false;
                }
                if (isset($notify['refund_fee']) && intval($notify['refund_fee']) !== MachineServiceFeeService::yuanToWxCent($refund['refund_amount_cent'])) {
                    actionLog($notify, '设备服务费微信退款回调金额不一致');
                    return false;
                }
                $status = strtoupper((string)($notify['refund_status'] ?? ''));
                if ($status === 'SUCCESS') {
                    MachineServiceFeeService::completeRefund($refundNo, (string)($notify['refund_id'] ?? ''));
                    return true;
                }
                if (in_array($status, ['CHANGE', 'REFUNDCLOSE', 'FAIL'], true)) {
                    MachineServiceFeeService::failRefund($refundNo, '微信退款状态：' . $status);
                }
                return true;
            } catch (\Throwable $e) {
                actionException($e, 1);
                return false;
            }
        });
        $response->send();
    }

}
