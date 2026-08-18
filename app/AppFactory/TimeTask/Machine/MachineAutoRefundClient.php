<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/4/29
 * Time: 15:30
 */

namespace app\AppFactory\TimeTask\Machine;

use app\AppFactory\AppFactory;
use app\AppFactory\TimeTask\TimeTaskBase;
use think\facade\Cache;
use think\facade\Db;

class MachineAutoRefundClient extends TimeTaskBase
{
    /**
     * 每3分钟执行一次：自动退款之前，先重发出货指令（MQ超时未回执的订单）。
     * 若设备MQ消费异常导致outGoods指令丢失，通过MQ重发并HTTP兜底，
     * 避免直接退款造成客户未付款即退款/设备本体已出货的数据不一致。
     */
    public function retryOutGoods()
    {
        $now = time();
        $limit = intval(input('limit', 50));
        if ($limit <= 0) {
            $limit = 50;
        }
        $where[] = ['so.pay_status', '=', 3];
        $where[] = ['so.out_status', 'in', [2, 3]];
        $where[] = ['so.http_out_status', '=', 1];
        $where[] = ['so.pay_time', '<', $now - 60];
        $where[] = ['so.pay_time', '>=', $now - 600];
        $where[] = ['sod.success_quantity', '=', 0]; // 幂等：子订单未成功出货才重发
        $where[] = ['sod.fail_quantity', '=', 0];    // 幂等：子订单未失败出货才重发（已失败需人工处理）
        $rows = Db::name('sale_orders_details')->alias('sod')
            ->join('sale_orders so', 'so.order_id = sod.order_id')
            ->where($where)
            ->field('so.order_id,so.trade_no,so.machine_id,so.ao_id,sod.sod_id,sod.success_quantity,sod.fail_quantity')
            ->group('so.order_id,so.trade_no,so.machine_id,so.ao_id,sod.sod_id,sod.success_quantity,sod.fail_quantity')
            ->order('so.pay_time asc,so.order_id asc')
            ->limit($limit)
            ->select()
            ->toArray();
        actionLog(Db::getLastSql(), 'retryOutGoods.rows.sql', 'retryOutGoods');

        if (!$rows) {
            actionLog('没有需要重发出货指令的订单', 'retryOutGoods', 'retryOutGoods');
            return '重发出货指令处理完成：0条';
        }

        $machines = [];
        $success = 0;
        $skip = 0;
        $fail = 0;
        foreach ($rows as $row) {
            $orderId = intval($row['order_id']);
            $machineId = trim($row['machine_id'] ?? '');
            if ($machineId === '') {
                $skip++;
                continue;
            }

            $lockKey = 'retry_out_goods_lock_' . $orderId;
            if (!$this->acquireAutoRefundLock($lockKey, 150)) {
                $skip++;
                continue;
            }

            try {
                // 二次确认：仍未收到出货回执
                $order = Db::name('sale_orders')
                    ->where('order_id', $orderId)
                    ->field('order_id,trade_no,machine_id,out_status,pay_status,pay_time,http_out_status')
                    ->find();
                if (!$order || intval($order['pay_status']) !== 3 || !in_array(intval($order['out_status']), [2, 3], true)) {
                    $skip++;
                    continue;
                }
                if (intval($order['http_out_status']) !== 1) {
                    $skip++;
                    continue;
                }

                $machine = $machines[$machineId] ?? null;
                if (!$machine) {
                    $machine = Db::name('machine')
                        ->where('machine_id', $machineId)
                        ->field('machine_id,mac_address,signKey,online')
                        ->find();
                    $machines[$machineId] = $machine;
                }
                if (!$machine || intval($machine['online']) !== 1) {
                    $skip++;
                    continue;
                }

                // ★ 幂等二次确认：子订单已有成功/失败出货数量则跳过，避免MQ恢复后重复出货
                $detailHasOut = Db::name('sale_orders_details')
                    ->where('order_id', $orderId)
                    ->whereRaw('success_quantity > 0 OR fail_quantity > 0')
                    ->find();
                if ($detailHasOut) {
                    $skip++;
                    actionLog([
                        'order_id' => $orderId,
                        'sod_id' => $detailHasOut['sod_id'] ?? 0,
                    ], 'retryOutGoods命中幂等保护，子订单已有出货结果，跳过重发', 'retryOutGoods');
                    continue;
                }

                // 重新构建并下发 outGoods 指令（复用 AfterOrderPaymentTrait::outGoods 逻辑）
                $order = Db::name('sale_orders')->where('order_id', $orderId)->find();
                $order['details'] = Db::name('sale_orders_details')
                    ->where('order_id', $orderId)
                    ->select()
                    ->toArray();
                $result = $this->sendOutGoodsByMachine($machineId, $order);
                $result = obj2arr($result);
                $state = is_array($result) ? intval($result['state'] ?? 0) : 0;
                if ($state == 200 || $state == 0) {
                    $success++;
                    actionLog([
                        'order_id' => $orderId,
                        'trade_no' => $order['trade_no'] ?? '',
                        'machine_id' => $machineId,
                        'result' => $result,
                    ], '重发出货指令成功', 'retryOutGoods');
                } else {
                    $fail++;
                    actionLog([
                        'order_id' => $orderId,
                        'trade_no' => $order['trade_no'] ?? '',
                        'machine_id' => $machineId,
                        'result' => $result,
                    ], '重发出货指令失败', 'retryOutGoods');
                }
            } catch (\Throwable $e) {
                $fail++;
                actionException($e, 1, 'retryOutGoods');
            } finally {
                $this->releaseAutoRefundLock($lockKey);
            }
        }

        return "重发出货指令处理完成：成功{$success}，跳过{$skip}，失败{$fail}";
    }

    /**
     * 组装并下发 outGoods 指令到设备。
     * 复刻 AfterOrderPaymentTrait::outGoods 构造逻辑，避免在定时任务中引入完整支付上下文。
     * @param string $machineId
     * @param array $order
     * @return mixed
     */
    protected function sendOutGoodsByMachine($machineId, array $order)
    {
        $machine = Db::name('machine')
            ->where('machine_id', $machineId)
            ->field('machine_id,mac_address,signKey,online,current_status')
            ->find();
        if (!$machine || intval($machine['online']) !== 1) {
            return ['state' => 100, 'msg' => '设备不在线'];
        }
        if (!empty($machine['current_status']) && $machine['current_status'] !== 'normal') {
            return ['state' => 100, 'msg' => '设备当前状态非normal，暂不重发出货'];
        }

        $details = $order['details'] ?? [];
        if (!$details || !is_array($details)) {
            return ['state' => 100, 'msg' => '订单无商品明细'];
        }

        $outArr = [];
        foreach ($details as $v) {
            if (empty($v['mc_id'])) {
                $v['g_type'] = 1;
            }
            if (empty($v['mc_id']) && intval($v['g_type']) == 1) {
                $pos = intval($v['channel_position'] ?? 1);
                $outArr[$pos][] = [
                    'channel_code' => $v['channel_code'] ?? '',
                    'quantity' => $v['quantity'] ?? 1,
                    'is_gift' => $v['is_gift'] ?? 2,
                    'out_port' => $v['out_port'] ?? 1,
                ];
            }
        }
        if (!$outArr) {
            return ['state' => 100, 'msg' => '无可重发的货道数据'];
        }

        $main = [];
        foreach ($outArr as $pos => $items) {
            foreach ($items as $item) {
                $main[$pos][] = [$item['channel_code'], intval($item['quantity'])];
            }
        }

        $content = [
            'msgType' => 'outGoods',
            'trade_no' => $order['trade_no'] ?? '',
            'main' => $main,
            'outGoods' => $outArr,
            'order_points' => $order['total_points'] ?? 0,
            'can_out_goods' => true,
        ];

        $key = $machine['signKey'] ?: env('api.md5Key');
        if (!$key) {
            return ['state' => 100, 'msg' => '缺少设备签名密钥'];
        }

        $app = AppFactory::machine([
            'machine_id' => $machine['machine_id'],
            'key' => $key,
            'mac' => $machine['mac_address'] ?? '',
        ]);
        return $app->sendMq->sendMq('outGoods', $content);
    }

    /**
     * 每3分钟执行一次：自动退款（出货超时/异常）
     */
    public function autoRefund()
    {
        $now = time();
        $limit = intval(input('limit', 100));
        if ($limit <= 0) {
            $limit = 100;
        }
        $remark = input('remark', '系统自动退款：出货异常超时');
        $where[] = ['mg.auto_refund', '=', 1];
        $where[] = ['so.pay_status', '=', 3];
        $where[] = ['so.out_status', 'in', [2, 3 , 5]];
        $where[] = ['so.http_out_status', '<>', 3];
        $where[] = ['so.pay_time', '<', $now - 180];
        $where[] = ['so.create_time', '>=', $now - 900];
        $where[] = ['sod.success_quantity', '=', 0];
        $where[] = ['sod.refund_quantity', '=', 0];
        $rows = Db::name('sale_orders_details')->alias('sod')
            ->join('sale_orders so', 'so.order_id = sod.order_id')
            ->join('machine_goods mg', 'mg.mg_id = sod.mg_id')
            ->where($where)
            ->field('so.order_id,so.trade_no,so.pay_type,sod.sod_id,sod.quantity,sod.refund_quantity')
            ->order('so.pay_time asc,so.order_id asc,sod.sod_id asc')
            ->limit($limit)
            ->select()
            ->toArray();
        actionLog(Db::getLastSql(), 'autoRefund.rows.sql','autoRefund');

        if (!$rows) {
            actionLog('没有符合自动退款条件的订单', 'autoRefund','autoRefund');
            return '自动退款处理完成：0条';
        }

        $todo = [];
        foreach ($rows as $row) {
            $sodId = intval($row['sod_id']);
            if (!isset($todo[$sodId])) {
                $todo[$sodId] = $row;
            }
        }

        $success = 0;
        $skip = 0;
        $fail = 0;

        foreach ($todo as $row) {
            $orderId = intval($row['order_id']);
            $sodId = intval($row['sod_id']);
            $refundQuantity = 1;

            $lockKey = 'auto_refund_lock_sod_' . $sodId;
            if (!$this->acquireAutoRefundLock($lockKey)) {
                $skip++;
                continue;
            }

            try {
                $recheck = Db::name('sale_orders_details')->alias('sod')
                    ->join('sale_orders so', 'so.order_id = sod.order_id')
                    ->join('machine_goods mg', 'mg.mg_id = sod.mg_id')
                    ->where('so.order_id', $orderId)
                    ->where('sod.sod_id', $sodId)
                    ->where($where)
                    ->find();

                if (!$recheck) {
                    $skip++;
                    continue;
                }

                $result = $this->requestRefundBySaleOrdersClient([
                    'order_id' => $orderId,
                    'remark' => $remark,
                    'refund' => [
                        'sod_id' => $sodId,
                        'quantity' => $refundQuantity,
                    ],
                ]);

                $result = obj2arr($result);
                $state = is_array($result) ? intval($result['state'] ?? 0) : 0;
                if ($state == 200) {
                    $success++;
                } else {
                    $fail++;
                    actionLog([
                        'order_id' => $orderId,
                        'sod_id' => $sodId,
                        'result' => $result,
                    ], 'autoRefund执行失败','autoRefund');
                }
            } catch (\Throwable $e) {
                $fail++;
                actionException($e, 1, 'autoRefund');
            } finally {
                $this->releaseAutoRefundLock($lockKey);
            }
        }

        return "自动退款处理完成：成功{$success}，跳过{$skip}，失败{$fail}";
    }

    /**
     * 统一调用管理端退款入口，避免在定时任务侧重复维护退款流程。
     */
    protected function requestRefundBySaleOrdersClient(array $postData)
    {
        try {
            $result = AppFactory::management([])->saleOrders->refundOrder($postData);
            return obj2arr($result);
        } catch (\Throwable $e) {
            actionException($e, 1, 'autoRefund.requestRefundBySaleOrdersClient');
            return [
                'state' => 100,
                'msg' => $e->getMessage(),
            ];
        }
    }

    protected function acquireAutoRefundLock(string $key, int $ttl = 170): bool
    {
        try {
            $cache = Cache::store('redis');
            if ($cache->has($key)) {
                return false;
            }
            $cache->set($key, 1, $ttl);
            return true;
        } catch (\Throwable $e) {
            actionLog([
                'key' => $key,
                'msg' => $e->getMessage(),
            ], 'autoRefund redis锁异常，降级为无锁执行', 'autoRefund');
            return true;
        }
    }

    protected function releaseAutoRefundLock(string $key): void
    {
        try {
            Cache::store('redis')->delete($key);
        } catch (\Throwable $e) {
            actionLog([
                'key' => $key,
                'msg' => $e->getMessage(),
            ], 'autoRefund redis解锁异常，等待TTL自动过期', 'autoRefund');
        }
    }
}
