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
            $this->countOrder = $this->order;
            $radio = 100;
            $this->handleRadio($radio);
            foreach ($this->countOrder['details'] as $sodKey => $sodValue) {
                $sodRadio = $radio;
                $insert = [
                    "order_id" => $this->countOrder['order_id'],
                    "sp_id" => $this->countOrder['sp_id'],
                    "m_id" => $this->countOrder['m_id'],
                    "machine_name" => $this->countOrder['machine_name'],
                    "machine_id" => $this->countOrder['machine_id'],
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
                    $insert['manager_id'] = $value['manager_id'];
                    $insert['bill_account'] = $value['bill_account'] ?? "";
                    $insert['revenue_type'] = $value['transfer_method'];
                    $income_amount = bcmul($insert['sod_total_price'], bcmul($value['income_value'], 0.01, 3), 3);
                    if ($income_amount > 0.01 && $sodRadio > 0) {
                        $insert['income_amount'] = $income_amount;
                        $flag[] = $this->addSaleOrdersRevenue($insert);
                        actionLog($flag,'生成分润记录flag');
                        actionLog($this->getLS(),'生成分润记录SQL');
                    }
                }
            }
        }
        $result = flag_check($flag);
        return $result;
    }

    /**
     * 获取分润策略，整理收益人数据
     */
    public function getStrategy()
    {
        // 查询设备绑定的分润策略关系列表
        $si = $this->getStrategyIncomeByMachineId($this->order['m_id']);
        if ($si) {
            $smField = "sm_id,manager_id,sort";
            foreach ($si as $sik => $siv) {
                $getField = $smField . "," . $siv["si_id"] . " as si_id,(" . $siv['income_value'] . ") as income_value, " . $siv['transfer_method'] . " as transfer_method";
                $sm = $this->getStrategyManagerList(['s_id' => $siv['si_id'], 's_type' => 1], 0, $getField);
                if ($sm) $sm = $sm->toArray();
                foreach ($sm as $smk => $smv) {
                    $manager = $this->getAuthManagerFind(['manager_id' => $smv['manager_id']],'manager_id,bill_account');
                    if (!$manager) unset($sm[$smk]);
                    $smv['bill_account'] = $manager['bill_account'];
                    $sm[$smk] = $smv;
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
        if ($this->countOrder['pay_type'] == 4) {
            $radio = floor(bcmul(bcdiv(bcsub($this->countOrder['total_price'], 0.01, 3), $this->countOrder['total_price'], 3), 100));
        }
        return $radio;
    }

}