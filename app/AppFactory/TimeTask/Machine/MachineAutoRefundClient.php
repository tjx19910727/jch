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
        actionLog(Db::getLastSql(), 'autoRefund.rows.sql');

        if (!$rows) {
            actionLog('没有符合自动退款条件的订单', 'autoRefund');
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
                    ], 'autoRefund执行失败');
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

    protected function acquireAutoRefundLock(string $key, int $ttl = 180): bool
    {
        if (Cache::has($key)) {
            return false;
        }
        Cache::set($key, 1, $ttl);
        return true;
    }

    protected function releaseAutoRefundLock(string $key): void
    {
        Cache::delete($key);
    }
}
