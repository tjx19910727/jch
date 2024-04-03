<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/5
 * Time: 16:11
 */

namespace app\AppFactory\Machine\Receive;


use app\AppFactory\Kernel\ServiceContainer;
use app\AppFactory\Kernel\Traits\Activity\ActivityCouponTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityCouponUsedTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityFdContentTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityFdTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityFdUsedTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityGoodsTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityLotteryConfigTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityLotteryContentTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityLotteryTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityLotteryUsedGoodsTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityLotteryUsedTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityMachineTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityPickCodeTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityPickTrait;
use app\AppFactory\Kernel\Traits\Auth\AuthManagerTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineChannelTrait;
use app\AppFactory\Kernel\Traits\Payment\AfterOrderPaymentTrait;
use app\AppFactory\Kernel\Traits\Payment\BeforeOrderPaymentTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersRevenueTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyIncomeTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyMachineTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyManagerTrait;

class ActivityClient extends ReceiveBaseClient
{
    use ActivityCouponTrait, ActivityCouponUsedTrait;
    use ActivityLotteryTrait, ActivityLotteryConfigTrait, ActivityLotteryContentTrait, ActivityLotteryUsedTrait, ActivityLotteryUsedGoodsTrait;
    use ActivityPickTrait, ActivityPickCodeTrait;
    use ActivityFdTrait,ActivityFdContentTrait,ActivityFdUsedTrait;
    use ActivityMachineTrait, ActivityGoodsTrait;
    use SaleOrdersTrait,SaleOrdersRevenueTrait;
    use MachineChannelTrait;
    use BeforeOrderPaymentTrait,AfterOrderPaymentTrait;
    use AuthManagerTrait,StrategyMachineTrait,StrategyManagerTrait,StrategyIncomeTrait;

    protected $order;

    public function __construct(ServiceContainer $app)
    {
        parent::__construct($app);
    }

    /**
     * 获取适用设备的优惠券活动列表
     * @return array|string
     */
    public function getByMachine()
    {
        return $this->rQ($this->getAcByMachine());
    }

    /**
     * 使用优惠券码获取优惠券信息
     * @return array|string
     */
    public function getByCode()
    {
        $ac = $this->getAcByCode();
        if (is_string($ac)) {
            return $this->rFail($ac);
        }
        return $this->r(200, $this->lang("query_success"), ['ac' => $ac]);
    }

    /**
     * 通过设备获取取货码活动信息
     * @return array|string
     */
    public function getApByMachine()
    {
        return $this->rQ($this->getActivityPickByMachine());
    }

    /**
     * 通过取货码查询活动
     * @return array|string
     */
    public function getApByCode()
    {
        $ap = $this->getActivityPickByCode();
        if (is_string($ap)) {
            return $this->rFail($ap);
        }
        return $this->r(200, $this->lang("query_success"), ['ap' => $ap]);
    }

    /**
     * 获取设备绑定的盲盒活动
     * @return array|string
     */
    public function getAlByMachine()
    {
        return $this->rQ($this->getActivityLotteryByMachine());
    }

    /**
     * 生产抽奖订单信息
     * @return array|string
     */
    public function lotteryOrder()
    {
        if ($this->data['pay_type'] != 4 && $this->data['pay_type'] != 0) return $this->rFail($this->lang("VSubCar.pay_type_no_range"));
        $updateAl = [];
        $alc = $this->getActivityLotteryConfigFind(['alc_id' => $this->data['alc_id']]);
        if (!$alc) return $this->rFail($this->lang("alc_no_data"));
        $alc = $alc->toArray();
        // 检查活动内容
        $al = $this->getActivityLotteryFind(['al_id' => $alc['al_id']]);
        if (!$al) return $this->rFail($this->lang("VActivityLottery.al_no_data"));
        if ($al['status'] == 3) return $this->rFail($this->lang("VActivityLottery.status3"));
        if ($al['status'] == 4) return $this->rFail($this->lang("VActivityLottery.status4"));
        if ($al['start_time'] > time()) return $this->rFail($this->lang("VActivityLottery.al_not_begin"));
        if ($al['end_time'] > 0 && $al['end_time'] < time()) {
            $updateAl['status'] = 3;
            return $this->rFail($this->lang("VActivityLottery.status3"));
        }
        if ($al['status'] == 1) {
            $updateAl['status'] = 2;
        }
        if ($updateAl) {
            $updateAl['al_id'] = $al['al_id'];
            $this->updateActivityLottery($updateAl);
        }
        $al['active_num'] += $al['gifts_num'];

        // 检查活动商品
        $content = $this->getActivityLotteryContentList(['al_id' => $al['al_id']], 0,'c_id,retain_num,g_id,probability');
        if (!$content) return $this->rFail($this->lang("VActivityLottery.content_no_data"));
        $content = $content->toArray();
        $totalProbability = array_sum(array_column($content,"probability"));
        if ($totalProbability != 100) return $this->rFail($this->lang("probability_no_100"));
        foreach ($content as $k => $v) {
            $mc = $this->getMachineChannelFind(['m_id' => $this->machine['m_id'], 'g_id' => $v['g_id']], 'channel_code,stock');
            if (!$mc) return $this->rFail($this->lang("VActivityLottery.mc_no_data"));
            $mc = $mc->toArray();
            if ($mc['stock'] < $alc['active_num'] || $mc['stock'] < $alc['active_num'] + $v['retain_num']) {
                return $this->rFail($this->lang("VActivityLottery.under_stock"));
            }
        }

        $trade_no = date("YmdHis") . $this->machine['m_id'] . $this->get_rand_string(6, "num");
        $order = [
            "trade_no" => $trade_no,
            "m_id" => $this->machine['m_id'],
            "machine_name" => $this->machine['machine_name'],
            "machine_id" => $this->machine['machine_id'],
            "manager_id" => $this->machine['manager_id'],
            "ao_id" => $this->machine['ao_id'],
            "order_type" => 4,
            "pay_type" => $this->data['pay_type'],
            "pay_method" => $this->data['pay_method'],
            "total_price" => $this->data['total_price'],
            "total_quantity" => $alc['active_num'],
            "create_date" => strtotime(date("Y-m-d")),
        ];
        // 添加订单信息
        $order['order_id'] = $this->addSaleOrders($order);
        if ($order['order_id']) {
            // 添加抽奖记录主体信息
            $alu = [
                "al_id" => $al['al_id'],
                "alc_id" => $alc['alc_id'],
                "order_id" => $order['order_id'],
                "trade_no" => $order['trade_no'],
                "m_id" => $this->machine['m_id'],
                "machine_id" => $this->machine['machine_id'],
                "machine_name" => $this->machine['machine_name'],
                "price" => $al['price'],
                "quantity" => $alc['active_num'],
                "total_price" => $this->data['total_price'],
                "active_type" => $alc['active_type'],
                "used_date" => strtotime(date("Y-m-d")),
            ];
            $result = $this->addActivityLotteryUsed($alu);
            if ($result) {
                return $this->r(200, $this->lang("VSubCar.make_order_success"), $order);
            }
        }
        return $this->r(100, $this->lang("VSubCar.make_order_fail"));
    }

    /**
     * 执行抽奖
     * @return array|string
     * @throws \Exception
     */
    public function luckyDrawResult()
    {
        $order = $this->getSaleOrdersFind(['order_id' => $this->data['order_id']]);
        if (!$order) {
            return $this->r(100, $this->lang("VActivityLottery.order_no_data"));
        }
        if ($order['order_type'] != 4) return $this->r(100,$this->lang("VActivityLottery.order_type_error"));
        if ($order['pay_status'] != 3) return $this->r(100,$this->lang("VActivityLottery.order_no_pay"));
        $used = $this->getActivityLotteryUsedFind(['order_id' => $order['order_id']]);
        if (!$used) {
            return $this->r(100, $this->lang("VActivityLottery.used_no_data"));
        }
        $alc = $this->getActivityLotteryConfigFind(['alc_id' => $used['alc_id']]);
        if (!$alc) return $this->r(100,$this->lang("VActivityLottery.alc_no_data"));

        $detailsCount = $this->getSaleOrdersDetailsCount(['order_id' => $order['order_id']]);
        if ($detailsCount >= $used['quantity']){
            return $this->r(100,$this->lang("VActivityLottery.lucky_draw_ended"));
        }
        $content = $this->getActivityLotteryContentList(['al_id' => $used['al_id']], 0,'*', "probability asc");
        if (!$content) return $this->rFail($this->lang("VActivityLottery.content_no_data"));
        $content = $content->toArray();
        $totalProbability = array_sum(array_column($content, 'probability'));
        if ($totalProbability != 100) return $this->rFail($this->lang("VActivityLottery.probability_no_100"));
        $list = [];
        // 多抽循环，每次抽奖结果放至中奖列表
        for ($i = 0; $i < $used['quantity']; $i++) {
            $random = mt_rand(1, $totalProbability);
            $probabilitySum = 0;
            // 抽奖，循环活动内容，
            // 活动内容以中奖概率从小到大顺序排序，
            // 中奖值加上每条活动内容的中奖概率，
            // 当中奖值大于随机数时为中奖，将活动内容放入中奖列表，跳出当前循环，执行下轮抽奖
            foreach ($content as $key => $value) {
                if ($alc['designated_gif'] && $alc['designated_gift'] == $value['c_id']) {
                    $list[$used['quantity']] = $value;
                }
                $probabilitySum = bcadd($probabilitySum,$value['probability']);
                if ($probabilitySum > $random) {
                    $list[$i] = $value;
                    break;
                }
            }
        }
        if (!$list) {
            return $this->rFail($this->lang("lottery_empty"));
        }
        $averagePrice = bcdiv($order['total_price'],$order['total_quantity'],3);

        $this->startTrans();
        $flag = [];
        $ugAll = [];
        $mcField = "mc_id,shelf_way,channel_position,channel_code,mg_id,g_id,g_name,pic,sku,gc_id,gc_name,cost_price,market_price,stock";
        foreach ($list as $lk => $lv) {
            $mc = $this->getMachineChannelFind(['g_id' => $lv['g_id'],'m_id' => $order['m_id']],$mcField,'stock desc');
            if (!$mc) {
                $this->rollbackTrans();
                return $this->rFail($this->lang("VActivityLottery.mc_no_data"));
            }
            $mc = $mc->toArray();
            $ug = [
                "al_id" => $used['al_id'],
                "alu_id" => $used['alu_id'],
                "g_id" => $mc['g_id'],
                "g_name" => $mc['g_name'],
                "sku" => $mc['sku'],
                "probability" => $lv['probability'],
                "mc_id" => $mc['mc_id'],
                "channel_code" => $mc['channel_code'],
                "quantity" => 1,
            ];
            $sod = $this->getSaleOrdersDetailsFind(['order_id' => $order['order_id'],'mc_id' => $mc['mc_id']]);
            if (!$sod) {
                unset($mc['stock']);
                $insertSod = $mc;
                $insertSod['order_id'] = $order['order_id'];
                $insertSod['quantity'] = 1;
                $insertSod['total_sod_price'] = $averagePrice;
                $insertSod['retail_price'] = $averagePrice;
                $sod_id = $this->addSaleOrdersDetails($insertSod);
                $flag[] = $sod_id;
            } else {
                $sod_id = $sod['sod_id'];
                $update['sod_id'] = $sod_id;
                $update['quantity'] = $sod['quantity']++;
                $update['retail_price'] = bcdiv($averagePrice,$update['quantity'],3);
                $flag[] = $this->updateSaleOrdersDetails($update);
            }
            $ug['sod_id'] = $sod_id;
            $ugAll[] = $ug;
        }

        $flag[] = $this->addActivityLotteryUsedGoodsMore($ugAll);
        $check = $this->checkFlag($flag);
        if ($check) {
            $this->commitTrans();
            $order['details'] = $this->getSaleOrdersDetailsList(['order_id' => $order['order_id']],0);
            $totalQuantity = array_sum(array_column($order['details'],'quantity'));
            if ($totalQuantity == $used['quantity']) {
                $this->updateActivityLotteryUsed(['alu_id' => $used['alu_id'],'status' => 2,'used_date' => strtotime(date("Y-m-d"))]);
            }
            return $this->r(200,$this->lang("action_success"),['lottery_list' => $list,"order" => $order]);
        }
        $this->rollbackTrans();
        return $this->r(100,$this->lang("action_fail"));
    }

    /**
     * 抽奖订单出货
     * @return array|string
     * @throws \Exception
     */
    public function lotteryOutGoods()
    {
        $this->order = $this->getSaleOrdersFind(['order_id' => $this->data['order_id']]);
        if (!$this->order) return $this->rFail($this->lang("VActivityLottery.order_no_data"));
        if ($this->order['pay_status'] != 3) return $this->rFail($this->lang("VActivityLottery.order_no_pay"));
        if ($this->order['out_status'] > 1) return $this->rFail($this->lang("VActivityLottery.is_out_goods"));
        $whereDetails['order_id'] = $this->order['order_id'];
        $used = $this->getActivityLotteryUsedFind(['order_id' => $this->order['order_id']]);
        if (!$used) return $this->r(100, $this->lang("VActivityLottery.used_no_data"));
        $quantity = $this->getSaleOrdersDetailsSum($whereDetails,'quantity');
        if ($used['quantity'] != $quantity) return $this->r(100,$this->lang("VActivityLottery.quantity_not_match"));
        $this->order['details'] = $this->getSaleOrdersDetailsList(['order_id' => $this->order['order_id']],0,'*');
        if (!$this->order['details']) return $this->r(100,$this->lang("VActivityLottery.order_details_no_data"));
        $this->startTrans();
        $flag = [];
        // 生成分润记录
        $flag[] = $this->countIncome();
        // 修改分润记录
        $flag[] = $this->settlementRevenue();
        // 修改为发送出货命令状态
        $flag[] = $this->updateSaleOrders(['order_id' => $this->order['order_id'],'out_status' => 2]);
        $check = $this->checkFlag($flag);
        if ($check) {
            $this->outGoods();
            $this->commitTrans();
            return $this->r(200,$this->lang("action_success"));
        }
        $this->rollbackTrans();
        return $this->r(100,$this->lang("action_fail"));
    }

    /**
     * 根据设备获取满减满送活动信息列表
     * @return array|string
     */
    public function getFdByMachine()
    {
        return $this->rQ($this->getActivityFdByMachine());
    }

    /**
     * 订单使用满减满送活动
     * @return mixed
     */
    public function useFd()
    {
        return $this->orderUseFd();
    }
}