<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/4
 * Time: 13:58
 */

namespace app\AppFactory\Kernel\Traits\Activity;


use app\AppFactory\Kernel\Model\Activity\Coupon\ActivityCouponModel;
use app\AppFactory\Kernel\Model\Revenue\RevenueOrderModel;
use app\AppFactory\Kernel\Service\Revenue\RevenueCouponService;
use think\response\Json;

trait ActivityCouponTrait
{
    public function getActivityCouponColumn($where, $column)
    {
        return ActivityCouponModel::getColumn($where, $column);
    }

    public function getActivityCouponValue($where,$value)
    {
        return ActivityCouponModel::getFieldValue($where,$value);
    }
    /**
     * 获取一条优惠券活动
     * @param $where
     * @param string $field
     * @param string $order
     * @return mixed
     */
    public function getActivityCouponFind($where, $field = "*", $order = "")
    {
        return ActivityCouponModel::getFind($where, $field, $order);
    }

    public function getActivityCouponList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "")
    {
        return ActivityCouponModel::getList($where, $pageNum, $field, $order, $eachFun);
    }

    public function getActivityCouponByMachine($where, $field = "*", $order = "c_id desc")
    {
        return ActivityCouponModel::getListByMachine($where, $field, $order);
    }

    public function addActivityCoupon($insert)
    {
        !isset($this->manager['manager_id']) ?: $insert['creator'] = $this->manager['manager_id'];
        $data = ActivityCouponModel::create($insert);
        return $data->c_id;
    }

    public function updateActivityCoupon($update, $where = [], $field = [])
    {
        !isset($this->manager['manager_id']) ?: $update['update_id'] = $this->manager['manager_id'];
        return ActivityCouponModel::update($update, $where, $field);
    }

    public function incActivityCoupon($where,$field,$inc = 1)
    {
        return ActivityCouponModel::setInc($where,$field,$inc);
    }

    public function decActivityCoupon($where,$field,$dec = 1)
    {
        return ActivityCouponModel::setDec($where,$field,$dec);
    }

    public function delActivityCoupon($where)
    {
        $ac = $this->getActivityCouponFind($where, 'c_id');
        $result = ActivityCouponModel::whereDel($where);
        if ($result) {
            $this->delActivityCouponUsed(['c_id' => $ac['c_id']]);
            $this->delActivityMachine(['a_id' => $ac['c_id'], 'a_type' => 1]);
            $this->delActivityGoods(['a_id' => $ac['c_id'], 'a_type' => 1]);
        }
        return $result;
    }


    /**
     * 获取优惠券
     * @return array|string
     */
    public function getAcByMachine()
    {
        $managerIds = implode(",",$this->getAuthManagerMachineColumn(['m_id' => $this->machine['m_id']],'manager_id'));
        $where = "(ac.designated_machine = 1 or (am.m_id = " . $this->machine['m_id'] . " AND am.a_type = 1)) AND start_date < " . strtotime(date("Y-m-d H:i:s")) . " AND status < 3 AND (
        end_date is null or end_date > " . strtotime(date("Y-m-d H:i:s")) . ")";
        if ($managerIds) $where .= " and creator in ($managerIds)";
        $field = "c_id,c_name,desc,start_date,end_date,c_type,reduction,used_limit,pay_limit,designated_goods,designated_machine,status";
        $ac = $this->getActivityCouponByMachine($where, $field);
        actionLog($this->getLS(),'【SQL】查询优惠券');
        if ($ac) {
            $ac = $ac->toArray();
            $agField = "g_id,g_name,pic,sku,market_price,retail_price,gc_id,gc_name";
            foreach ($ac as $key => $value) {
                $update = [];
                if ($value['status'] == 1) {
                    $value['status'] = 2;
                    $update['status'] = 2;
                }
                if ($value['end_date'] > 0 && $value['end_date'] < strtotime(date("Y-m-d")) && $value['status'] != 3) {
                    $value['status'] = 3;
                    $update['status'] = 3;
                }
                if ($update) $this->updateActivityCoupon($update,['c_id' => $value['c_id']]);
                $ac[$key] = $value;
                $gIds = $this->getMachineChannelColumn(['m_id' => $this->machine['m_id'],'status' => 1],'g_id');
                $whereAg['a_id'] = $value['c_id'];
                $whereAg['a_type'] = 1;
                if ($gIds) $whereAg[] = ['g_id','in',$gIds];
                $ac[$key]['ag'] = $this->getActivityGoodsList($whereAg, 0, $agField);
            }
        }
        return $ac;
    }

    /**
     * 使用优惠券码获取优惠券信息
     * @return mixed
     */
    public function getAcByCode()
    {
        $acUsed = [];
        $where['code'] = $this->data['coupon_code'];
        $fieldAc = "c_id,code,c_type,pay_limit,reduction,status,start_date,end_date,used_limit,pay_limit,designated_machine,designated_goods,exclusion,creator";
        $ac = $this->getActivityCouponFind(['code' => $this->data['coupon_code']], $fieldAc,'c_id desc');
        // 查无固定码优惠券活动
        if (!$ac) {
            $where['code_type'] = 1;
            $field = "cu_id,c_id,code,c_type,pay_limit,reduction,status";
            $acUsed = $this->getActivityCouponUsedFind($where, $field,'cu_id desc');
            // 查无随机码生成记录
            if (!$acUsed) {
                return $this->lang("VActivityCoupon.check_no_code");
            }
            $acUsed = $acUsed->toArray();
            // 有随机码优惠券活动，重新查优惠券活动信息
            $ac = $this->getActivityCouponFind(['c_id' => $acUsed['c_id']], $fieldAc);
        }
        if ($ac) {
            $ac = $ac->toArray();
            // 查询优惠券活动创建人绑定的设备ID列表，检查当前设备是否在绑定列表中，不是的话不允许使用
            $mIds = $this->getAuthManagerMachineColumn(['manager_id' => $ac['creator']],'m_id');
            if (!in_array($this->machine['m_id'],$mIds))
                return $this->lang("VActivityCoupon.no_am_data");

            $ac['coupon_code'] = $this->data['coupon_code'];
            // 随机码的优惠券，检查使用状态
            if ($acUsed) {
                // 已使用
                if ($acUsed['status'] == 2) return $this->lang("VActivityCoupon.status2");
                // 已过期
                if ($acUsed['status'] == 3) return $this->lang("VActivityCoupon.status3");
                // 已作废
                if ($acUsed['status'] == 4) return $this->lang("VActivityCoupon.status4");
            }
            // 不是随机码的，有使用次数上限的
            if ($ac['code'] && $ac['used_limit'] > 0) {
                $whereCount['c_id'] = $ac['c_id'];
                $whereCount['status'] = 2;
                $usedNum = $this->getActivityCouponUsedCount($whereCount);
                // 已使用次数等于或超过上限设置的
                if ($ac['used_limit'] <= $usedNum) {
                    return $this->lang("VActivityCoupon.used_limit");
                }
            }
            // 开始时间大于当前时间，优惠券活动还未开始的
            if ($ac["start_date"] > time()) {
                return $this->lang("VActivityCoupon.not_begin");
            }
            // 有设置结束时间，并且结束时间小于当前时间，活动已结束
            if ($ac["end_date"] > 0 && $ac['end_date'] < time()) {
                // 修改优惠券活动为3.已过期
                $this->updateActivityCoupon(['c_id' => $ac['c_id'], 'status' => 3]);
                // 修改随机码优惠券使用记录为3.已过期
                if (!$ac['code']) $this->updateActivityCouponUsed(['status' => 3], ['c_id' => $ac['c_id'], 'status' => 1]);
                return $this->lang("VActivityCoupon.finished");
            }
            // 优惠券状态由1.未开始修改为2.进行中
            if ($ac['status'] == 1) $this->updateActivityCoupon(['status' => 2], ['c_id' => $ac['c_id']]);

            // 有指定设备，查无关联设备
            if ($ac['designated_machine'] && $ac['designated_machine'] == 2) {
                $am = $this->getActivityMachineFind(['a_id' => $ac['c_id'],'a_type' => 1, 'm_id' => $this->machine['m_id']]);
                if (!$am) return $this->lang("VActivityCoupon.no_am_data");
            }
            // 有指定商品且不是全部商品，查询指定商品列表
            if ($ac['designated_goods'] > 1) {

                $gIds = $this->getMachineChannelColumn(['m_id' => $this->machine['m_id'],'status' => 1],'g_id');
                $whereAg['a_id'] = $ac['c_id'];
                $whereAg['a_type'] = 1;
                if ($gIds) $whereAg[] = ['g_id','in',$gIds];
                $ag = $this->getActivityGoodsList(['a_id' => $ac['c_id'], 'a_type' => 1], 0,
                    'g_id,g_name,pic,sku,market_price,retail_price,gc_id,gc_name'
                );
                if (!$ag) return $this->r(100,$this->lang("VActivityCoupon.no_ag_data"));
                if ($ag) $ac['ag'] = $ag->toArray();
            }
            $ac['acUsed'] = $acUsed;
            return $ac;
        }
        return $this->lang('VActivityCoupon.ac_not_data');
    }

    /**
     * 订单使用优惠券
     * 优惠券适用商品处理规则：
     * 后台创建优惠券活动时，可设置适用商品，适用商品的设置有三种模式，1.全部商品，2.指定商品，3.部分商品除外（勾选全部商品，选择了商品生成列表，不包含在列表中的享受优惠券作用）
     * 当适用商品为1.全部商品时，以订单总金额去计算优惠金额与打折扣
     * 当适用商品为2.指定商品时，以指定商品单价计算优惠，即：（单价 - 优惠值）* 购买数量 = 商品总金额。购物车内不在指定商品范围内的不享受优惠。
     * 当适用商品为3.部分商品除外时，指定商品列表内的商品不享受优惠，其他的商品以单价计算优惠。
     * 优惠计算方式，全部商品时直接从订单总金额优惠
     *      一、使用指定商品立减的优惠券，购买多个商品时，终端显示改为（价格-优惠额）*数量
            二、使用指定商品折扣的优惠券，够买享受优惠的商品+不享受优惠的商品，后台改为优惠商品总价*折扣+不优惠商品总价
     * @return bool|string
     */
    public function orderUseCoupon()
    {
        // 通过券码获取优惠券活动信息，判断使用条件
        $ac = $this->getAcByCode();
        if (is_string($ac)) {
            return $this->rFail($ac);
        }
        if ($ac instanceof Json) {
            return $ac;
        }

        if ($this->order['coupon_id'] > 0) {
            return $this->rFail($this->lang("VActivityCoupon.used_coupon"));
        }

        // 互斥活动
        if ($this->order['order_type'] > 2) {
            if ($ac['exclusion'] == 1)
                return $this->rFail($this->lang("VActivityCoupon.exclusion"));
            else {
                if ($this->order['fd_id'] > 0 && $this->getActivityFdValue(['fd_id' => $this->order['fd_id']],'exclusion') == 1) {
                    return $this->rFail($this->lang("VActivityFd.exclusion"));
                }
            }
        }

        // 有订单最低消费金额设置，订单交易金额小于最低消费金额
        if ($ac['pay_limit'] > 0 && $this->order['total_price'] < $ac['pay_limit']) {
            return $this->rFail($this->lang("VActivityCoupon.pay_limit"));
        }
        // 原始订单支付金额
        $original_price = $this->order['total_price'];

        if (!isset($this->order['details'])) {
            $sodField = "sod_id,discount_price,total_sod_price,retail_price,quantity,g_id";
            $this->order['details'] = $this->getSaleOrdersDetailsList(['order_id' => $this->order['order_id']],0,$sodField,'total_sod_price asc')->toArray();
        }

        try {
            $this->startTrans();// 区分适用商品规则
            // 1. 全部商品，以订单总金额计算优惠金额与打折扣
            if ($ac['designated_goods'] == 1) {
                $discount_price = 0;
                // 区分优惠券类型，1：立减金额，2：优惠折扣，计算优惠值
                if ($ac['c_type'] == 1) $discount_price = $ac['reduction'];
                if ($ac['c_type'] == 2) $discount_price = bcmul($this->order['total_price'], bcdiv(bcsub(100,$ac['reduction']), 100, 2), 3);
                if ($discount_price <= $this->order['total_price']) {
//                    $totalPrice = $this->order['total_price'];
                    // 优惠金额作用至订单总金额
                    $this->order['discount_price'] = bcadd($this->order['discount_price'], $discount_price, 2);
                    $this->order['total_price'] = bcsub($this->order['total_price'], $discount_price, 3);
                    actionLog($this->order,'优惠券订单数据');

                    foreach ($this->order['details'] as $key => $value) {
                        // 商品优惠金额 = 订单优惠金额 * 商品金额占比 = 订单优惠金额 *  （商品总金额 / 订单总金额）
//                        $sodDiscountPrice = bcmul($this->order['discount_price'],bcdiv($value['total_sod_price'],$totalPrice,2),4);

                        $sodDiscountPrice = 0;
                        if ($ac['c_type'] == 2) {
                            // 商品优惠金额 = 商品售价 * 数量 * （100 - 打折）
                            $reduction = bcdiv(bcsub(100,$ac['reduction']),100,2);
                            $sodDiscountPrice = bcmul($value['total_sod_price'],$reduction,4);
                        }
                        // 商品优惠金额 = 商品总额 / 订单总额 * 立减金额
                        if ($ac['c_type'] == 1 ) {
                            $sodDiscountPrice = bcmul(bcdiv($value['total_sod_price'],$original_price,4),$ac['reduction'],4);
                        }
                        // 最后一个优惠金额，并且优惠金额大于计算后的商品详情优惠金额，则将剩下的优惠金额都给这个商品
                        if (!isset($this->order['details'][$key + 1]) && $discount_price > $sodDiscountPrice)
                            $sodDiscountPrice = $discount_price;
                        actionLog($value,'优惠计算前商品数据');
                        actionLog($sodDiscountPrice,'商品优惠金额');
                        if ($sodDiscountPrice < 0.01 && $key > 0) continue;
                        $discount_price = bcsub($discount_price,$sodDiscountPrice,4);
                        $value['discount_price'] = $sodDiscountPrice;
                        $value['total_sod_price'] = bcsub($value['total_sod_price'], $sodDiscountPrice, 4);
                        actionLog($value,'优惠后商品数据');
                        $this->updateSaleOrdersDetails(['sod_id' => $value['sod_id'], 'discount_price' => $value['discount_price'], 'total_sod_price' => $value['total_sod_price']]);
                    }
                }
            } else {
                $acg_id = array_column($ac['ag'], 'g_id');
                foreach ($this->order['details'] as $key => $value) {
                    // 2. 指定商品，商品在指定范围内。  3.部分商品除外，商品在指定范围外
                    if (($ac['designated_goods'] == 2 && in_array($value['g_id'], $acg_id)) ||
                        ($ac['designated_goods'] == 3 && !in_array($value['g_id'], $acg_id))) {
                        // 区分优惠券类型，1：立减金额，2：优惠折扣，计算优惠值
                        if ($ac['c_type'] == 1) $discount_price = $ac['reduction'];
                        if ($ac['c_type'] == 2) $discount_price = bcmul($value['retail_price'], bcdiv(bcsub(100,$ac['reduction']), 100, 2), 3);
                        if ($discount_price < 0.01) continue;
                        if ($value['total_sod_price'] > $discount_price) {
                            $value['discount_price'] = bcadd($value['discount_price'], $discount_price, 4);
                            $totalDiscountPrice = bcmul($discount_price, $value['quantity'], 3);
                            $value['total_sod_price'] = bcsub($value['total_sod_price'], $totalDiscountPrice, 4);
                            $this->updateSaleOrdersDetails(['sod_id' => $value['sod_id'], 'discount_price' => $discount_price, 'total_sod_price' => $value['total_sod_price']]);
                            actionLog($this->getLS(), '优惠券使用，减去商品总价');
                            actionLog($value, '处理后的数据');
                            $this->order['discount_price'] = bcadd($this->order['discount_price'], $totalDiscountPrice, 3);
                            $this->order['total_price'] = bcsub($this->order['total_price'], $totalDiscountPrice, 3);
                        }
                    }
                }
            }
            if ($original_price != $this->order['total_price']) {
                $updateOrder = [
                    'order_id' => $this->order['order_id'],
                    'discount_price' => $this->order['discount_price'],
                    'order_type' => $this->order['order_type'] == 1 ? 2:6,
                    'coupon_id' => $ac['c_id'],
                    'total_price' => $this->order['total_price']
                ];
                if (!$this->order['retail_price']) $updateOrder['retail_price'] = $original_price;
                // 修改订单金额，绑定优惠券使用记录
                $this->updateSaleOrders($updateOrder);
                actionLog($this->getLS(), '修改订单优惠数据');
                $used = [
                    "order_id" => $this->order['order_id'],
                    "trade_no" => $this->order['trade_no'],
                    "m_id" => $this->machine['m_id'],
                    "machine_id" => $this->machine['machine_id'],
                    "machine_name" => $this->machine['machine_name'],
                    "original_price" => $original_price,
                    "discount_price" => $this->order['discount_price'],
                    "retail_price" => $this->order['total_price'],
                ];
                // 修改优惠券绑定订单ID与订单编号
                if ($ac['acUsed']) {
                    $used['cu_id'] = $ac['acUsed']['cu_id'];
                    $this->updateActivityCouponUsed($used);
                    actionLog($this->getLS(), '修改使用记录，绑定订单');
                } else {
                    $used['c_id'] = $ac['c_id'];
                    $used['c_type'] = $ac['c_type'];
                    $used['pay_limit'] = $ac['pay_limit'];
                    $used['reduction'] = $ac['reduction'];
                    $used['code'] = $ac['coupon_code'];
                    $used['code_type'] = 2;
                    $this->addActivityCouponUsed($used);
                    actionLog($this->getLS(), '新增待使用记录，绑定订单');
                }
                $revenueCouponResult = $this->bindRevenueCouponAfterOrderUseCoupon($ac, bcsub($original_price, $this->order['total_price'], 2));
                if ($revenueCouponResult !== true) {
                    $this->rollbackTrans();
                    return $revenueCouponResult;
                }
            }
            $this->commitTrans();
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e,1);
            return $e->getMessage();
        }
        return true;
    }

    protected function bindRevenueCouponAfterOrderUseCoupon(array $activityCoupon, $discountAmount)
    {
        $couponCode = trim(strval($activityCoupon['coupon_code'] ?? ''));
        if ($couponCode === '') {
            return true;
        }

        $coupon = RevenueCouponService::findEnabledCouponByCode($couponCode);
        if (!$coupon) {
            return true;
        }
        if (!is_array($coupon)) $coupon = $coupon->toArray();

        $usedCode = trim(strval($this->order['revenue_coupon_code'] ?? ''));
        if ($usedCode !== '' && $usedCode !== $couponCode) {
            return $this->rFail("订单已使用其他分账优惠券");
        }
        if ($usedCode === $couponCode) {
            return true;
        }

        $this->order['details'] = $this->getOrderDetailsForRevenueCouponScope();
        $matched = RevenueCouponService::matchScope($coupon, $this->getRevenueCouponOrderArray(), $this->order['details']);
        if (!$matched['matched']) {
            return $this->rFail("优惠券分账配置不适用于当前订单");
        }

        $discountAmount = bcadd(strval($discountAmount), '0', 2);
        if (bccomp($discountAmount, '0.01', 2) < 0) {
            return true;
        }

        $updateOrder = [
            'order_id' => $this->order['order_id'],
            'revenue_coupon_code' => $couponCode,
            'revenue_coupon_discount_type' => intval($coupon['discount_type'] ?? 0),
            'revenue_coupon_discount_value' => bcadd(strval($coupon['discount_value'] ?? 0), '0', 3),
            'revenue_coupon_discount_amount' => $discountAmount,
        ];
        $this->updateSaleOrders($updateOrder, [], array_keys($updateOrder));
        $this->order['revenue_coupon_code'] = $couponCode;
        $this->order['revenue_coupon_discount_type'] = $updateOrder['revenue_coupon_discount_type'];
        $this->order['revenue_coupon_discount_value'] = $updateOrder['revenue_coupon_discount_value'];
        $this->order['revenue_coupon_discount_amount'] = $updateOrder['revenue_coupon_discount_amount'];

        $refreshResult = $this->refreshPendingRevenueAfterRevenueCoupon();
        if ($refreshResult !== true) {
            return $refreshResult;
        }
        return true;
    }

    protected function getRevenueCouponOrderArray()
    {
        if (is_object($this->order) && method_exists($this->order, 'toArray')) {
            return $this->order->toArray();
        }
        return is_array($this->order) ? $this->order : [];
    }

    protected function refreshPendingRevenueAfterRevenueCoupon()
    {
        $order = $this->getRevenueCouponOrderArray();
        if (empty($order['order_id']) || floatval($order['total_price'] ?? 0) <= 0 || intval($order['pay_channel'] ?? 0) <= 0) {
            return true;
        }
        $pendingCount = RevenueOrderModel::where(['order_id' => intval($order['order_id']), 'status' => 0])->count();
        if (!$pendingCount) {
            return true;
        }
        $this->order['details'] = $this->getOrderDetailsForRevenueCouponScope();
        $result = $this->countIncome();
        if (!$result) {
            return $this->rFail($this->revenueError ?: "重新生成分账订单失败");
        }
        return true;
    }

    protected function getOrderDetailsForRevenueCouponScope()
    {
        if (isset($this->order['details']) && $this->order['details']) {
            $details = $this->order['details'];
            if (!is_array($details)) $details = $details->toArray();
            return $details;
        }
        $details = $this->getSaleOrdersDetailsList(['order_id' => $this->order['order_id']], 0, 'sod_id,g_id,mg_id,total_sod_price,quantity');
        if (!$details) return [];
        return is_array($details) ? $details : $details->toArray();
    }
}
