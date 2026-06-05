<?php

namespace app\AppFactory\Management\Revenue;

use app\AppFactory\Kernel\Traits\Payment\AfterOrderPaymentTrait;
use app\AppFactory\Kernel\Traits\Payment\BeforeOrderPaymentTrait;
use app\AppFactory\Kernel\Traits\Revenue\RevenueOrderTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersTrait;
use app\AppFactory\Management\ManagementClient;
use think\facade\Db;

class RevenueOrderClient extends ManagementClient
{
    use RevenueOrderTrait;
    use SaleOrdersTrait;
    use BeforeOrderPaymentTrait;
    use AfterOrderPaymentTrait;

    public function getList($where, $pageNum = 0, $field = "*", $order = "ro_id desc")
    {
        $where = $this->filterByManager($where);
        return $this->rQ($this->getRevenueOrderList($where, $pageNum, $field, $order));
    }

    public function getFind($where, $field = "*", $order = "ro_id desc")
    {
        $where = $this->filterByManager($where);
        return $this->rQ($this->getRevenueOrderFind($where, $field, $order));
    }

    public function getDetail($where)
    {
        $where = $this->filterByManager($where);
        $order = $this->getRevenueOrderFind($where, "*", "ro_id desc");
        if (!$order) return $this->rNoData();
        $order = is_array($order) ? $order : $order->toArray();
        $order['sale_order'] = Db::name('sale_orders')->where(['order_id' => $order['order_id']])->find();
        $order['sale_details'] = Db::name('sale_orders_details')->where(['order_id' => $order['order_id']])->select()->toArray();
        $order['revenue_orders'] = Db::name('revenue_order')->where(['order_id' => $order['order_id']])->order('ro_id asc')->select()->toArray();
        $order['refund_orders'] = Db::name('sale_orders_refund')->where(['order_id' => $order['order_id']])->order('sor_id desc')->select()->toArray();
        return $this->rQ($order);
    }

    public function export($where)
    {
        $where = $this->filterByManager($where);
        $list = $this->getRevenueOrderList($where, 0, "*", "ro_id desc");
        if (!$list || $list->isEmpty()) return $this->rFail("没有数据可导出");
        $list = $list->toArray();
        foreach ($list as &$item) {
            $item['rule_mode_text'] = $this->getRuleModeText($item['rule_mode'] ?? 0);
            $item['status_text'] = $this->getStatusText($item['status'] ?? 0);
            $item['settle_amount'] = bcsub($item['income_amount'] ?? 0, $item['refund_amount'] ?? 0, 2);
            $item['create_time_text'] = !empty($item['create_time']) ? date('Y-m-d H:i:s', $item['create_time']) : '';
            $item['revenue_time_text'] = !empty($item['revenue_time']) ? date('Y-m-d H:i:s', $item['revenue_time']) : '';
        }
        $title = [
            'ro_id' => '分账单ID',
            'order_id' => '订单ID',
            'trade_no' => '订单号',
            'sp_id' => '收款策略ID',
            'machine_id' => '设备编号',
            'machine_name' => '设备名称',
            'rule_mode_text' => '分账模式',
            'source' => '分账来源',
            'payer_ao_id' => '收款组织',
            'receiver_ao_id' => '接收组织',
            'manager_name' => '账户管理人',
            'account_type' => '账户类型',
            'account' => '分账账户',
            'income_value' => '分账比例/值',
            'income_amount' => '应分账金额',
            'refund_amount' => '已退分账金额',
            'settle_amount' => '可结算金额',
            'period_key' => '阶梯周期',
            'period_amount_before' => '本单前累计',
            'period_amount_after' => '本单后累计',
            'status_text' => '状态',
            'create_time_text' => '创建时间',
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

        $this->startTrans();
        try {
            $flag = [];
            $hasRevenue = Db::name('revenue_order')->where(['order_id' => $this->order['order_id']])->count();
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
            $this->order['pay_time'] = intval($data['pay_time'] ?? 0) ?: time();
            $flag[] = $this->updateSaleOrders($this->order, [], [
                'pay_status',
                'pay_time',
                'pay_type',
                'pay_method',
                'sp_id',
                'mch_no',
                'out_trade_no',
            ]);

            if (!flag_check($flag)) {
                throw new \Exception("模拟支付成功处理失败");
            }

            $this->commitTrans();
            return $this->rQ($this->buildMockPayResult());
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

    protected function getRuleModeText($mode)
    {
        $map = [1 => '普通分账', 2 => '设备出租', 3 => '设备分账'];
        return $map[intval($mode)] ?? '未知';
    }

    protected function getStatusText($status)
    {
        $map = [0 => '待支付', 1 => '已结算', 2 => '待结算', 3 => '失败', 4 => '已取消'];
        return $map[intval($status)] ?? '未知';
    }

    protected function applyMockPayFields($data)
    {
        foreach (['pay_type', 'pay_method', 'sp_id'] as $field) {
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
            'sale_order' => Db::name('sale_orders')->where(['order_id' => $this->order['order_id']])->find(),
            'revenue_orders' => Db::name('revenue_order')->where(['order_id' => $this->order['order_id']])->order('ro_id asc')->select()->toArray(),
        ];
    }

    protected function canMockSettleRevenue($payStatus)
    {
        $revenueList = Db::name('revenue_order')->where(['order_id' => $this->order['order_id']])->select()->toArray();
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
