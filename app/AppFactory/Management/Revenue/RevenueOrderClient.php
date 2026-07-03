<?php

namespace app\AppFactory\Management\Revenue;

use app\AppFactory\Kernel\Model\Revenue\RevenueOrderRefundModel;
use app\AppFactory\Kernel\Traits\Payment\AfterOrderPaymentTrait;
use app\AppFactory\Kernel\Traits\Payment\BeforeOrderPaymentTrait;
use app\AppFactory\Kernel\Traits\Revenue\RevenueOrderTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersRefundTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersTrait;
use app\AppFactory\Management\ManagementClient;

class RevenueOrderClient extends ManagementClient
{
    use RevenueOrderTrait;
    use SaleOrdersTrait;
    use SaleOrdersRefundTrait;
    use BeforeOrderPaymentTrait;
    use AfterOrderPaymentTrait;
    use RevenueOrganizationNameTrait;
    use RevenuePayTypeDescTrait;

    public function getList($where = [], $pageNum = 0, $field = "*", $order = "ro_id desc", $rQ = '')
    {
        $where = $this->filterByManager($where);
        return $this->rQ($this->appendRevenuePayTypeDesc(
            $this->appendRevenueOrganizationNames(
                $this->appendRevenueRefundSummary(
                    $this->getRevenueOrderList($where, $pageNum, $field, $order)
                )
            )
        ));
    }

    public function getFind($where = [], $field = "*", $order = "ro_id desc",$rQ = '')
    {
        $where = $this->filterByManager($where);
        return $this->rQ($this->appendRevenuePayTypeDesc(
            $this->appendRevenueOrganizationNames(
                $this->getRevenueOrderFind($where, $field, $order)
            )
        ));
    }

    public function getDetail($where)
    {
        $where = $this->filterByManager($where);
        $order = $this->getRevenueOrderFind($where, "*", "ro_id desc");
        if (!$order) return $this->rNoData();
        $order = is_array($order) ? $order : $order->toArray();
        $order['sale_order'] = $this->getSaleOrdersFind(['order_id' => $order['order_id']]);
        $order['sale_details'] = $this->getSaleOrdersDetailsList(['order_id' => $order['order_id']], 0, '*', 'sod_id asc')->toArray();
        $order['revenue_orders'] = $this->getRevenueOrderList(['order_id' => $order['order_id']], 0, '*', 'ro_id asc')->toArray();
        $order['refund_orders'] = $this->getSaleOrdersRefundList(['order_id' => $order['order_id']], 0, '*', 'sor_id desc')->toArray();
        $order['revenue_refund_orders'] = $this->getRevenueRefundOrdersByOrderId(intval($order['order_id']));
        $order['revenue_orders'] = $this->attachRevenueRefundOrders($order['revenue_orders'], $order['revenue_refund_orders']);
        return $this->rQ($this->appendRevenuePayTypeDesc(
            $this->appendRevenueOrganizationNames($order)
        ));
    }

    public function export($where)
    {
        $where = $this->filterByManager($where);
        $list = $this->getRevenueOrderList($where, 0, "*", "ro_id desc");
        if (!$list || $list->isEmpty()) return $this->rFail("没有数据可导出");
        $list = $this->appendRevenueRefundSummary($list);
        $list = $this->appendRevenueOrganizationNames($list);
        foreach ($list as &$item) {
            $item['rule_mode_text'] = $this->getRuleModeText($item['rule_mode'] ?? 0);
            $item['status_text'] = $this->getStatusText($item['status'] ?? 0);
            $item['settlement_type_text'] = $this->getSettlementTypeText($item['settlement_type'] ?? 1, $item['settlement_days'] ?? 0);
            $item['settle_amount'] = bcsub($item['income_amount'] ?? 0, $item['refund_amount'] ?? 0, 2);
            $item['create_time_text'] = !empty($item['create_time']) ? date('Y-m-d H:i:s', $item['create_time']) : '';
            $item['planned_revenue_time_text'] = !empty($item['planned_revenue_time']) ? date('Y-m-d H:i:s', $item['planned_revenue_time']) : '';
            $item['revenue_time_text'] = !empty($item['revenue_time']) ? date('Y-m-d H:i:s', $item['revenue_time']) : '';
        }
        $title = [
            'ro_id' => '分账单ID',
            'order_id' => '订单ID',
            'sod_id' => '子订单ID',
            'g_id' => '商品ID',
            'mg_id' => '设备商品ID',
            'trade_no' => '订单号',
            'sp_id' => '收款策略ID',
            'machine_id' => '设备编号',
            'machine_name' => '设备名称',
            'rule_mode_text' => '分账模式',
            'source' => '分账来源',
            'payer_ao_id' => '收款组织',
            'payer_organization_name' => '收款组织名称',
            'receiver_ao_id' => '接收组织',
            'receiver_organization_name' => '接收组织名称',
            'manager_name' => '账户管理人',
            'account_type' => '账户类型',
            'account' => '分账账户',
            'income_value' => '分账比例/值',
            'income_amount' => '应分账金额',
            'refund_amount' => '已退分账金额',
            'refund_detail_amount' => '分账退款流水金额',
            'settle_amount' => '可结算金额',
            'period_key' => '阶梯周期',
            'period_amount_before' => '本单前累计',
            'period_amount_after' => '本单后累计',
            'settlement_type_text' => '分账时间',
            'status_text' => '状态',
            'create_time_text' => '创建时间',
            'planned_revenue_time_text' => '计划结算时间',
            'revenue_time_text' => '结算时间',
        ];
        return $this->sendToExport("分账订单-报表", "分账订单-" . date("YmdHis"), $title, $list);
    }

    public function mockPaySuccess($data)
    {
        if (!env('CglPay.is_test')) {
            return $this->rFail("仅测试环境允许模拟支付成功");
        }

        $tradeNo = trim($data['trade_no'] ?? '');
        $orderId = intval($data['order_id'] ?? 0);
        if (!$tradeNo && !$orderId) {
            return $this->rFail("订单号或订单ID不能为空");
        }

        $where = $tradeNo ? ['trade_no' => $tradeNo] : ['order_id' => $orderId];
        $order = $this->getSaleOrdersFind($where);
        if (!$order) return $this->rFail("订单不存在");
        $this->order = is_array($order) ? $order : $order->toArray();

        $payStatus = intval($this->order['pay_status'] ?? 0);
        if ($payStatus > 2 && $payStatus !== 3) {
            return $this->rFail("当前订单状态不允许模拟支付成功");
        }

        $this->applyMockPayFields($data);
        $this->order['pay_time'] = intval($data['pay_time'] ?? 0) ?: time();

        $this->startTrans();
        try {
            $flag = [];
            $hasRevenue = $this->getRevenueOrderList(['order_id' => $this->order['order_id']], 0, 'ro_id', 'ro_id asc')->count();
            if (!$hasRevenue) {
                $flag[] = $this->countIncome();
                if (end($flag) === false) {
                    throw new \Exception($this->revenueError ?: "生成新分账单失败");
                }
            }
            if (!$this->canMockSettleRevenue($payStatus)) {
                throw new \Exception("当前订单存在不可结算的新分账单，请重新创建测试订单");
            }

            $flag[] = $this->settlementRevenue();
            $this->order['pay_status'] = 3;
            $flag[] = $this->updateSaleOrders($this->order, [], [
                'pay_status',
                'pay_time',
                'pay_type',
                'pay_channel',
                'pay_method',
                'sp_id',
                'mch_no',
                'out_trade_no',
            ]);

            if (!flag_check($flag)) {
                throw new \Exception("模拟支付成功处理失败");
            }

            $this->commitTrans();
            return $this->rQ($this->appendRevenuePayTypeDesc(
                $this->appendRevenueOrganizationNames($this->buildMockPayResult())
            ));
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rFail($e->getMessage());
        }
    }

    protected function filterByManager($where)
    {
        if (!isset($this->manager['audit_status']) || intval($this->manager['audit_status']) !== 1) {
            $where[] = ['manager_id', '=', $this->manager['manager_id']];
        }
        return $where;
    }

    protected function appendRevenueRefundSummary($list)
    {
        if (!$list) return $list;
        $isPaginator = is_object($list) && method_exists($list, 'getCollection');
        $items = $isPaginator ? $list->getCollection() : $list;
        if (!$items) return $list;
        $roIds = [];
        foreach ($items as $item) {
            $itemArr = is_array($item) ? $item : $item->toArray();
            if (!empty($itemArr['ro_id'])) $roIds[] = intval($itemArr['ro_id']);
        }
        $summary = $this->getRevenueRefundSummaryByRoIds($roIds);
        if (is_array($items)) {
            foreach ($items as $key => $item) {
                $items[$key] = $this->appendRevenueRefundSummaryItem($item, $summary);
            }
        } else {
            $items = $items->each(function ($item) use ($summary) {
                return $this->appendRevenueRefundSummaryItem($item, $summary);
            });
        }
        if ($isPaginator) {
            $list->setCollection($items);
            return $list;
        }
        return $items;
    }

    protected function appendRevenueRefundSummaryItem($item, array $summary)
    {
        $roId = intval($item['ro_id'] ?? 0);
        $item['refund_detail_amount'] = $summary[$roId]['refund_detail_amount'] ?? '0.00';
        $item['refund_pending_amount'] = $summary[$roId]['refund_pending_amount'] ?? '0.00';
        $item['refund_failed_amount'] = $summary[$roId]['refund_failed_amount'] ?? '0.00';
        $item['refund_record_count'] = $summary[$roId]['refund_record_count'] ?? 0;
        return $item;
    }

    protected function getRevenueRefundSummaryByRoIds(array $roIds)
    {
        $roIds = array_values(array_unique(array_filter(array_map('intval', $roIds))));
        if (!$roIds) return [];
        $rows = RevenueOrderRefundModel::where('ro_id', 'in', $roIds)
            ->field('ro_id,status,refund_amount')
            ->select()
            ->toArray();
        $summary = [];
        foreach ($rows as $row) {
            $roId = intval($row['ro_id'] ?? 0);
            if (!isset($summary[$roId])) {
                $summary[$roId] = [
                    'refund_detail_amount' => '0.00',
                    'refund_pending_amount' => '0.00',
                    'refund_failed_amount' => '0.00',
                    'refund_record_count' => 0,
                ];
            }
            $amount = $row['refund_amount'] ?? 0;
            $summary[$roId]['refund_record_count']++;
            if (intval($row['status'] ?? 0) === 2) {
                $summary[$roId]['refund_detail_amount'] = bcadd($summary[$roId]['refund_detail_amount'], $amount, 2);
            } elseif (intval($row['status'] ?? 0) === 1) {
                $summary[$roId]['refund_pending_amount'] = bcadd($summary[$roId]['refund_pending_amount'], $amount, 2);
            } elseif (intval($row['status'] ?? 0) === 3) {
                $summary[$roId]['refund_failed_amount'] = bcadd($summary[$roId]['refund_failed_amount'], $amount, 2);
            }
        }
        return $summary;
    }

    protected function getRevenueRefundOrdersByOrderId($orderId)
    {
        if ($orderId <= 0) return [];
        return RevenueOrderRefundModel::where(['order_id' => intval($orderId)])
            ->order('ror_id desc')
            ->select()
            ->toArray();
    }

    protected function attachRevenueRefundOrders(array $revenueOrders, array $refundOrders)
    {
        $map = [];
        foreach ($refundOrders as $refundOrder) {
            $roId = intval($refundOrder['ro_id'] ?? 0);
            if (!isset($map[$roId])) $map[$roId] = [];
            $map[$roId][] = $refundOrder;
        }
        foreach ($revenueOrders as $key => $revenueOrder) {
            $roId = intval($revenueOrder['ro_id'] ?? 0);
            $revenueOrders[$key]['refund_orders'] = $map[$roId] ?? [];
            $revenueOrders[$key]['refund_detail_amount'] = '0.00';
            foreach ($revenueOrders[$key]['refund_orders'] as $refundOrder) {
                if (intval($refundOrder['status'] ?? 0) === 2) {
                    $revenueOrders[$key]['refund_detail_amount'] = bcadd(
                        $revenueOrders[$key]['refund_detail_amount'],
                        $refundOrder['refund_amount'] ?? 0,
                        2
                    );
                }
            }
        }
        return $revenueOrders;
    }

    protected function getRuleModeText($mode)
    {
        $map = [1 => '基础/设备分账', 2 => '设备出租', 3 => '设备分账(历史兼容)', 4 => '设备商品分账', 5 => '优惠券分账'];
        return $map[intval($mode)] ?? '未知';
    }

    protected function getStatusText($status)
    {
        $map = [0 => '待支付', 1 => '已结算', 2 => '待结算', 3 => '失败', 4 => '已取消'];
        return $map[intval($status)] ?? '未知';
    }

    protected function getSettlementTypeText($type, $days)
    {
        return intval($type) === 2 ? 'T+' . max(1, intval($days)) : '即时分账';
    }

    protected function applyMockPayFields($data)
    {
        foreach (['pay_type', 'pay_channel', 'pay_method', 'sp_id'] as $field) {
            if (isset($data[$field]) && $data[$field] !== '') {
                $this->order[$field] = intval($data[$field]);
            }
        }
        foreach (['mch_no', 'out_trade_no'] as $field) {
            if (isset($data[$field])) {
                $this->order[$field] = trim($data[$field]);
            }
        }
    }

    protected function buildMockPayResult()
    {
        return [
            'sale_order' => $this->getSaleOrdersFind(['order_id' => $this->order['order_id']]),
            'revenue_orders' => $this->getRevenueOrderList(['order_id' => $this->order['order_id']], 0, '*', 'ro_id asc')->toArray(),
        ];
    }

    protected function canMockSettleRevenue($payStatus)
    {
        $revenueList = $this->getRevenueOrderList(['order_id' => $this->order['order_id']], 0, '*', 'ro_id asc')->toArray();
        if (!$revenueList) return true;
        if (intval($payStatus) === 3) return true;
        foreach ($revenueList as $item) {
            if (in_array(intval($item['status'] ?? 0), [0, 2], true)) {
                return true;
            }
        }
        return false;
    }
}
