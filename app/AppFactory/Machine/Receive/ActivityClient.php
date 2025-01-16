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
use app\AppFactory\Kernel\Traits\Api\ApiAdvanceTrait;
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
    use ActivityFdTrait, ActivityFdContentTrait, ActivityFdUsedTrait;
    use ActivityMachineTrait, ActivityGoodsTrait;
    use SaleOrdersTrait, SaleOrdersRevenueTrait;
    use MachineChannelTrait;
    use BeforeOrderPaymentTrait, AfterOrderPaymentTrait;
    use StrategyMachineTrait, StrategyManagerTrait, StrategyIncomeTrait;
    use ApiAdvanceTrait;

    protected $order;

    public function __construct(ServiceContainer $app)
    {
        parent::__construct($app);
    }

    /**
     * 销毁实例时触发
     */
    public function __destruct()
    {
        // TODO: Implement __destruct() method.
        $result = $this->updateMachineMqRecord(['status' => 2, 'msg_id' => $this->data['msg_id']], ['msg_id' => $this->data['msg_id']]);
        actionLog($result, '处理完成时修改状态为已处理');
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
        $apc = $this->getActivityPickByCode();
        if (is_string($apc)) {
            return $this->rFail($apc);
        }
        return $this->r(200, $this->lang("query_success"), ['apc' => $apc]);
    }

    /**
     * 使用优惠券
     * @return array|bool|\think\response\Json
     */
    public function useCoupon()
    {
        $this->order = $this->getSaleOrdersFind(['order_id' => $this->data['order_id']]);
        if (!$this->order) return $this->r(100, $this->lang("VActivityPickCode.order_no_data"));
        if ($this->order['out_status'] != 1) return $this->r(100, $this->lang("VActivityPickCode."));

        // 有优惠券码，重新处理订单数据
        if (isset($this->data['coupon_code'])) {
            try {
                $result = $this->orderUseCoupon();
                if ($result !== true) {
                    return $result;
                }
                $this->order['details'] = $this->getSaleOrdersDetailsList(['order_id' => $this->order['order_id']],0,'*');
            } catch (\Exception $e) {
                actionException($e,1);
                return $this->rTryCatch($e->getMessage());
            }
        }
        return $this->r(200,"操作成功",$this->order);
    }

    /**
     * 使用取货码
     * @return array|int|string|\think\response\Json
     */
    public function usePickCode()
    {

        try {
            $this->startTrans();
            $apc = $this->getActivityPickByCode();
            if (!is_array($apc)) {
                $this->rollbackTrans();
                return $this->rFail($apc);
            }
            actionLog($apc, "使用的取货码");
            $flag = [];
            // 预订订单取货
            if ($apc['pick_type'] == 3) {
                $this->order = $this->getSaleOrdersFind(['order_id' => $apc['order_id']]);
                if (!$this->order) return $this->r(100, $this->lang("VActivityPickCode.order_no_data"));
                $this->order = $this->order->toArray();
                if ($this->order['m_id'] != $this->machine['m_id']) return $this->r(100,$this->lang("VActivityPickCode.pick_code_can_not_use"));
                if ($this->order['out_status'] != 1) return $this->r(100, $this->lang("VActivityPickCode.out_status1"));
            } else {
                // 系统随机取货，随机获取货架商品信息生成取货商品，整理carList，如果pick_type==2，则carList由外部传入
                if ($apc['pick_type'] == 1) {
                    $ap = $apc['ap'];
                    if (!$ap['ag']) {
                        return $this->lang("VActivityPick.ag_not_data");
                    }
                    $apg_id = array_column($ap['ag'], 'g_id');
                    $whereMc[] = ['g_id', 'in', $apg_id];
                    $whereMc['status'] = 1;
                    $whereMc['m_id'] = $this->machine['m_id'];
                    $whereMc[] = ['stock', '>',0];
                    $mc = $this->getMachineChannelColumn($whereMc, 'mc_id');
                    if (!$mc) {
                        return $this->r(100,$this->lang("VActivityPickCode.mc_id_empty"));
                    }
                    $mc_count = count($mc);
                    $num = random_int(1, $mc_count);
                    // 只取一个商品
                    $this->data['carList'][] = ["mc_id" => $mc[($num - 1)], 'quantity' => 1];
                }
                $trade_no = date("YmdHis") . $this->machine['m_id'] . $this->get_rand_string(6, "num");
                $order = [
                    "trade_no" => $trade_no,
                    "mch_no" => $trade_no,
                    "m_id" => $this->machine['m_id'],
                    "machine_name" => $this->machine['machine_name'],
                    "machine_id" => $this->machine['machine_id'],
                    "order_type" => 3,
                    "pay_status" => 2,
                    "pay_time" => time(),
                    "pay_code" => $this->data['pick_code'],
                    "apc_id" => $apc['apc_id'],
                    "ao_id" => $this->machine['ao_id'],
                    "create_date" => strtotime(date("Y-m-d")),
                ];
                $order_id = $this->addSaleOrders($order);
                if ($order_id) {
                    $mcField = "mc_id,channel_code,frozen_stock,stock,shelf_way,channel_position,manufacture_time,sell_by_date,
                            mg_id,g_id,g_name,gc_id,gc_name,pic,sku,bar_code,batch_number,
                            cost_price,market_price";
                    $updateOrder['order_id'] = $order_id;
                    $updateOrder['total_quantity'] = 0;
                    foreach ($this->data['carList'] as $key => $value) {
                        $mc = $this->getMachineChannelFind(['mc_id' => $value['mc_id']], $mcField);
                        if (!$mc) {
                            $this->rollbackTrans();
                            return $this->r(100, $this->lang("VSubCar.channel_no_data"));
                        }
                        $mc = $mc->toArray();
                        if ($mc['stock'] < $value['quantity']) {
                            $this->rollbackTrans();
                            return $this->r(100, $this->lang("VSubCar.under_stock"));
                        }
                        $details = [
                            "order_id" => $order_id,
                            "retail_price" => 0,
                            "total_sod_price" => 0,
                            "quantity" => $value['quantity'],
                        ];
                        $details = array_merge($details, $mc);
                        $sod_id = $this->addSaleOrdersDetails($details);
                        actionLog($this->getLS(), '生成订单详情数据');
                        if ($sod_id) {
                            $updateOrder['total_quantity'] = bcadd($updateOrder['total_quantity'], $value['quantity']);
                        } else {
                            $this->rollbackTrans();
                            return $this->r(100, $this->lang("VSubCar.make_order_details_fail"));
                        }
                    }
                    $flag[] = $this->updateSaleOrders($updateOrder);
                    actionLog($this->getLS(), '修改订单数据');
                    $flag[] = $this->updateActivityPickCode(['apc_id' => $apc['apc_id'], 'order_id' => $order_id, 'trade_no' => $trade_no]);
                    actionLog($this->getLS(), '修改取货码数据');
                }
                if (!$order_id) {
                    $this->rollbackTrans();
                    return $this->r(100, $this->lang("VActivityPickCode.add_order_fail"));
                }
                $this->order = $this->getSaleOrdersFind(['order_id' => $order_id])->toArray();
            }
            $this->order['details'] = $this->getSaleOrdersDetailsList(['order_id' => $this->order['order_id']]);
            if ($this->order['total_price'] > 0) {
                $flag[] = $this->countIncome();
            }
            $result = flag_check($flag);
            if ($result) {
                $result = $this->outGoods();
                if ($result !== true) {
                    $this->rollbackTrans();
                    return $result;
                }
                if (isset($this->order['details'])) unset($this->order['details']);
                $this->updateSaleOrders($this->order);
                $this->updateActivityPickCode(['apc_id' => $apc['apc_id'], 'status' => 5]);
                $this->updateApiAdvance(['status' => "PROCESSING"], ['apc_id' => $apc['apc_id']]);
                $this->commitTrans();
                return $this->r(200,$this->lang("action_success"),$this->order);
            }
            $this->rollbackTrans();
            return $this->rFail();
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 获取设备绑定的付费抽奖活动
     * @return array|string
     */
    public function getAlByMachine()
    {
        return $this->rQ($this->getActivityLotteryByMachine());
    }

    /**
     * 生成抽奖订单信息
     * @return array|string
     */
    public function lotteryOrder()
    {
        if ($this->data['pay_type'] != 4 && $this->data['pay_type'] != 0) return $this->rFail($this->lang("VSubCar.pay_type_no_range"));
        $updateAl = [];
        $alc = $this->getActivityLotteryConfigFind(['alc_id' => $this->data['alc_id']]);
        if (!$alc) return $this->r(300,$this->lang("alc_no_data"));
        $alc = $alc->toArray();
        // 检查活动内容
        $al = $this->getActivityLotteryFind(['al_id' => $alc['al_id']]);
        if (!$al) return $this->r(300,$this->lang("VActivityLottery.al_no_data"));
        if ($al['status'] == 3) return $this->r(300,$this->lang("VActivityLottery.status3"));
        if ($al['status'] == 4) return $this->r(300,$this->lang("VActivityLottery.status4"));
        if ($al['start_time'] > time()) return $this->r(300,$this->lang("VActivityLottery.al_not_begin"));
        if ($al['end_time'] > 0 && $al['end_time'] < time()) {
            $updateAl['status'] = 3;
            return $this->r(300,$this->lang("VActivityLottery.status3"));
        }
        if ($al['status'] == 1) {
            $updateAl['status'] = 2;
        }
        if ($updateAl) {
            $updateAl['al_id'] = $al['al_id'];
            $this->updateActivityLottery($updateAl);
        }
        // 增加赠送次数
        $alc['active_num'] += $alc['gifts_num'];

        // 检查活动商品
        $content = $this->getActivityLotteryContentList(['al_id' => $al['al_id']], 0, 'c_id,retain_num,g_id,probability,g_name');
        if (!$content) return $this->r(300,$this->lang("VActivityLottery.content_no_data"));
        $content = $content->toArray();
        actionLog($content,'活动商品内容');
        // 总中奖概率，必须要刚好100%
        $totalProbability = array_sum(array_column($content, "probability"));
        if ($totalProbability != 100) return $this->r(300,$this->lang("probability_no_100"));

        // 如果存在赠送商品，则校对数量再加1
        $checkStock = $alc['active_num'];
        if ($alc['designated_gift']) $checkStock = $alc['active_num'] + 1;
        $mcList = [];
        // 循环活动内容，
        foreach ($content as $k => $v) {
            // 判断货架正常，有这个商品
            $mc = $this->getMachineChannelFind(['m_id' => $this->machine['m_id'], 'status' => 1, 'g_id' => $v['g_id'],['stock',">=",$checkStock + $v['retain_num'] ]], 'channel_code,stock','stock desc');
            if (!$mc) {
                actionLog($this->getLS(),'货道禁用或没库存');
                continue;
//                return $this->rFail($this->lang("VActivityLottery.mc_no_data") . "【" . $v['g_name'] . "】" . $this->lang("VActivityLottery.goods_not_data"));
            }
            $mcList[] = $mc;
        }
        // 所有商品都没库存或禁用，则返回无活动商品
        if (!$mcList) return $this->r(300,$this->lang("VActivityLottery.content_no_data"));
        $trade_no = date("YmdHis") . $this->machine['m_id'] . $this->get_rand_string(6, "num");
        $order = [
            "trade_no" => $trade_no,
            "m_id" => $this->machine['m_id'],
            "machine_name" => $this->machine['machine_name'],
            "machine_id" => $this->machine['machine_id'],
            "manager_id" => $this->machine['manager_id'],
            "ao_id" => $this->machine['ao_id'],
            "order_type" => 4,
            "lottery_id" => $al['al_id'],
            "pay_type" => $this->data['pay_type'],
            "pay_method" => $this->data['pay_method'],
            "total_price" => $this->data['total_price'],
            "total_quantity" => $alc['active_num'],  // 商品总数，执行完成抽奖后需要重新校对
            "create_date" => strtotime(date("Y-m-d")),
        ];
        if ($this->data['total_price'] == 0) {
            $order['pay_status'] = 3;
            $order['pay_time'] = time();
            $order['pay_type'] = 0;
            $order['pay_method'] = 1;
        }
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
                "quantity" => $alc['active_num'],  // 抽奖总次数，包含赠送次数，不包含赠送商品
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
            return $this->r(300, $this->lang("VActivityLottery.order_no_data"));
        }
        if ($order['order_type'] != 4) return $this->r(300, $this->lang("VActivityLottery.order_type_error"));
        if ($order['pay_status'] != 3) return $this->r(300, $this->lang("VActivityLottery.order_no_pay"));

        $used = $this->getActivityLotteryUsedFind(['order_id' => $order['order_id']], 'alu_id,al_id,alc_id,quantity,used_quantity');
        if (!$used) {
            return $this->r(300, $this->lang("VActivityLottery.used_no_data"));
        }
        $used = $used->toArray();
        actionLog($used,'执行抽奖查询已使用抽奖数据');
        // 已使用抽奖次数大于等于抽奖总次数，返回抽奖数量已用完
        if ($used['used_quantity'] >= $used['quantity']) {
            return $this->r(300, $this->lang("VActivityLottery.lucky_draw_ended"));
        }

        $alc = $this->getActivityLotteryConfigFind(['alc_id' => $used['alc_id']]);
        if (!$alc) return $this->r(300, $this->lang("VActivityLottery.alc_no_data"));
        $content = $this->getActivityLotteryContentList(['al_id' => $used['al_id']], 0, '*', "probability asc");
        if (!$content) return $this->r(300,$this->lang("VActivityLottery.content_no_data"));
        $content = $content->toArray();
        actionLog($content,'抽奖活动内容');

        // 总中奖概率
        $totalProbability = array_sum(array_column($content, 'probability'));
        if ($totalProbability != 100) return $this->r(300,$this->lang("VActivityLottery.probability_no_100"));
        // 本次执行抽奖次数初始化，总数量减去已抽次数
        $quantity = bcsub($used['quantity'], $used['used_quantity']);
        if ($quantity <= 0) return $this->r(300,$this->lang("VActivityLottery.used_quantity_is_null"));
        // 单抽状态下，每次执行抽奖时抽奖次数重置为1
        if ($alc['active_type'] == 1) $quantity = 1;

        // 库存不足或禁用货架，不存在这个商品上架的情况下不参与抽奖
        $whereMcGid = function ($query) use ($quantity)  {
            $query->where("`m_id` = " . $this->machine['m_id'] . " AND (`status` <> 1 OR `stock` > $quantity)");
        };
        $channel = $this->getMachineChannelColumn($whereMcGid,'g_id');
        actionLog($this->getLS(),'【SQL】获取设备货道商品ID');
        actionLog($channel,'货道商品ID');
        $temp = [];
        // 有这个商品在设备货道上，正常且有库存的，重置抽奖列表
        foreach ($content as $cK => $cV) {
            if (in_array($cV['g_id'],$channel)) {
                $temp[] = $cV;
            }
        }
        actionLog($temp,'重新整理的抽奖活动内容');
        if (!$temp) return $this->r(300,$this->lang("VActivityLottery.content_no_data"));
        $content = $temp;
        $totalProbability = array_sum(array_column($content, 'probability'));
        actionLog($totalProbability,'重新计算的总中奖率');

        // 执行抽奖主程序
        $giftNum = 0;
        $list = [];
        if ($content) {
            // 多抽循环，每次抽奖结果放至中奖列表
            for ($i = 0; $i < $quantity; $i++) {
                // 已抽奖次数+1
                $used['used_quantity']++;

                // 每次抽奖的随机数值
                $random = mt_rand(1, $totalProbability);
                actionLog($random, "第" . $i . "次随机数值");
                $probabilitySum = 0;
                // 抽奖，循环活动内容，以中奖概率从小到大顺序排序，
                foreach ($content as $key => $value) {
                    // 赠送指定商品
                    if ($alc['designated_gif']) {
                        // 是赠送的商品，放入中奖列表队尾
                        if ($alc['designated_gift'] == $value['c_id']) {
                            $value['is_gift'] = 1;
                            $giftNum++;
                            $list[$i + $quantity] = $value;
                        }
                    }
                    // 当前一条抽奖活动内容中奖数值，叠加前面的活动内容中奖数值
                    $probabilitySum = bcadd($probabilitySum, $value['probability']);
                    // 中奖数值大于随机数值即为中奖，活动内容放入中奖数组中，退出当前抽奖循环，执行下轮抽奖
                    actionLog($probabilitySum, "第" . $i . "次" . $value['c_id'] . "活动内容中奖数值");
                    if ($probabilitySum >= $random) {
                        actionLog($value, "第" . $i . "次" . "中奖数据");
                        $list[$i] = $value;
                        break;
                    }
                }
            }
        }
        actionLog($list,'中奖列表');
        $this->startTrans();

        try {// 修改已抽奖次数
            $this->updateActivityFdUsed(['used_quantity' => $used['used_quantity']], ['alu_id' => $used['alu_id']]);
            // 没抽中，返回谢谢惠顾
            if (!$list) {
                $this->commitTrans();
                $order['details'] = $this->getSaleOrdersDetailsList(['order_id' => $order['order_id']], 0)->toArray();
                return $this->r(200, $this->lang("VActivityLottery.lottery_empty"), ['lottery_list' => $list, "order" => $order]);
            }
            $averagePrice = bcdiv($order['total_price'], $used['quantity'], 3);
            $flag = [];
            $ugAll = [];
            $mcField = "mc_id,shelf_way,channel_position,channel_code,mg_id,g_id,g_name,pic,sku,gc_id,gc_name,cost_price,market_price,stock";
            foreach ($list as $lk => $lv) {
                $mc = $this->getMachineChannelFind(['g_id' => $lv['g_id'], 'm_id' => $order['m_id']], $mcField, 'stock desc');
                if (!$mc) {
                    actionLog($this->getLS(),'中奖商品不在这台设备中');
                    $this->rollbackTrans();
                    return $this->r(300,$this->lang("VActivityLottery.mc_no_data"));
                }
                $mc = $mc->toArray();
                $ug = [
                    "al_id" => $used['al_id'],
                    "alu_id" => $used['alu_id'],
                    "alc_id" => $lv['c_id'],
                    "g_id" => $mc['g_id'],
                    "g_name" => $mc['g_name'],
                    "sku" => $mc['sku'],
                    "probability" => $lv['probability'],
                    "mc_id" => $mc['mc_id'],
                    "channel_code" => $mc['channel_code'],
                    "quantity" => 1,
                ];
                // 不存在商品记录则生成，存在商品则数量+1
                $sod = $this->getSaleOrdersDetailsFind(['order_id' => $order['order_id'], 'mc_id' => $mc['mc_id']]);
                if (!$sod || ($sod && isset($lv['is_gift']))) {
                    unset($mc['stock']);
                    $insertSod = $mc;
                    $insertSod['order_id'] = $order['order_id'];
                    $insertSod['quantity'] = 1;
                    $insertSod['total_sod_price'] = $averagePrice;
                    $insertSod['retail_price'] = $averagePrice;
                    if (isset($lv['is_gift'])) {
                        $insertSod['total_sod_price'] = 0;
                        $insertSod['retail_price'] = 0;
                    }
                    $sod_id = $this->addSaleOrdersDetails($insertSod);
                    $flag[] = $sod_id;
                } else {
                    $sod = $sod->toArray();
                    $sod_id = $sod['sod_id'];
                    $update['sod_id'] = $sod_id;
                    $update['quantity'] = $sod['quantity'] + 1;
                    $update['retail_price'] = bcdiv($averagePrice, $update['quantity'], 3);
                    $flag[] = $this->updateSaleOrdersDetails($update);
                }
                actionLog($this->getLS(),'订单详情处理SQL');
                $ug['sod_id'] = $sod_id;
                $ugAll[] = $ug;
            }
            $flag[] = $this->addActivityLotteryUsedGoodsMore($ugAll);
            actionLog($this->getLS(),'生成中奖记录数据');
            actionLog($flag,'事务执行结果');
            $check = $this->checkFlag($flag);
            if ($check) {
                $this->commitTrans();
                $order['details'] = $this->getSaleOrdersDetailsList(['order_id' => $order['order_id']], 0)->toArray();
                $totalQuantity = array_sum(array_column($order['details'], 'quantity'));
                if ($totalQuantity == $used['quantity']) {
                    $this->updateActivityLotteryUsed(['alu_id' => $used['alu_id'], 'status' => 2, 'used_date' => strtotime(date("Y-m-d"))]);
                    actionLog($this->getLS(),'订单商品总数量等于抽奖记录的数量，执行修改抽奖记录');
                }
                return $this->r(200, $this->lang("action_success"), ['lottery_list' => $list, "order" => $order]);
            }
            $this->rollbackTrans();
            return $this->r(300, $this->lang("action_fail"));
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 抽奖订单出货
     * @return array|string
     * @throws \Exception
     */
    public function lotteryOutGoods()
    {
        $this->order = $this->getSaleOrdersFind(['order_id' => $this->data['order_id']]);
        if (!$this->order) return $this->r(300,$this->lang("VActivityLottery.order_no_data"));
        if ($this->order['pay_status'] != 3) return $this->r(300,$this->lang("VActivityLottery.order_no_pay"));
        if ($this->order['out_status'] > 1) return $this->r(300,$this->lang("VActivityLottery.is_out_goods"));
        $whereDetails['order_id'] = $this->order['order_id'];
        $used = $this->getActivityLotteryUsedFind(['order_id' => $this->order['order_id']]);
        if (!$used) return $this->r(300, $this->lang("VActivityLottery.used_no_data"));
        $quantity = $this->getSaleOrdersDetailsSum($whereDetails, 'quantity');
        if ($used['quantity'] != $quantity) return $this->r(300, $this->lang("VActivityLottery.quantity_not_match"));
        $this->order['details'] = $this->getSaleOrdersDetailsList(['order_id' => $this->order['order_id']], 0, '*');
        if (!$this->order['details']) return $this->r(300, $this->lang("VActivityLottery.order_details_no_data"));
        $this->startTrans();
        try {
            $flag = [];// 生成分润记录
            $flag[] = $this->countIncome();// 修改分润记录
            $flag[] = $this->settlementRevenue();// 修改为发送出货命令状态
            $updateOrder['order_id'] = $this->order['order_id'];
            $updateOrder['out_status'] = 2;
            if ($this->order['pay_status'] != 3) {
                $updateOrder['pay_status'] = 3;
                $updateOrder['pay_type'] = 0;
                $updateOrder['pay_method'] = 1;
                $updateOrder['pay_time'] = time();
            }
            $flag[] = $this->updateSaleOrders($updateOrder);
            $check = $this->checkFlag($flag);
            if ($check) {
                $this->outGoods();
                $this->commitTrans();
                return $this->r(200, $this->lang("action_success"));
            }
            $this->rollbackTrans();
            return $this->r(300, $this->lang("action_fail"));
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
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