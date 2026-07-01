<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/3
 * Time: 8:17
 */

namespace app\AppFactory\Kernel\Traits\Payment;

/**
 * Trait BeforeOrderPaymentTrait
 * 订单支付前处理
 * @package app\AppFactory\Kernel\Traits\Pay
 */
use app\AppFactory\Kernel\Service\Revenue\RevenueCalculator;
trait BeforeOrderPaymentTrait
{
    protected $revenueError = "";

    /**
     * 计算收益
     */
    public function countIncome()
    {
        try {
            $this->order['details'] = $this->order['detail'] ?? $this->getSaleOrdersDetailsList(['order_id' => $this->order['order_id']])->toArray();
            $calculator = new RevenueCalculator();
            $result = $calculator->calculate($this->order);
            $this->revenueError = "";
            return $result;
        } catch (\Exception $e) {
            $this->revenueError = $e->getMessage();
            actionException($e, 1);
            return false;
        }
    }
}
