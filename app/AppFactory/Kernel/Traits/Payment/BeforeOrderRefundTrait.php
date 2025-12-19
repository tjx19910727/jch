<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/10/16
 * Time: 18:04
 */

namespace app\AppFactory\Kernel\Traits\Payment;


use app\AppFactory\Kernel\Support\Validate\SaleOrders\VSaleOrdersRefund;

trait BeforeOrderRefundTrait
{

    protected $insertSor;
    protected $sodRefundAmount;
    public $billList;

    /**
     * 生成退款记录
     * @return array|string
     */
    public function createSor()
    {
        $this->totalRefundMoney = 0;
        $this->refundTradeNo = $this->getRefundNo();
        $flag = [];
        try {
            validate(VSaleOrdersRefund::class)->scene('refund')->check($this->postData['refund']);
        } catch (\Exception $e) {
            return $this->rValidate($e->getMessage());
        }
        $this->sod = $this->getSaleOrdersDetailsFind(['sod_id' => $this->postData['refund']['sod_id']]);
        $this->sod = obj2arr($this->sod);
        if (!$this->sod) {
            return $this->rFail("查无购买的商品信息");
        }
        if ($this->sod['is_gift'] == 1)
            return $this->rFail("赠品不允许退款");
        if ($this->sod['quantity'] == $this->postData['refund']['quantity']) $this->sodRefundAmount = $this->sod['total_sod_price'];
        if ($this->sod['refund_quantity'] + $this->postData['refund']['quantity'] > $this->sod['quantity']) {
            return $this->rFail("退款数量大于当前订单详情数量");
        }
        // 本次退款总金额
        if (!$this->sodRefundAmount)
            $this->sodRefundAmount = bcmul(bcdiv($this->sod['total_sod_price'],$this->sod['quantity'],2) , $this->postData['refund']['quantity'],3);
        actionLog($this->sodRefundAmount,'本次退款总金额');
        // 当前退款金额大于可退金额时，重置为剩余可退金额bcdiv($this->sod['total_sod_price'],$this->sod['quantity'],2)
        $refundAmount = bcsub($this->order['total_price'], $this->order['refund_amount'], 3);
        if ($this->sodRefundAmount > $refundAmount) {
            $this->sodRefundAmount = $refundAmount;
            actionLog($this->sodRefundAmount, "本次退款总金额【重置后】");
        }

        $this->sod['refund_quantity'] = bcadd($this->sod['refund_quantity'],$this->postData['refund']['quantity']);
        $this->sod['refund_amount'] = bcadd($this->sod['refund_amount'],$this->sodRefundAmount,3);
        $amountRefunded = $this->getSaleOrdersRefundSum(['order_id' => $this->sod['order_id'],'status' => 2],'refund_amount');
        actionLog($amountRefunded,'已退款总金额');
        // 最后一次退款，并且退款金额小于可退金额。
        $lastRefundAmount = bcsub($this->order['total_price'],$amountRefunded,2);
        if ($this->order['total_quantity'] == $this->order['refund_quantity'] + $this->postData['refund']['quantity'] && $this->sodRefundAmount < $lastRefundAmount) {
            $this->sodRefundAmount = $lastRefundAmount;
            actionLog($this->sodRefundAmount,'本次退款总金额【重置后】-最后退款金额小于可退金额');
        }
        $saleAmount = $this->getSaleOrdersValue(['order_id' => $this->sod['order_id']],'total_price');
        actionLog($saleAmount,'订单销售金额');
        if (bcadd($amountRefunded , $this->sodRefundAmount,3) > $saleAmount) {
            return $this->rFail("总退款金额超出订单总金额");
        }
        $this->handleSorData();
        $flag[] = $this->revenueRefund();
        $systemRefund = $this->systemRefund();
        if (is_object($systemRefund) || is_string($systemRefund)) return $systemRefund;
        $flag[] = $systemRefund;
        if ($this->sodRefundAmount <= 0) return $this->rFail("退款金额小于0，退款失败");
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
        // 受分润记录
        $revenue = $this->getSaleOrdersRevenueList(['sod_id' => $this->sod['sod_id'],['status','between',[2,3]]]);
        if ($revenue) {
            foreach ($revenue as $key  => $value) {
                $insertSor = $this->insertSor;
                $refund_amount = bcmul(bcdiv($this->insertSor['refund_quantity'],$value['sod_quantity'],2),$value['income_amount'],3);
                // 四舍五入取整并保留两位小数
                $refund_amount = round($refund_amount, 2);
                $this->totalRefundMoney = bcadd($this->totalRefundMoney,$refund_amount,2);
                $insertSor['refund_amount'] = $refund_amount;
                $insertSor['manager_id'] = $value['manager_id'];
                $insertSor['nickname'] = $this->getAuthManagerValue(['manager_id' => $value['manager_id']],'nickname');
                $flag[] = $this->addSaleOrdersRefund($insertSor);
                // 京东收银角色退款退分润
                if ($this->order['pay_type'] == 4) {
                    $billList['customerNum'] = $revenue['bill_account'];
                    $billList['amount'] = $refund_amount; // 小数点两位
                    $this->billList[] = $billList;
                }
            }
            actionLog($flag,'生成分润退款flag');
        }
        return $this->checkFlag($flag);
    }

    /**
     * 系统退款
     * @return int
     */
    protected function systemRefund()
    {
        if ($this->totalRefundMoney < $this->sodRefundAmount) {
            $insertSor = $this->insertSor;
            $insertSor['refund_amount'] = round(bcsub($this->sodRefundAmount,$this->totalRefundMoney,2),2);
            $insertSor['manager_id'] = 0;
            $insertSor['nickname'] = "收款方";
            if($this->order['pay_type'] == 9 || $this->order['order_type'] == 7){
                $refund_amount = round(bcsub($this->sodRefundAmount,$this->totalRefundMoney,2),2);
                $refund_points = $refund_amount * $this->order['intergral_rate'];
                $insertSor['refund_amount'] = 0;
                $insertSor['refund_points'] = $refund_points;
            }
            $this->totalRefundMoney = $this->sodRefundAmount;
            // 京东收银系统退款退分润
            if ($this->order['pay_type'] == 4 && $this->billList) {
                if (!isset($this->strategyPayee['bill_account'])) {
                    return $this->rFail("收款方未配置分账账号");
                }
                if (!$this->strategyPayee) return $this->rFail("收款方分账账号不能为空");
                $this->billList[] = [
                    "customerNum" => $this->strategyPayee['bill_account'],
                    "amount" => $insertSor['refund_amount'],
                ];
            }
            return $this->addSaleOrdersRefund($insertSor);
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
            "ao_id" => $this->order['ao_id'],
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
        ];
    }

}