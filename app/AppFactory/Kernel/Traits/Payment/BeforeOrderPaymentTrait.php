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
trait BeforeOrderPaymentTrait
{
    /**
     * @var array 收益人数据
     */
    protected $beneficiary = [];

    protected $income;
    protected $countOrder = [];

    // 检查门店收费策略
    public function checkCharge()
    {
        $this->countOrder = $this->order;
        $sc = $this->getStrategyChargeByStoreId($this->countOrder['store_id']);
        if (!is_array($sc)) return $sc;
        // 固定金额
        if ($sc['charge_type'] == 1) {
            // 查询门店收费记录
            // 先收后用
            if ($sc['charge_mode'] == 1) {
                $where['charge_type'] = $sc['charge_type'];
                $where['charge_mode'] = $sc['charge_mode'];
                $where['store_id'] = $this->countOrder['store_id'];
                $where['status'] = 2;
                $sc = $this->checkStoreCharge($where);
                if (!is_array($sc)) return $sc;
            }
        }
        // 固定比例
        if ($sc['charge_type'] == 2) {
            // 生成订单收费记录，支付完成后再扣除
            $storeCharge = [
                "sc_id" => $sc['sc_id'],
                "store_id" => $this->countOrder['store_id'],
                "store_name" => $this->countOrder['store_name'],
                "order_id" => $this->countOrder['order_id'],
                "charge_type" => 2,
                "numerical_value" => $sc['numerical_value'],
                "cycle" => $sc['cycle'],
                "trade_no" => $this->countOrder['trade_no'],
                "expire_time" => 0,
                "status" => 1,
            ];

            $flag[] = 1;
            $this->startTrans();
            $chargeRadio = bcmul($storeCharge['numerical_value'], 0.01, 3);
            // 存在固定比例收费数据，先生成收费数据，剩下再给分润创建数据
            if (isset($this->countOrder['details']) && $this->countOrder['details']) {
                foreach ($this->countOrder['details'] as $dk => $dv) {
                    $fee_amount = bcmul($dv['total_sod_price'], $chargeRadio, 3);
                    if ($fee_amount) {
                        // 对每种商品生成一条收费数据
                        $insertSc = $storeCharge;
                        $insertSc['sod_id'] = $dv['sod_id'];
                        $insertSc['fee_amount'] = $fee_amount;
                        $this->countOrder['details'][$dk]['total_sod_price'] = bcsub($dv['total_sod_price'], $insertSc['fee_amount'], 3);
                        $flag[] = $this->addStoreCharge($insertSc);
                    }
                }
            } else {
                $fee_amount = bcmul($this->countOrder['total_price'], $chargeRadio, 3);
                if ($fee_amount) {
                    $insertSc = $storeCharge;
                    $insertSc['fee_amount'] = $fee_amount;
                    $this->countOrder['total_price'] = bcsub($this->countOrder['total_price'], $insertSc['fee_amount'], 3);
                    $flag[] = $this->addStoreCharge($insertSc);
                }
            }
            $result = flag_check($flag);
            if ($result) {
                $this->commitTrans();
                return true;
            }
            $this->rollbackTrans();
            return $this->rFail("处理失败");
        }
        return true;
    }

    /**
     * 计算收益
     */
    public function countIncome()
    {
        $this->getStrategy();
        return $this->addRevenue();
    }

    /**
     * 生成待分润记录
     * @return int
     */
    public function addRevenue()
    {
        $flag[] = 1;
        if ($this->beneficiary) {
            $radio = 100;
            $this->handleRadio($radio);
            foreach ($this->countOrder['details'] as $sodKey => $sodValue) {
                $sodRadio = $radio;
                $insert = [
                    "order_id" => $this->countOrder['order_id'],
                    "store_id" => $this->countOrder['store_id'],
                    "store_name" => $this->countOrder['store_name'],
                    "terminal_no" => $this->countOrder['terminal_no'],
                    "order_amount" => $this->countOrder['total_price'],
                    "sod_id" => $sodValue['sod_id'],
                    "sod_amount" => $sodValue['retail_price'],
                    "sod_quantity" => $sodValue['quantity'],
                    "sod_total_price" => $sodValue['total_sod_price'],
                ];
                foreach ($this->beneficiary as $key => $value) {
                    $sodRadio = bcsub($sodRadio, $value['income_value']);
                    $insert['si_id'] = $value['si_id'];
                    $insert['income_value'] = $value['income_value'];
                    $insert['beneficiary'] = $value['manager_id'];
                    $insert['revenue_type'] = $value['transfer_method'];
                    $income_amount = bcmul($insert['sod_total_price'], bcmul($value['income_value'], 0.01, 3), 3);
                    if ($income_amount > 0.010 && $sodRadio > 0) {
                        $insert['income_amount'] = $income_amount;
                        $flag[] = $this->addSaleOrdersRevenue($insert);
                        actionLog($flag,'生成分润记录flag');
                        actionLog($this->getLS(),'生成分润记录SQL');
                    }
                }
            }

//            foreach ($this->beneficiary as $key => $value) {
//                $insert = [
//                    "si_id" => $value['si_id'],
//                    "order_id" => $this->countOrder['order_id'],
//                    "store_id" => $this->countOrder['store_id'],
//                    "store_name" => $this->countOrder['store_name'],
//                    "terminal_no" => $this->countOrder['terminal_no'],
//                    "order_amount" => $this->countOrder['total_price'],
//                    "income_value" => $value['income_value'],
//                    "beneficiary" => $value['manager_id'],
//                    "revenue_type" => $value['transfer_method'],
//                ];
//                if ($this->countOrder['details']) {
//                    foreach ($this->countOrder['details'] as $sodKey => $sodValue) {
//                        $insert['sod_id'] = $sodValue['sod_id'];
//                        $insert['sod_amount'] = $sodValue['retail_price'];
//                        $insert['sod_quantity'] = $sodValue['quantity'];
//                        $insert['sod_total_price'] = $sodValue['total_sod_price'];
//                        if ($radio > 0) {
//                            $income_amount = bcmul($insert['sod_total_price'], bcmul($value['income_value'], 0.01, 3), 3);
//                            if ($income_amount > 0.010) {
//                                $insert['income_amount'] = $income_amount;
//                                $radio = bcsub($radio, $value['income_value']);
//                                $flag[] = $this->addSaleOrdersRevenue($insert);
//                            }
//                        }
//                    }
//                } else {
//                    $income_amount = bcmul($insert['order_amount'], bcmul($value['income_value'], 0.01, 3), 3);
//                    if ($income_amount > 0.010) {
//                        $insert['income_amount'] = $income_amount;
//                        $radio = bcsub($radio, $value['income_value']);
//                        $flag[] = $this->addSaleOrdersRevenue($insert);
//                    }
//                }
//            }
        }
        $result = flag_check($flag);
        return $result;
    }

    /**
     * 获取分润策略，整理收益人数据
     */
    public function getStrategy()
    {
        // 查询门店绑定的分润策略关系列表
        $si = $this->getStrategyIncomeByStoreId($this->order['store_id']);
        if ($si) {
            $smField = "sm_id,manager_id,sort";
            foreach ($si as $sik => $siv) {
                $getField = $smField . "," . $siv["si_id"] . " as si_id,(" . $siv['income_value'] . ") as income_value, " . $siv['transfer_method'] . " as transfer_method";
                $sm = $this->getStrategyManagerList(['s_id' => $siv['si_id'], 's_type' => 1], 0, $getField);
                if ($sm) $sm = $sm->toArray();
                foreach ($sm as $smk => $smv) {
                    $manager = $this->getAuthManagerFind(['manager_id' => $smv['manager_id']],'manager_id');
                    if (!$manager) unset($sm[$smk]);
                }
                $this->beneficiary = array_merge($this->beneficiary, $sm);
            }
            // 以排序值排序
            array_multisort(array_column($this->beneficiary, 'sort'), SORT_ASC, $this->beneficiary);
            $this->beneficiary = super_unique($this->beneficiary, "manager_id");
            actionLog($this->beneficiary, "处理前收益人数据");
        }
    }

    /**
     * 处理分润比例
     * @param $radio
     * @return string
     */
    public function handleRadio(&$radio)
    {
        if ($this->countOrder['payment_type'] == 4) {
            $radio = floor(bcmul(bcdiv(bcsub($this->countOrder['total_price'], 0.01, 3), $this->countOrder['total_price'], 3), 100));
        }
        return $radio;
    }

}