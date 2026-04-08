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
use app\AppFactory\Kernel\Traits\Strategy\StrategyManagerTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyIncomeTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyPayeeTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyMachineTrait;
use app\AppFactory\Kernel\Traits\Auth\AuthOrgRevenueTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersRevenueTrait;
use app\AppFactory\Kernel\Traits\Auth\AuthManagerTrait;
trait BeforeOrderPaymentTrait
{
    use StrategyManagerTrait,StrategyIncomeTrait,StrategyPayeeTrait,StrategyMachineTrait,
    AuthOrgRevenueTrait,SaleOrdersRevenueTrait,AuthManagerTrait;
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
        // $this->getStrategy();
        // return $this->addRevenue();
        return $this->addAuthOrgMachineChannelRevenue();
    }

    /**
     * 生成货道租赁待分润记录
     */
    public function addAuthOrgMachineChannelRevenue(){
        $this->order['details'] = $this->order['detail'] ?? $this->getSaleOrdersDetailsList(['order_id' => $this->order['order_id']])->toArray();
        $this->countOrder = $this->order;
        $flag[] = 1;
        foreach ($this->countOrder['details'] as $sodKey => $sodValue) {
            $strategy_machine = $this->getStrategyMachineFind(['s_type' => 3,'ao_id' => $sodValue['sod_ao_id'],'m_id' => $this->countOrder['m_id']]);
            if(!$strategy_machine) continue;
            $strategy_machine = $strategy_machine->toArray();
            $strategy_income = $this->getStrategyIncomeFind(['si_id' => $strategy_machine['s_id']]);
            if(!$strategy_income) continue;
            $strategy_income = $strategy_income->toArray();
            $radio = $strategy_income['income_value'] ?? 0;
            $strategy_manager = $this->getStrategyManagerFind(['s_id' => $strategy_machine['s_id'],'s_type' => 1,'ao_id' => $sodValue['sod_ao_id'],'m_id' => $this->countOrder['m_id']]);
            if(!$strategy_manager) continue;
            $strategy_manager = $strategy_manager->toArray();
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
                'si_id' => $strategy_machine['s_id'],
                'income_value' => $radio,
                'manager_id' => $strategy_manager['manager_id'],
                // 'bill_account' => $strategy_machine['s_id'],
                'revenue_type' => 4,
                'income_amount' =>  bcmul($sodValue['total_sod_price'], bcmul($radio, 0.01, 3), 3),
                'ao_id' => $sodValue['sod_ao_id'],
            ];
            $sor_id = $this->addSaleOrdersRevenue($insert);
            // 写入组织分账日志
            $log = [
                'ao_id' => $sodValue['sod_ao_id'] ?? 0,
                'order_id' => $insert['order_id'] ?? '',
                'sp_id' => $insert['sp_id'] ?? 0,
                'm_id' => $insert['m_id'] ?? 0,
                'machine_name' => $insert['machine_name'] ?? '',
                'machine_id' => $insert['machine_id'] ?? '',
                'order_amount' => $insert['order_amount'] ?? 0,
                'sod_id' => $insert['sod_id'] ?? '',
                'sod_amount' => $insert['sod_amount'] ?? 0,
                'sod_quantity' => $insert['sod_quantity'] ?? 0,
                'sod_total_price' => $insert['sod_total_price'] ?? 0,
                'si_id' => $insert['si_id'] ?? 0,
                'income_value' => $insert['income_value'] ?? 0,
                'revenue_type' => $insert['revenue_type'] ?? 0,
                'income_amount' => $insert['income_amount'] ?? 0,
            ];
            $aor_id = $this->addAuthOrgRevenueLog($log);
            $flag[] = $sor_id;
            actionLog($sor_id,'生成分润记录sor_id');
            actionLog($aor_id,'生成组织分账日志aor_id');
        }
        return flag_check($flag);
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
                    if ($income_amount >= 0.01 && $sodRadio > 0) {
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