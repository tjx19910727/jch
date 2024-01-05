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
    /**
     * 生成退款记录
     * @return array
     */
    public function createSor()
    {
        $this->totalRefundMoney = 0;
        $this->refundTradeNo = $this->getRefundNo();
        $flag = [];
        foreach ($this->postData['refund'] as $key => $value) {
            try {
                validate(VSaleOrdersRefund::class)->scene('refund')->check($value);
            } catch (\Exception $e) {
                return $this->rValidate($e->getMessage());
            }
            $this->sod = $this->getSaleOrdersDetailsFind(['sod_id' => $value['sod_id']]);
            $this->sod = obj2arr($this->sod);
            if (!$this->sod) {
                return $this->rValidate("查无购买的商品信息");
            }
            $this->sod['refund_quantity'] = $value['quantity'];
            $refundAmount = $this->calculateRefundAmount();
            $this->totalRefundMoney = bcadd($this->totalRefundMoney,$refundAmount,2);
            $insertSor = [
                "order_id" => $this->sod['order_id'],
                "sod_id" => $this->sod['sod_id'],
                "ss_id" => $this->sod['ss_id'],
                "goods_id" => $this->sod['goods_id'],
                "wg_id" => $this->sod['wg_id'],
                "refund_trade_no" => $this->refundTradeNo,
                "refund_amount" => $refundAmount,
                "refund_quantity" => $value['quantity'],
            ];
            actionLog($insertSor,'生成退款记录数据');
            $create = $this->addSaleOrdersRefund($insertSor);
            actionLog($create,'生成退款记录结果');
        }
        if ($this->totalRefundMoney <= 0) return $this->rFail("退款金额小于0，退款失败");
        actionLog($flag,'退款记录生成结果');
        return $flag;
    }

    /**
     * 计算退款金额
     * @return string
     */
    public function calculateRefundAmount()
    {
        $refundAmount = bcmul($this->sod['refund_quantity'],$this->sod['retail_price'],2);
        return $refundAmount;
    }

}