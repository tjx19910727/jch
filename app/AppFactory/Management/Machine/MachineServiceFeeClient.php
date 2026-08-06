<?php

namespace app\AppFactory\Management\Machine;

use AliPay\Factory as AliFactory;
use app\AppFactory\Kernel\Model\Machine\MachineServiceFeeModel;
use app\AppFactory\Kernel\Model\Machine\MachineServiceFeeOrderDetailModel;
use app\AppFactory\Kernel\Model\Machine\MachineServiceFeeOrderModel;
use app\AppFactory\Kernel\Support\Machine\MachineServiceFeePayConfigService;
use app\AppFactory\Kernel\Support\Machine\MachineServiceFeeService;
use app\AppFactory\Management\ManagementClient;
use EasyWeChat\Factory as WxFactory;
use think\facade\Db;

class MachineServiceFeeClient extends ManagementClient
{
    /**
     * 批量设置前预览，不落库。
     */
    public function setFeePreview(array $postData)
    {
        try {
            $mIds = $this->parseMIds($postData['m_ids'] ?? []);
            $annualFeeYuan = MachineServiceFeeService::normalizeYuan($postData['annual_fee'] ?? '');
            $machines = $this->getPermittedMachines($mIds);
            $now = time();
            $graceSeconds = MachineServiceFeePayConfigService::GRACE_SECONDS;
            $rows = [];
            foreach ($machines as $machine) {
                $fee = MachineServiceFeeModel::where('m_id', intval($machine['m_id']))->find();
                $old = MachineServiceFeeService::getMachineState($fee, $now);
                $newExpireAt = intval($old['service_expire_at']);
                $willGrantGrace = 0;
                if (!MachineServiceFeeService::isPositiveYuan($annualFeeYuan)) {
                    $newExpireAt = 0;
                } elseif ($newExpireAt <= $now && (!$fee || intval($fee['grace_used']) === 0)) {
                    $newExpireAt = $now + $graceSeconds;
                    $willGrantGrace = 1;
                }
                $rows[] = [
                    'm_id' => intval($machine['m_id']),
                    'machine_id' => (string)$machine['machine_id'],
                    'ao_id' => intval($machine['ao_id']),
                    'old_annual_fee' => $old['annual_fee'],
                    'new_annual_fee' => $annualFeeYuan,
                    'old_expire_at' => intval($old['service_expire_at']),
                    'new_expire_at' => $newExpireAt,
                    'new_expire_time' => $newExpireAt ? date('Y-m-d H:i:s', $newExpireAt) : '',
                    'will_grant_grace' => $willGrantGrace,
                ];
            }
            return $this->r(200, '预览成功', ['list' => $rows, 'count' => count($rows)]);
        } catch (\Throwable $e) {
            return $this->r(100, $e->getMessage());
        }
    }

    /**
     * 批量设置年费。0元设备同步清空期限；正年费的已过期设备仅发一次24小时缓冲。
     */
    public function setFee(array $postData)
    {
        $transactionStarted = false;
        try {
            $mIds = $this->parseMIds($postData['m_ids'] ?? []);
            $annualFeeYuan = MachineServiceFeeService::normalizeYuan($postData['annual_fee'] ?? '');
            $machines = $this->getPermittedMachines($mIds);

            if (!MachineServiceFeeService::isPositiveYuan($annualFeeYuan)) {
                $activeQrCount = MachineServiceFeeOrderDetailModel::alias('d')
                    ->join('machine_service_fee_order o', 'o.msfo_id=d.msfo_id')
                    ->where('d.m_id', 'in', $mIds)
                    ->where('o.pay_status', MachineServiceFeeService::PAY_PROCESSING)
                    ->where('o.qr_expire_at', '>', time())
                    ->count();
                if ($activeQrCount) {
                    throw new \RuntimeException('选中设备存在有效的续费二维码，请待二维码过期后再设为0元');
                }
            }

            $now = time();
            $graceSeconds = MachineServiceFeePayConfigService::GRACE_SECONDS;
            $operatorId = intval($this->manager['manager_id'] ?? 0);
            $changed = [];
            Db::startTrans();
            $transactionStarted = true;
            foreach ($machines as $machine) {
                $mId = intval($machine['m_id']);
                $fee = MachineServiceFeeModel::where('m_id', $mId)->lock(true)->find();
                $refundInProgress = MachineServiceFeeOrderDetailModel::alias('d')
                    ->join('machine_service_fee_order o', 'o.msfo_id=d.msfo_id')
                    ->where('d.m_id', $mId)
                    ->where('o.pay_status', MachineServiceFeeService::PAY_SUCCESS)
                    ->where('o.refund_status', MachineServiceFeeService::REFUND_PROCESSING)
                    ->lock(true)
                    ->count();
                if ($refundInProgress) {
                    throw new \RuntimeException('设备' . $machine['machine_id'] . '存在处理中的服务费退款，暂不能修改年费');
                }
                $oldFeeYuan = $fee ? MachineServiceFeeService::normalizeYuan($fee['annual_fee_cent']) : '0.00';
                $oldExpireAt = $fee ? intval($fee['service_expire_at']) : 0;
                $oldGraceUsed = $fee ? intval($fee['grace_used']) : 0;
                $newExpireAt = $oldExpireAt;
                $newGraceUsed = $oldGraceUsed;
                $graceGrantedAt = $fee ? intval($fee['grace_granted_at']) : 0;
                if (!MachineServiceFeeService::isPositiveYuan($annualFeeYuan)) {
                    $newExpireAt = 0;
                    $newGraceUsed = 0;
                    $graceGrantedAt = 0;
                } elseif ($newExpireAt <= $now && $oldGraceUsed === 0) {
                    $newExpireAt = $now + $graceSeconds;
                    $newGraceUsed = 1;
                    $graceGrantedAt = $now;
                }

                $save = [
                    'm_id' => $mId,
                    'machine_id' => (string)$machine['machine_id'],
                    'ao_id' => intval($machine['ao_id']),
                    'annual_fee_cent' => $annualFeeYuan,
                    'service_expire_at' => $newExpireAt,
                    'grace_used' => $newGraceUsed,
                    'grace_granted_at' => $graceGrantedAt,
                    'update_id' => $operatorId,
                    'update_time' => $now,
                ];
                if ($fee) {
                    if (!MachineServiceFeeService::moneyEquals($oldFeeYuan, $annualFeeYuan)) {
                        $save['price_version'] = intval($fee['price_version']) + 1;
                    }
                    $fee->save($save);
                } else {
                    $save['creator_id'] = $operatorId;
                    $save['price_version'] = 1;
                    $save['create_time'] = $now;
                    MachineServiceFeeModel::create($save);
                }

                MachineServiceFeeService::addLog([
                    'm_id' => $mId,
                    'machine_id' => (string)$machine['machine_id'],
                    'action' => 'set_fee',
                    'old_fee_cent' => $oldFeeYuan,
                    'new_fee_cent' => $annualFeeYuan,
                    'old_expire_at' => $oldExpireAt,
                    'new_expire_at' => $newExpireAt,
                    'operator_id' => $operatorId,
                    'remark' => !MachineServiceFeeService::isPositiveYuan($annualFeeYuan) ? '设为免服务费并清空期限' : '批量设置设备服务年费',
                    'create_time' => $now,
                ]);
                $changed[] = $mId;
            }
            Db::commit();
            $transactionStarted = false;
            return $this->r(200, '设置设备服务年费成功', ['count' => count($changed)]);
        } catch (\Throwable $e) {
            if ($transactionStarted) {
                Db::rollback();
            }
            actionException($e, 1);
            return $this->r(100, $e->getMessage());
        }
    }

    public function renewPreview(array $postData)
    {
        try {
            $items = $this->parseRenewItems($postData);
            $machines = $this->getPermittedMachines(array_keys($items));
            $this->assertSameCustomer($machines);
            $now = time();
            $rows = [];
            $totalAmountYuan = '0.00';
            $totalYears = 0;
            foreach ($machines as $machine) {
                $mId = intval($machine['m_id']);
                $fee = MachineServiceFeeModel::where('m_id', $mId)->find();
                if (!$fee || !MachineServiceFeeService::isPositiveYuan($fee['annual_fee_cent'])) {
                    throw new \RuntimeException('设备' . $machine['machine_id'] . '未设置正数服务年费，不能续费');
                }
                $years = intval($items[$mId]);
                $baseAt = max(intval($fee['service_expire_at']), $now);
                $annualFeeYuan = MachineServiceFeeService::normalizeYuan($fee['annual_fee_cent']);
                $amountYuan = MachineServiceFeeService::multiplyYuan($annualFeeYuan, $years);
                $estimatedAt = MachineServiceFeeService::addNaturalYears($baseAt, $years);
                $rows[] = [
                    'm_id' => $mId,
                    'machine_id' => (string)$machine['machine_id'],
                    'ao_id' => intval($machine['ao_id']),
                    'annual_fee_cent' => $annualFeeYuan,
                    'annual_fee' => $annualFeeYuan,
                    'renew_years' => $years,
                    'amount_cent' => $amountYuan,
                    'amount' => $amountYuan,
                    'service_expire_at' => intval($fee['service_expire_at']),
                    'service_expire_time' => intval($fee['service_expire_at']) ? date('Y-m-d H:i:s', intval($fee['service_expire_at'])) : '',
                    'estimated_new_expire_at' => $estimatedAt,
                    'estimated_new_expire_time' => date('Y-m-d H:i:s', $estimatedAt),
                ];
                $totalAmountYuan = MachineServiceFeeService::addYuan($totalAmountYuan, $amountYuan);
                $totalYears += $years;
            }
            return $this->r(200, '续费预览成功', [
                'list' => $rows,
                'device_count' => count($rows),
                'total_years' => $totalYears,
                'total_amount_cent' => $totalAmountYuan,
                'total_amount' => $totalAmountYuan,
                'expire_is_estimate' => 1,
            ]);
        } catch (\Throwable $e) {
            return $this->r(100, $e->getMessage());
        }
    }

    /**
     * 创建订单不检查同设备待支付订单。每次调用都会生成一张新订单。
     */
    public function createRenewOrder(array $postData)
    {
        $transactionStarted = false;
        try {
            $channel = strtolower(trim((string)($postData['pay_channel'] ?? '')));
            if (!in_array($channel, ['wx', 'ali'], true)) {
                throw new \InvalidArgumentException('请选择微信支付或支付宝支付');
            }
            // 下单前先校验固定商户配置，避免配置错误时产生无法支付的订单。
            $payee = MachineServiceFeePayConfigService::getPayConfig($channel);
            $payMethod = $channel === 'wx' ? 'native' : 'precreate';
            $items = $this->parseRenewItems($postData);
            $machines = $this->getPermittedMachines(array_keys($items));
            $this->assertSameCustomer($machines);
            $now = time();
            $operatorId = intval($this->manager['manager_id'] ?? 0);
            $orderNo = MachineServiceFeeService::makeNo('SF');
            $detailRows = [];
            $totalAmountYuan = '0.00';
            $totalYears = 0;

            Db::startTrans();
            $transactionStarted = true;
            foreach ($machines as $machine) {
                $mId = intval($machine['m_id']);
                $fee = MachineServiceFeeModel::where('m_id', $mId)->lock(true)->find();
                if (!$fee || !MachineServiceFeeService::isPositiveYuan($fee['annual_fee_cent'])) {
                    throw new \RuntimeException('设备' . $machine['machine_id'] . '未设置正数服务年费，不能续费');
                }
                $years = intval($items[$mId]);
                $annualFeeYuan = MachineServiceFeeService::normalizeYuan($fee['annual_fee_cent']);
                $amountYuan = MachineServiceFeeService::multiplyYuan($annualFeeYuan, $years);
                $baseAt = max(intval($fee['service_expire_at']), $now);
                $detailRows[] = [
                    'order_no' => $orderNo,
                    'm_id' => $mId,
                    'machine_id' => (string)$machine['machine_id'],
                    'ao_id' => intval($machine['ao_id']),
                    'annual_fee_cent' => $annualFeeYuan,
                    'renew_years' => $years,
                    'amount_cent' => $amountYuan,
                    'preview_expire_at' => intval($fee['service_expire_at']),
                    'estimated_new_expire_at' => MachineServiceFeeService::addNaturalYears($baseAt, $years),
                    'create_time' => $now,
                    'update_time' => $now,
                ];
                $totalAmountYuan = MachineServiceFeeService::addYuan($totalAmountYuan, $amountYuan);
                $totalYears += $years;
            }
            if (!MachineServiceFeeService::isPositiveYuan($totalAmountYuan)) {
                throw new \RuntimeException('续费订单金额必须大于0');
            }

            $order = MachineServiceFeeOrderModel::create([
                'order_no' => $orderNo,
                'payer_ao_id' => intval($machines[0]['ao_id']),
                'device_count' => count($detailRows),
                'total_years' => $totalYears,
                'total_amount_cent' => $totalAmountYuan,
                'pay_channel' => $channel,
                'pay_method' => $payMethod,
                'pay_status' => MachineServiceFeeService::PAY_PENDING,
                'refund_status' => MachineServiceFeeService::REFUND_NONE,
                'creator_id' => $operatorId,
                'create_time' => $now,
                'update_time' => $now,
            ]);
            foreach ($detailRows as $row) {
                $row['msfo_id'] = intval($order['msfo_id']);
                MachineServiceFeeOrderDetailModel::create($row);
            }
            Db::commit();
            $transactionStarted = false;

            // 订单落库成功后直接向用户选择的渠道申请二维码，无需前端再次请求。
            $expireAt = time() + MachineServiceFeePayConfigService::QR_EXPIRE_SECONDS;
            $qrCodeUrl = $channel === 'wx'
                ? $this->createWxQr($order, $payee, $expireAt)
                : $this->createAliQr($order, $payee, $expireAt);
            MachineServiceFeeOrderModel::where('msfo_id', intval($order['msfo_id']))->update([
                'pay_status' => MachineServiceFeeService::PAY_PROCESSING,
                'qr_code_url' => $qrCodeUrl,
                'qr_expire_at' => $expireAt,
                'update_time' => time(),
            ]);
            $order = MachineServiceFeeOrderModel::where('msfo_id', intval($order['msfo_id']))->find();
            return $this->r(200, '续费订单及支付二维码创建成功', $this->formatQrResponse($order));
        } catch (\Throwable $e) {
            if ($transactionStarted) {
                Db::rollback();
            }
            actionException($e, 1);
            return $this->r(100, $e->getMessage());
        }
    }

    public function getPayMethods()
    {
        return $this->r(200, '查询成功', $this->availablePayMethods());
    }

    public function createPayQr(array $postData)
    {
        try {
            $orderNo = trim((string)($postData['order_no'] ?? ''));
            $channel = strtolower(trim((string)($postData['pay_channel'] ?? '')));
            if (!$orderNo || !in_array($channel, ['wx', 'ali'], true)) {
                throw new \InvalidArgumentException('订单号或支付渠道无效');
            }
            $order = $this->getPermittedOrder($orderNo);
            if (intval($order['pay_status']) === MachineServiceFeeService::PAY_SUCCESS) {
                return $this->r(200, '订单已支付', $this->formatOrder($order));
            }
            if (intval($order['pay_status']) === MachineServiceFeeService::PAY_PROCESSING) {
                if (intval($order['qr_expire_at']) > time() && $order['pay_channel'] === $channel && $order['qr_code_url']) {
                    return $this->r(200, '支付二维码创建成功', $this->formatQrResponse($order));
                }
                throw new \RuntimeException('当前订单二维码已失效，请重新创建续费订单');
            }
            if (intval($order['pay_status']) !== MachineServiceFeeService::PAY_PENDING) {
                throw new \RuntimeException('当前订单状态不允许发起支付');
            }

            $payee = MachineServiceFeePayConfigService::getPayConfig($channel);
            $expireSeconds = MachineServiceFeePayConfigService::QR_EXPIRE_SECONDS;
            $expireAt = time() + $expireSeconds;
            $qrCodeUrl = $channel === 'wx'
                ? $this->createWxQr($order, $payee, $expireAt)
                : $this->createAliQr($order, $payee, $expireAt);

            MachineServiceFeeOrderModel::where('msfo_id', intval($order['msfo_id']))->update([
                'pay_channel' => $channel,
                'pay_method' => $channel === 'wx' ? 'native' : 'precreate',
                'pay_status' => MachineServiceFeeService::PAY_PROCESSING,
                'qr_code_url' => $qrCodeUrl,
                'qr_expire_at' => $expireAt,
                'update_time' => time(),
            ]);
            $order = MachineServiceFeeOrderModel::where('msfo_id', intval($order['msfo_id']))->find();
            return $this->r(200, '支付二维码创建成功', $this->formatQrResponse($order));
        } catch (\Throwable $e) {
            actionException($e, 1);
            return $this->r(100, $e->getMessage());
        }
    }

    public function getPayStatus(array $postData)
    {
        try {
            $order = $this->getPermittedOrder(trim((string)($postData['order_no'] ?? '')));
            return $this->r(200, '查询成功', $this->formatOrder($order));
        } catch (\Throwable $e) {
            return $this->r(100, $e->getMessage());
        }
    }

    /**
     * 续费订单页只返回曾支付成功的订单；已退款订单仍保留显示。
     */
    public function getSuccessOrderList(array $postData)
    {
        try {
            $pageSize = max(1, min(100, intval($postData['pageNum'] ?? 20)));
            $query = MachineServiceFeeOrderModel::alias('o')->where('o.pay_status', MachineServiceFeeService::PAY_SUCCESS)->field('o.*');
            $permitted = $this->resolvePermittedMachineIds();
            if ($permitted !== null) {
                if (!$permitted) {
                    return $this->r(200, '查询成功', ['data' => [], 'total' => 0]);
                }
                $safeMIds = implode(',', array_map('intval', $permitted));
                $query->whereRaw("NOT EXISTS (SELECT 1 FROM machine_service_fee_order_detail d WHERE d.msfo_id=o.msfo_id AND d.m_id NOT IN ({$safeMIds}))");
            }
            if (!empty($postData['order_no'])) {
                $query->where('o.order_no', 'like', '%' . trim((string)$postData['order_no']) . '%');
            }
            if (isset($postData['refund_status']) && $postData['refund_status'] !== '') {
                $query->where('o.refund_status', intval($postData['refund_status']));
            }
            if (!empty($postData['paid_at']) && strpos((string)$postData['paid_at'], '~') !== false) {
                list($start, $end) = explode('~', (string)$postData['paid_at'], 2);
                $query->whereBetween('o.paid_at', [strtotime(trim($start)), strtotime(trim($end) . ' 23:59:59')]);
            }
            $list = $query->order('o.paid_at desc,o.msfo_id desc')->paginate($pageSize, false, ['query' => request()->param()]);
            $list->each(function ($item) {
                return $this->formatOrder($item, true);
            });
            return $this->rQ($list);
        } catch (\Throwable $e) {
            return $this->r(100, $e->getMessage());
        }
    }

    public function getSuccessOrderFind(array $postData)
    {
        try {
            $order = $this->getPermittedOrder(trim((string)($postData['order_no'] ?? '')), true);
            if (intval($order['pay_status']) !== MachineServiceFeeService::PAY_SUCCESS) {
                throw new \RuntimeException('该订单未支付成功');
            }
            return $this->r(200, '查询成功', $this->formatOrder($order, true));
        } catch (\Throwable $e) {
            return $this->r(100, $e->getMessage());
        }
    }

    /**
     * V1仅支持整单全额退款。
     */
    public function refundOrder(array $postData)
    {
        $refund = [];
        $gatewayAccepted = false;
        try {
            $orderNo = trim((string)($postData['order_no'] ?? ''));
            if (!$orderNo) {
                throw new \InvalidArgumentException('订单号不能为空');
            }
            $this->getPermittedOrder($orderNo, true);
            $domainResult = MachineServiceFeeService::beginFullRefund(
                $orderNo,
                $postData['reason'] ?? '管理端整单退款',
                intval($this->manager['manager_id'] ?? 0)
            );
            $order = $domainResult['order'];
            $refund = $domainResult['refund'];
            if (!$refund) {
                throw new \RuntimeException('退款记录创建失败');
            }
            if ($domainResult['idempotent']) {
                return $this->r(200, '退款处理中', $refund);
            }

            $payee = MachineServiceFeePayConfigService::getPayConfig($order['pay_channel']);
            if ($order['pay_channel'] === 'wx') {
                $result = $this->requestWxRefund($order, $refund, $payee);
                $gatewayAccepted = true;
                return $this->r(200, '微信退款申请成功', ['refund_no' => $refund['refund_no'], 'gateway' => $result]);
            }
            if ($order['pay_channel'] === 'ali') {
                $result = $this->requestAliRefund($order, $refund, $payee);
                $gatewayAccepted = true;
                MachineServiceFeeService::completeRefund($refund['refund_no'], (string)($result['trade_no'] ?? ''));
                return $this->r(200, '支付宝退款成功', ['refund_no' => $refund['refund_no']]);
            }
            throw new \RuntimeException('原支付渠道不支持自动退款');
        } catch (\Throwable $e) {
            if (!empty($refund['refund_no']) && !$gatewayAccepted) {
                try {
                    MachineServiceFeeService::failRefund($refund['refund_no'], $e->getMessage());
                } catch (\Throwable $ignore) {
                    actionException($ignore, 1);
                }
            }
            actionException($e, 1);
            return $this->r(100, $e->getMessage());
        }
    }

    private function parseMIds($value)
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : explode(',', $value);
        }
        if (!is_array($value)) {
            throw new \InvalidArgumentException('请选择设备');
        }
        $mIds = array_values(array_unique(array_filter(array_map('intval', $value), function ($id) {
            return $id > 0;
        })));
        if (!$mIds) {
            throw new \InvalidArgumentException('请选择设备');
        }
        if (count($mIds) > 100) {
            throw new \InvalidArgumentException('每次最多选择100台设备');
        }
        sort($mIds, SORT_NUMERIC);
        return $mIds;
    }

    private function parseRenewItems(array $postData)
    {
        $rawItems = $postData['items'] ?? [];
        if (is_string($rawItems)) {
            $rawItems = json_decode($rawItems, true);
        }
        if (!$rawItems && isset($postData['m_ids'])) {
            $rawItems = [];
            foreach ($this->parseMIds($postData['m_ids']) as $mId) {
                $rawItems[] = ['m_id' => $mId, 'renew_years' => intval($postData['renew_years'] ?? 1)];
            }
        }
        if (!is_array($rawItems) || !$rawItems) {
            throw new \InvalidArgumentException('请选择续费设备');
        }
        $maxYears = MachineServiceFeePayConfigService::MAX_RENEW_YEARS;
        $items = [];
        foreach ($rawItems as $key => $row) {
            if (is_array($row)) {
                $mId = intval($row['m_id'] ?? 0);
                $years = intval($row['renew_years'] ?? 0);
            } else {
                $mId = intval($key);
                $years = intval($row);
            }
            if ($mId <= 0 || $years < 1 || $years > $maxYears) {
                throw new \InvalidArgumentException('设备ID或续费年数无效，年数范围为1-' . $maxYears);
            }
            if (isset($items[$mId])) {
                throw new \InvalidArgumentException('同一设备不能在一张订单里重复出现');
            }
            $items[$mId] = $years;
        }
        if (count($items) > 100) {
            throw new \InvalidArgumentException('每张订单最多100台设备');
        }
        ksort($items, SORT_NUMERIC);
        return $items;
    }

    private function getPermittedMachines(array $mIds)
    {
        $permitted = $this->resolvePermittedMachineIds();
        if ($permitted !== null && array_diff($mIds, $permitted)) {
            throw new \RuntimeException('选中设备中包含无权操作的设备');
        }
        $rows = Db::name('machine')->where('m_id', 'in', $mIds)
            ->field('m_id,machine_id,machine_name,ao_id')->order('m_id asc')->select()->toArray();
        if (count($rows) !== count($mIds)) {
            throw new \RuntimeException('选中设备不存在或已删除');
        }
        return $rows;
    }

    private function resolvePermittedMachineIds()
    {
        // Keep service-fee operations aligned with the device-list account scope.
        return $this->app->machine->resolvePermittedMachineIds();
    }

    private function assertSameCustomer(array $machines)
    {
        $aoIds = array_values(array_unique(array_map(function ($machine) {
            return intval($machine['ao_id']);
        }, $machines)));
        if (count($aoIds) !== 1) {
            throw new \RuntimeException('一张续费订单只能包含同一客户的设备');
        }
    }

    private function getPermittedOrder($orderNo, $successOnly = false)
    {
        if (!$orderNo) {
            throw new \InvalidArgumentException('订单号不能为空');
        }
        $order = MachineServiceFeeOrderModel::where('order_no', $orderNo)->find();
        if (!$order || ($successOnly && intval($order['pay_status']) !== MachineServiceFeeService::PAY_SUCCESS)) {
            throw new \RuntimeException('设备服务费续费订单不存在');
        }
        $permitted = $this->resolvePermittedMachineIds();
        if ($permitted !== null) {
            $detailMIds = MachineServiceFeeOrderDetailModel::where('msfo_id', intval($order['msfo_id']))->column('m_id');
            if (array_diff(array_map('intval', $detailMIds), $permitted)) {
                throw new \RuntimeException('无权查看或操作该订单');
            }
        }
        return $order;
    }

    private function availablePayMethods()
    {
        return [
            [
                'pay_channel' => 'wx',
                'pay_channel_desc' => '微信支付',
                'pay_method' => 'native',
            ],
            [
                'pay_channel' => 'ali',
                'pay_channel_desc' => '支付宝',
                'pay_method' => 'precreate',
            ],
        ];
    }

    private function createWxQr($order, array $payee, $expireAt)
    {
        $app = WxFactory::payment($payee);
        $params = [
            'body' => '设备服务费续费',
            'out_trade_no' => (string)$order['order_no'],
            'time_expire' => date('YmdHis', $expireAt),
            'total_fee' => MachineServiceFeeService::yuanToWxCent($order['total_amount_cent']),
            'notify_url' => $this->getUrl('/pay/notify.service_fee_wx/paymentNotify'),
            'trade_type' => 'NATIVE',
            'attach' => 'service_fee|' . $order['order_no'],
        ];
        actionLog($params, '设备服务费微信统一下单参数');
        $result = $app->order->unify($params);
        actionLog($result, '设备服务费微信统一下单结果');
        if (($result['return_code'] ?? '') !== 'SUCCESS' || ($result['result_code'] ?? '') !== 'SUCCESS' || empty($result['code_url'])) {
            throw new \RuntimeException((string)($result['err_code_des'] ?? $result['return_msg'] ?? '微信支付二维码创建失败'));
        }
        return (string)$result['code_url'];
    }

    private function createAliQr($order, array $payee, $expireAt)
    {
        $payee['notifyUrl'] = $this->getUrl('/pay/notify.service_fee_ali/paymentNotify');
        $app = AliFactory::trade($payee);
        $result = $app->trade->preCreate([
            'out_trade_no' => (string)$order['order_no'],
            'total_amount' => MachineServiceFeeService::normalizeYuan($order['total_amount_cent']),
            'subject' => '设备服务费续费',
            'notifyUrl' => $payee['notifyUrl'],
        ], ['timeout_express' => max(1, intval(ceil(($expireAt - time()) / 60))) . 'm']);
        actionLog($result, '设备服务费支付宝预下单结果');
        if (($result['code'] ?? '') !== '10000' || empty($result['qr_code'])) {
            throw new \RuntimeException((string)($result['sub_msg'] ?? $result['msg'] ?? '支付宝支付二维码创建失败'));
        }
        return (string)$result['qr_code'];
    }

    private function requestWxRefund(array $order, array $refund, array $payee)
    {
        $app = WxFactory::payment($payee);
        $notifyUrl = $this->getUrl('/pay/notify.service_fee_wx/refundNotify/refund_no/' . $refund['refund_no']);
        $result = $app->refund->byOutTradeNumber(
            $order['order_no'],
            $refund['refund_no'],
            MachineServiceFeeService::yuanToWxCent($order['total_amount_cent']),
            MachineServiceFeeService::yuanToWxCent($refund['refund_amount_cent']),
            ['refund_desc' => $refund['reason'], 'notify_url' => $notifyUrl]
        );
        actionLog($result, '设备服务费微信退款结果');
        if (($result['return_code'] ?? '') !== 'SUCCESS' || ($result['result_code'] ?? '') !== 'SUCCESS') {
            throw new \RuntimeException((string)($result['err_code_des'] ?? $result['return_msg'] ?? '微信退款申请失败'));
        }
        return $result;
    }

    private function requestAliRefund(array $order, array $refund, array $payee)
    {
        $app = AliFactory::trade($payee);
        $result = $app->trade->refund([
            'out_trade_no' => $order['order_no'],
            'refund_amount' => MachineServiceFeeService::normalizeYuan($refund['refund_amount_cent']),
            'out_request_no' => $refund['refund_no'],
            'refund_reason' => $refund['reason'],
        ]);
        actionLog($result, '设备服务费支付宝退款结果');
        if (($result['code'] ?? '') !== '10000') {
            throw new \RuntimeException((string)($result['sub_msg'] ?? $result['msg'] ?? '支付宝退款失败'));
        }
        return $result;
    }

    private function formatQrResponse($order)
    {
        $order = is_object($order) && method_exists($order, 'toArray') ? $order->toArray() : (array)$order;
        return [
            'order_no' => (string)$order['order_no'],
            'pay_channel' => (string)$order['pay_channel'],
            'pay_method' => (string)$order['pay_method'],
            'qr_code_url' => (string)$order['qr_code_url'],
            'qr_expire_at' => intval($order['qr_expire_at']),
            'qr_expire_time' => intval($order['qr_expire_at']) ? date('Y-m-d H:i:s', intval($order['qr_expire_at'])) : '',
            'total_amount_cent' => MachineServiceFeeService::normalizeYuan($order['total_amount_cent']),
            'total_amount' => MachineServiceFeeService::normalizeYuan($order['total_amount_cent']),
            'device_count' => intval($order['device_count'] ?? 0),
            'total_years' => intval($order['total_years'] ?? 0),
            'pay_status' => intval($order['pay_status']),
        ];
    }

    private function formatOrder($order, $withDetails = false)
    {
        $isModel = is_object($order) && method_exists($order, 'toArray');
        $data = $isModel ? $order->toArray() : (array)$order;
        $data['total_amount_cent'] = MachineServiceFeeService::normalizeYuan($data['total_amount_cent'] ?? 0);
        $data['total_amount'] = $data['total_amount_cent'];
        $data['pay_status_desc'] = [1 => '待支付', 2 => '支付中', 3 => '已支付', 4 => '已关闭', 5 => '支付失败'][intval($data['pay_status'] ?? 0)] ?? '未知';
        $data['refund_status_desc'] = [0 => '未退款', 1 => '退款中', 2 => '已退款', 3 => '退款失败'][intval($data['refund_status'] ?? 0)] ?? '未知';
        $data['paid_time'] = intval($data['paid_at'] ?? 0) ? date('Y-m-d H:i:s', intval($data['paid_at'])) : '';
        if ($withDetails) {
            $details = MachineServiceFeeOrderDetailModel::where('msfo_id', intval($data['msfo_id']))->order('msfod_id asc')->select();
            $data['details'] = [];
            foreach ($details as $detail) {
                $row = $detail->toArray();
                $row['annual_fee_cent'] = MachineServiceFeeService::normalizeYuan($row['annual_fee_cent']);
                $row['annual_fee'] = $row['annual_fee_cent'];
                $row['amount_cent'] = MachineServiceFeeService::normalizeYuan($row['amount_cent']);
                $row['amount'] = $row['amount_cent'];
                foreach (['preview_expire_at', 'estimated_new_expire_at', 'effective_old_expire_at', 'effective_start_at', 'effective_end_at', 'old_grace_granted_at', 'refunded_at'] as $field) {
                    $row[$field . '_time'] = intval($row[$field] ?? 0) ? date('Y-m-d H:i:s', intval($row[$field])) : '';
                }
                $data['details'][] = $row;
            }
        }
        if ($isModel) {
            foreach ($data as $key => $value) {
                $order[$key] = $value;
            }
            return $order;
        }
        return $data;
    }

}
