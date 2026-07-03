<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/10/16
 * Time: 18:04
 */

namespace app\AppFactory\Kernel\Traits\Payment;


use app\AppFactory\Kernel\Support\Validate\SaleOrders\VSaleOrdersRefund;
use app\AppFactory\Kernel\Model\Revenue\RevenueOrderRefundModel;
use think\facade\Db;

trait BeforeOrderRefundTrait
{

    protected $insertSor;
    protected $sodRefundAmount;
    protected $businessSorId;
    protected $revenueRefundMoney;
    public $billList;

    /**
     * 按子单与数量计算本次应退金额（与 createSor 逻辑一致，不修改内存中的 sod）
     * @param array $refund ['sod_id' => int, 'quantity' => int]
     * @return array|float amount 为应退金额；失败返回 rFail 结构
     */
    protected function calcSodRefundAmount(array $refund)
    {
        try {
            validate(VSaleOrdersRefund::class)->scene('refund')->check($refund);
        } catch (\Exception $e) {
            return $this->rValidate($e->getMessage());
        }
        $sod = $this->getSaleOrdersDetailsFind(['sod_id' => $refund['sod_id']]);
        $sod = obj2arr($sod);
        if (!$sod) {
            return $this->rFail("查无购买的商品信息");
        }
        if ($sod['is_gift'] == 1) {
            return $this->rFail("赠品不允许退款");
        }
        if (intval($sod['order_id']) !== intval($this->order['order_id'])) {
            return $this->rFail("子订单不属于当前订单");
        }
        $refundQuantity = intval($refund['quantity']);
        if ($refundQuantity <= 0) {
            return $this->rFail("退款数量必须大于0");
        }
        if ($sod['refund_quantity'] + $refundQuantity > $sod['quantity']) {
            return $this->rFail("退款数量大于当前订单详情数量");
        }
        $sodRefundAmount = null;
        if ($sod['quantity'] == $refundQuantity) {
            $sodRefundAmount = $sod['total_sod_price'];
        }
        if (!$sodRefundAmount) {
            $sodRefundAmount = bcmul(bcdiv($sod['total_sod_price'], $sod['quantity'], 2), $refundQuantity, 3);
        }
        $refundAmount = bcsub($this->order['total_price'], $this->order['refund_amount'], 3);
        if (bccomp((string)$sodRefundAmount, (string)$refundAmount, 3) > 0) {
            $sodRefundAmount = $refundAmount;
        }
        $amountRefunded = $this->getSaleOrdersRefundSum(['order_id' => $sod['order_id'], 'status' => 2], 'refund_amount');
        $lastRefundAmount = bcsub($this->order['total_price'], $amountRefunded, 2);
        if ($this->order['total_quantity'] == $this->order['refund_quantity'] + $refundQuantity
            && bccomp((string)$sodRefundAmount, (string)$lastRefundAmount, 3) < 0) {
            $sodRefundAmount = $lastRefundAmount;
        }
        $saleAmount = $this->getSaleOrdersValue(['order_id' => $sod['order_id']], 'total_price');
        if (bcadd($amountRefunded, $sodRefundAmount, 3) > $saleAmount) {
            return $this->rFail("总退款金额超出订单总金额");
        }
        return ['amount' => round((float)$sodRefundAmount, 2), 'sod' => $sod];
    }

    /**
     * 生成退款记录
     * @return array|string
     */
    public function createSor()
    {
        $this->totalRefundMoney = 0;
        $this->revenueRefundMoney = 0;
        $this->billList = [];
        $this->refundTradeNo = $this->getRefundNo();
        $flag = [];
        $calc = $this->calcSodRefundAmount($this->postData['refund']);
        if (!is_array($calc) || !isset($calc['amount'])) {
            return $calc;
        }
        $this->sod = $calc['sod'];
        $this->sodRefundAmount = $calc['amount'];
        actionLog($this->sodRefundAmount, '本次退款总金额');
        $this->sod['refund_quantity'] = bcadd($this->sod['refund_quantity'], $this->postData['refund']['quantity']);
        $this->sod['refund_amount'] = bcadd($this->sod['refund_amount'], $this->sodRefundAmount, 3);
        $this->handleSorData();
        $systemRefund = $this->systemRefund();
        if (is_object($systemRefund) || is_string($systemRefund)) return $systemRefund;
        $flag[] = $systemRefund;
        $revenueRefund = $this->revenueRefund();
        if (is_object($revenueRefund) || is_string($revenueRefund)) return $revenueRefund;
        $flag[] = $revenueRefund;
        $appendBill = $this->appendSystemRefundBillList();
        if (is_object($appendBill) || is_string($appendBill)) return $appendBill;
        $flag[] = $appendBill;
        if ($this->sodRefundAmount <= 0) {
            if($this->order['pay_type'] != 9) return $this->rFail("退款金额小于0，退款失败");
        }
        actionLog($flag,'退款记录生成结果');
        return $flag;
    }

    /**
     * 分润退款
     * @return mixed
     */
    protected function revenueRefund()
    {
        $flag[] = 1;
        if (!$this->businessSorId) {
            return $this->rFail("业务退款单生成失败");
        }
        $sodId = intval($this->sod['sod_id']);
        $revenue = Db::name('revenue_order')
            ->where(['order_id' => $this->order['order_id']])
            ->whereRaw('(`sod_id` = ' . $sodId . ' OR `sod_id` = 0 OR `sod_id` IS NULL)')
            ->whereBetween('status', [1, 3])
            ->select()
            ->toArray();
        if ($revenue) {
            foreach ($revenue as $key  => $value) {
                $insertSor = $this->insertSor;
                $refund_amount = $this->calcBeforeRevenueRefundAmount($value);
                // 四舍五入取整并保留两位小数
                $refund_amount = round($refund_amount, 2);
                if ($refund_amount <= 0) continue;
                $this->revenueRefundMoney = bcadd($this->revenueRefundMoney,$refund_amount,2);
                $flag[] = $this->addRevenueOrderRefund($value, $refund_amount);
                if ($this->order['pay_type'] == 4 && ($value['account_type'] ?? '') === 'jd_account' && !empty($value['account'])) {
                    $billList['customerNum'] = $value['account'];
                    $billList['amount'] = $refund_amount; // 小数点两位
                    $this->billList[] = $billList;
                }
            }
            actionLog($flag,'生成新分账退款flag');
        }
        if (bccomp((string)$this->revenueRefundMoney, (string)$this->sodRefundAmount, 2) > 0) {
            return $this->rFail("分账退款金额超出业务退款金额");
        }
        return $this->checkFlag($flag);
    }

    protected function calcBeforeRevenueRefundAmount(array $revenue)
    {
        $incomeAmount = is_numeric($revenue['income_amount'] ?? null) ? $revenue['income_amount'] : 0;
        if ($incomeAmount <= 0) return 0;
        if (intval($revenue['sod_id'] ?? 0) === intval($this->sod['sod_id'])
            && !empty($revenue['sod_quantity'])
            && $revenue['sod_quantity'] > 0) {
            return bcmul(bcdiv($this->insertSor['refund_quantity'], $revenue['sod_quantity'], 6), $incomeAmount, 3);
        }
        $orderAmount = is_numeric($this->order['total_price'] ?? null) ? $this->order['total_price'] : 0;
        if ($orderAmount <= 0) return 0;
        return bcmul(bcdiv($this->sodRefundAmount, $orderAmount, 6), $incomeAmount, 3);
    }

    /**
     * 系统退款
     * @return int
     */
    protected function systemRefund()
    {
        $insertSor = $this->insertSor;
        $insertSor['refund_amount'] = round($this->sodRefundAmount, 2);
        $insertSor['manager_id'] = 0;
        $insertSor['nickname'] = "收款方";
        if($this->order['pay_type'] == 9 || $this->order['order_type'] == 7){//这里退的是消费的商场积分
            $insertSor['refund_cost_points'] = bcmul(bcdiv($this->sod['refund_quantity'], $this->sod['quantity'], 0),$this->sod['total_sod_cost_points'],2);
        }else{//这里退的是赠送的积分
            $insertSor['refund_points'] = bcmul(bcdiv($this->sod['refund_quantity'], $this->sod['quantity'], 0),$this->sod['total_sod_points'],2);
        }
        $this->totalRefundMoney = $this->sodRefundAmount;
        actionLog($insertSor,'生成业务退款订单');
        $this->businessSorId = $this->addSaleOrdersRefund($insertSor);
        return $this->businessSorId;
    }

    protected function addRevenueOrderRefund(array $revenue, $refundAmount)
    {
        $insert = [
            'ro_id' => intval($revenue['ro_id'] ?? 0),
            'sor_id' => intval($this->businessSorId),
            'order_id' => intval($this->order['order_id'] ?? 0),
            'sod_id' => intval($this->sod['sod_id'] ?? 0),
            'refund_trade_no' => $this->refundTradeNo,
            'trade_no' => $this->order['trade_no'] ?? '',
            'manager_id' => intval($revenue['manager_id'] ?? 0),
            'manager_name' => $revenue['manager_name'] ?? '',
            'account_type' => $revenue['account_type'] ?? '',
            'account' => $revenue['account'] ?? '',
            'refund_amount' => $refundAmount,
            'refund_quantity' => intval($this->postData['refund']['quantity'] ?? 0),
            'status' => 1,
        ];
        $record = RevenueOrderRefundModel::create($insert);
        actionLog($this->getLS(), '生成分账退款明细SQL');
        return $record->ror_id;
    }

    protected function appendSystemRefundBillList()
    {
        if ($this->order['pay_type'] != 4 || !$this->billList) {
            return 1;
        }
        if (!isset($this->strategyPayee['bill_account'])) {
            return $this->rFail("收款方未配置分账账号");
        }
        if (!$this->strategyPayee) return $this->rFail("收款方分账账号不能为空");
        $systemAmount = round(bcsub($this->sodRefundAmount, $this->revenueRefundMoney, 2), 2);
        if ($systemAmount > 0) {
            $this->billList[] = [
                "customerNum" => $this->strategyPayee['bill_account'],
                "amount" => $systemAmount,
            ];
        }
        return 1;
    }

    /**
     * 退款主体数据
     */
    protected function handleSorData()
    {
        $this->insertSor = [
            "order_id" => $this->sod['order_id'],
            "trade_no" => $this->order['trade_no'],
            "sod_id" => $this->sod['sod_id'],
            "m_id" => $this->order['m_id'],
            "machine_id" => $this->order['machine_id'],
            "machine_name" => $this->order['machine_name'],
            "ao_id" => $this->sod['sod_ao_id'] ?? ($this->order['ao_id'] ?? 0),
            "mc_id" => $this->sod['mc_id'],
            "channel_position" => $this->sod['channel_position'],
            "channel_code" => $this->sod['channel_code'],
            "g_id" => $this->sod['g_id'],
            "g_name" => $this->sod['g_name'],
            "pic" => $this->sod['pic'],
            "gc_id" => $this->sod['gc_id'],
            "gc_name" => $this->sod['gc_name'],
            "mg_id" => $this->sod['mg_id'],
            "refund_trade_no" => $this->refundTradeNo,
            "refund_quantity" => $this->postData['refund']['quantity'],
            "user_id" => $this->order['user_id'],
            "remark" => $this->postData['remark'] ?? "",
            "refund_cost_points" => 0,
        ];
    }

}
