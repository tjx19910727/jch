<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/6
 * Time: 11:30
 */

namespace app\AppFactory\Kernel\Traits\Activity;


use app\AppFactory\Kernel\Model\Activity\Fd\ActivityFdModel;

trait ActivityFdTrait
{
    public function getActivityFdValue($where,$value)
    {
        return ActivityFdModel::getFieldValue($where,$value);
    }
    
    public function getActivityFdFind($where,$field = "*",$order = "")
    {
        return ActivityFdModel::getFind($where,$field,$order);
    }

    public function getActivityFdList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "")
    {
        return ActivityFdModel::getList($where,$pageNum,$field,$order,$eachFun);
    }

    public function getActivityFdListByMachine($where,$field = "*",$order = "")
    {
        return ActivityFdModel::getListByMachine($where,$field,$order);
    }

    public function addActivityFd($insert)
    {
        !isset($this->manager['manager_id']) ?: $insert['creator'] = $this->manager['manager_id'];
        $data = ActivityFdModel::create($insert);
        return $data->fd_id;
    }

    public function updateActivityFd($update,$where = [],$field = [])
    {
        !isset($this->manager['manager_id']) ?: $update['update_id'] = $this->manager['manager_id'];
        return ActivityFdModel::update($update,$where,$field);
    }

    public function delActivityFd($where)
    {
        $fd = $this->getActivityFdFind($where,'fd_id');
        $result = ActivityFdModel::whereDel($where);
        if ($result) {
            $this->delActivityFdContent($where);
            $this->delActivityMachine(['a_id' => $fd['fd_id'], 'a_type' => 2]);
        }
        return $result;
    }

    public function getActivityFdByMachine()
    {
        $where = 'am.m_id = ' . $this->machine['m_id'] . " AND status < 3 AND start_date < " . time();
        $fdList = $this->getActivityFdListByMachine($where,'fd_id,fd_name,start_date,end_date,fd_type,condition_type,desc,status');
        if ($fdList) {
            $fdList = $fdList->toArray();
            foreach ($fdList as $key => $fdl){
                $update = [];
                $fieldOrder = "fdc_sort ASC, fdc_id desc";
                $field = "condition_value,g_id,g_name,pic,sku,gc_id,gc_name,active_value";
                // 20250320，与朱工、陈工、聂工讨论确认最低消费金额、最低消费件数排序规则，优先排序值顺序排序，排序值一致时，以条件数值倒序排序
                if (in_array($fdl['condition_type'],[1,2])) {
                    // 20250414，终端是以最后一条满足条件覆盖前一满足条件，所以排序得反向排序
                    $fieldOrder = "fdc_sort DESC, condition_value1 asc, fdc_id asc";
                    $field = "fdc_id,CAST(condition_value AS UNSIGNED) condition_value1, condition_value,g_id,g_name,pic,sku,gc_id,gc_name,active_value,fdc_sort";
                }
                $fdl['content'] = $this->getActivityFdContentList(['fd_id' => $fdl['fd_id'], 'goods_source' => 1],0,$field,$fieldOrder);
                if ($fdl['status'] == 1) $update['status'] = 2;
                if ($fdl['end_date'] > 0 && $fdl['end_date'] < strtotime(date("Y-m-d")) && $fdl['status'] != 3) {
                    $update['status'] = 3;
                    $fdl['status'] = 3;
                }
                if ($update) $this->updateActivityFd($update,['fd_id' => $fdl['fd_id']]);
                $fdList[$key] = $fdl;
            }
        }
        return $fdList;
    }

    private $fd;
    private $content;
    private $lastContent;
    private $skuMatchedContents = [];
    private $countContent = ["discount_price" => 0, "mc_id" => 0];

    /**
     * 订单使用满减满送
     * @return mixed
     */
    protected function orderUseFd()
    {
        $this->order = $this->getSaleOrdersFind(['order_id' => $this->data['order_id']]);
        if (!$this->order) return $this->rFail($this->lang("VActivityFd.order_no_data"));
        $this->order = $this->order->toArray();
        if ($this->order['fd_id'] == $this->data['fd_id']) {
            return $this->r(100,'VActivityFd.fd_used');
        }
        actionLog($this->order,'订单信息');
        $this->fd = $this->getActivityFdFind(['fd_id' => $this->data['fd_id']]);
        if (!$this->fd) return $this->rFail($this->lang("VActivityFd.fd_no_data"));
        $this->fd = $this->fd->toArray();
        $am = $this->getActivityMachineFind(['a_id' => $this->fd['fd_id'],'a_type' => 2, 'm_id' => $this->machine['m_id']]);
        if (!$am) return $this->r(100,$this->lang("VActivityFd.no_am_data"));


        actionLog($this->fd,'活动信息');
        // 线上商品适用范围独立校验，不参与 condition_type 核心商品规则。
        $onlineDetails = $this->getSaleOrdersDetailsList([
            'order_id' => $this->order['order_id'],
            ['wc_order_no', '<>', ''],
        ], 0, 'sod_id,wc_order_no');
        if ($onlineDetails) {
            $onlineContents = $this->getActivityFdContentList([
                'fd_id' => $this->fd['fd_id'],
                'goods_source' => 2,
            ], 0, 'fdc_id,source_no');
            if (!$this->fdOnlineGoodsMatch($onlineDetails, $onlineContents)) {
                return $this->r(100, '线上商品不适用当前满减活动');
            }
        }
        if ($this->order['order_type'] > 1 && $this->order['order_type'] != 5) {
            if ($this->fd['exclusion'] == 1)
                return $this->rFail($this->lang("VActivityFd.exclusion"));
            else {
                if ($this->order['coupon_id'] > 0 && $this->getActivityCouponValue(['c_id' => $this->order['coupon_id']],'exclusion') == 1) {
                    return $this->rFail($this->lang("VActivityCoupon.exclusion"));
                }
            }
        }
        $fieldOrder = "fdc_sort ASC, fdc_id desc";
        $field = "fdc_id,fd_id,fd_name,condition_value,g_id,g_name,pic,sku,gc_id,gc_name,active_value,fdc_sort";
        // 20250320，与朱工、陈工、聂工讨论确认最低消费金额、最低消费件数排序规则，优先排序值顺序排序，排序值一致时，以条件数值倒序排序
        if (in_array($this->fd['condition_type'],[1,2])) {
            $fieldOrder = "fdc_sort ASC,condition_value1 desc, fdc_id desc";
            $field = "fdc_id,fd_id,fd_name, CAST(condition_value AS UNSIGNED) condition_value1,condition_value,g_id,g_name,pic,sku,gc_id,gc_name,active_value,fdc_sort";
        }
        $this->content = $this->getActivityFdContentList(['fd_id' => $this->fd['fd_id'], 'goods_source' => 1],0,$field,$fieldOrder);
        if (!$this->content) return $this->rFail($this->lang("VActivityFd.content_no_data"));
        if (is_string($this->content)) return $this->rFail($this->content);
        actionLog($this->getLS(),'【SQL】查询活动规则');
        $this->content = $this->content->toArray();
        $this->skuMatchedContents = [];

        $this->startTrans();
        try {
            actionLog($this->content,'活动规则内容');
            // 最低消费金额
            if ($this->fd['condition_type'] == 1) {
                $this->lowestPayMoney();
            }
            // 最少消费件数
            if ($this->fd['condition_type'] == 2) {
                $this->lowestBuyNum();
            }
            // 指定SKU
            if ($this->fd['condition_type'] == 3) {
                //$this->designatedSku
                $this->designatedSkuV2();
            }
            // 不限额
            if ($this->fd['condition_type'] == 0) {
                $this->unlimited();
            }
            $flag[] = $this->handleActivityFd();
            if ($this->fd['condition_type'] == 3 && $this->skuMatchedContents) {
                foreach ($this->skuMatchedContents as $matchedContent) {
                    // 指定SKU支持多规则命中，按命中规则逐条落库
                    $insertUsed = [
                        "fd_id" => $this->fd['fd_id'],
                        "fd_name" => $this->fd['fd_name'],
                        "order_id" => $this->order['order_id'],
                        "trade_no" => $this->order['trade_no'],
                        "m_id" => $this->order['m_id'],
                        "machine_id" => $this->order['machine_id'],
                        "machine_name" => $this->order['machine_name'],
                        "fd_type" => $this->fd['fd_type'],
                        "condition_type" => $this->fd['condition_type'],
                        "fdc_id" => $matchedContent['fdc_id'] ?? 0,
                        "condition_value" => $matchedContent['condition_value'] ?? '',
                        "active_value" => $matchedContent['active_value'] ?? 0,
                        "g_id" => $matchedContent['g_id'] ?? 0,
                        "g_name" => $matchedContent['g_name'] ?? '',
                        "sku" => $matchedContent['sku'] ?? '',
                        "pic" => $matchedContent['pic'] ?? '',
                    ];
                    $flag[] = $this->addActivityFdUsed($insertUsed);
                    actionLog($this->getLS(), '【SQL】添加活动使用记录');
                }
            } elseif ($this->lastContent) {
                // 非指定SKU维持原有单条活动使用记录逻辑
                $insertUsed = [
                    "fd_id" => $this->fd['fd_id'],
                    "fd_name" => $this->fd['fd_name'],
                    "order_id" => $this->order['order_id'],
                    "trade_no" => $this->order['trade_no'],
                    "m_id" => $this->order['m_id'],
                    "machine_id" => $this->order['machine_id'],
                    "machine_name" => $this->order['machine_name'],
                    "fd_type" => $this->fd['fd_type'],
                    "condition_type" => $this->fd['condition_type'],
                    "fdc_id" => $this->lastContent['fdc_id'],
                    "condition_value" => $this->lastContent['condition_value'],
                    "active_value" => $this->lastContent['active_value'],
                    "g_id" => $this->lastContent['g_id'],
                    "g_name" => $this->lastContent['g_name'],
                    "sku" => $this->lastContent['sku'],
                    "pic" => $this->lastContent['pic'],
                ];
                $flag[] = $this->addActivityFdUsed($insertUsed);
                actionLog($this->getLS(), '【SQL】添加活动使用记录');
            }
            actionLog($flag, '处理结果flag');
            $check = $this->checkFlag($flag);
            actionLog($check, '处理结果');
            if ($check) {
                $this->commitTrans();
                $data['order'] = $this->getSaleOrdersFind(['order_id' => $this->order['order_id']], 'order_id,trade_no,order_type,total_price,discount_price,total_quantity');
                $data['order']['details'] = $this->getSaleOrdersDetailsList(['order_id' => $this->order['order_id']], 0,
                    'sod_id,mc_id,shelf_way,channel_position,channel_code,mg_id,g_name,pic,sku,gc_name,total_sod_price,discount_price,quantity,is_gift');
                $data['fdUsed'] = $this->getActivityFdUsedList(['order_id' => $this->order['order_id']], 0,
                    'fdu_id,fd_name,fd_type,condition_type,condition_value,active_value,g_id,g_name,pic');
                return $this->r(200, $this->lang("action_success"), $data);
            }
            $this->rollbackTrans();
            return $this->rFail();
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e,1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 最低消费金额
     */
    private function lowestPayMoney()
    {
        foreach ($this->content as $key => $value) {
            // 满足条件，执行逻辑，不满足就跳出
            if ($this->order['total_price'] >= $value['condition_value']) {
                $this->countContent($value);
                $this->lastContent = $value;
                break;
            }
        }
    }

    /**
     * 最少消费件数条件循环
     */
    private function lowestBuyNum()
    {
        foreach ($this->content as $key => $value) {
            // 满足条件，执行逻辑，不满足就跳出
            if ($this->order['total_quantity'] >= $value['condition_value']) {
                $this->countContent($value);
                $this->lastContent = $value;
                break;
            }
        }
    }

    /**
     * @var 指定SKU数据
     */
    protected $sku;

    /**
     * 指定SKU条件循环
     */
    private function designatedSku()
    {
        foreach ($this->content as $key => $value) {
            $detailsList = $this->getSaleOrdersDetailsList(['order_id' => $this->order['order_id'],'sku' => $value['condition_value']],
                0,'sod_id,total_sod_price,discount_price,quantity');
            actionLog($this->getLS(),'查询指定SKU');
            if ($detailsList) {
                $detailsList = $detailsList->toArray();
                foreach ($detailsList as $dk => $dv) {
                    $this->sku = $dv;
                    // 满足条件，执行逻辑，不满足就跳出
                    $this->countContent($value);
                    $this->lastContent = $value;
                }
                continue;
            }
            break;
        }
    }

    /**
     * 指定SKU条件循环
     */
    private function designatedSkuV2()
    {
        // 同一个活动规则里可能出现重复SKU，按当前排序优先级只处理第一条
        $handledSku = [];
        foreach ($this->content as $key => $value) {
            $ruleSku = (string)$value['condition_value'];
            if (isset($handledSku[$ruleSku])) {
                continue;
            }
            $handledSku[$ruleSku] = 1;

            $detailsList = $this->getSaleOrdersDetailsList(['order_id' => $this->order['order_id'],'sku' => $value['condition_value']],
                0,'sod_id,total_sod_price,discount_price,quantity');
            actionLog($this->getLS(),'查询指定SKU');
            if ($detailsList) {
                $detailsList = $detailsList->toArray();
                foreach ($detailsList as $dk => $dv) {
                    $this->sku = $dv;
                    // 满足条件，执行逻辑，不满足就跳出
                    $this->countContent($value);
                    $this->lastContent = $value;
                    if (isset($value['fdc_id'])) {
                        $this->skuMatchedContents[$value['fdc_id']] = $value;
                    } else {
                        $this->skuMatchedContents[] = $value;
                    }
                }
            }
        }
    }

    protected function fdDetailMatchesOnlineGoods($detail, $sourceNo)
    {
        $wcOrderNo = json_decode($detail['wc_order_no'] ?? '', true);
        if (!is_array($wcOrderNo)) return false;
        foreach ($wcOrderNo as $wcGoods) {
            if (is_array($wcGoods) && trim(strval($wcGoods['out_no'] ?? '')) === trim(strval($sourceNo))) return true;
        }
        return false;
    }

    /** 判断订单中的微程商品是否命中活动配置的线上商品范围。 */
    protected function fdOnlineGoodsMatch($details, $onlineContents)
    {
        $details = is_object($details) && method_exists($details, 'toArray') ? $details->toArray() : (array)$details;
        $onlineContents = is_object($onlineContents) && method_exists($onlineContents, 'toArray') ? $onlineContents->toArray() : (array)$onlineContents;
        if (!$details || !$onlineContents) return false;
        foreach ($details as $detail) {
            $matched = false;
            foreach ($onlineContents as $content) {
                if ($this->fdDetailMatchesOnlineGoods($detail, isset($content['source_no']) ? $content['source_no'] : '')) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) return false;
        }
        return true;
    }

    /**
     * 不限额循环，拿最后一条
     */
    private function unlimited()
    {
        foreach ($this->content as $key => $value) {
            if ($this->fd['fd_type'] != 3) break;
            $this->countContent($value);
            $this->lastContent = $value;
            break;
        }
    }

    /**
     * 符合条件，计算处理活动内容
     * @param $value
     */
    private function countContent($value)
    {
        $discount_price = 0;
        $mc_id = 0;
        // 赠品
        if ($this->fd['fd_type'] == 1) {
            $sod_quantity = $this->getSaleOrdersDetailsSum(['order_id' => $this->order['order_id'],'g_id' => $value['g_id']],'quantity');
            $mc_id = $this->getMachineChannelValue(['m_id' => $this->order['m_id'],'status' => 1,['stock',">",($sod_quantity + 1)],'g_id' => $value['g_id']],'mc_id');
            if (!$mc_id) $mc_id = 0;
        }
        // 立减
        if ($this->fd['fd_type'] == 2) {
            if ($this->fd['condition_type'] != 3) {
                $discount_price = $value['active_value'];
            } else {
                $discount_price = $this->clampFdDiscount($value['active_value'] * $this->sku['quantity'], $this->sku['total_sod_price']);
                actionLog($discount_price,'指定SKU优惠立减金额');
                if (bccomp($discount_price, '0', 4) > 0) {
                    // 修改订单详情商品总价
                    $this->updateSaleOrdersDetails([
                        'sod_id' => $this->sku['sod_id'],
                        'total_sod_price' => $this->subtractFdDiscount($this->sku['total_sod_price'], $discount_price),
                        'discount_price' => bcadd(strval($this->sku['discount_price'] ?? 0), $discount_price, 4)]);
                    actionLog($this->getLS(),'【SQL】指定SKU立减');
                }
            }
        }
        // 惊喜礼品
        if ($this->fd['fd_type'] == 3) {
            $random = mt_rand(1,100);
            if ($value['active_value'] >= $random) {
                $sod_quantity = $this->getSaleOrdersDetailsSum(['order_id' => $this->order['order_id'],'g_id' => $value['g_id']],'quantity');
                $mc_id = $this->getMachineChannelValue(['m_id' => $this->order['m_id'],'status' => 1,['stock',">=",($sod_quantity + 1)],'g_id' => $value['g_id']],'mc_id');
                actionLog($this->getLS(),'【SQL】查询惊喜礼品货架');
                if (!$mc_id) $mc_id = 0;
            }
        }
        // 折扣
        if ($this->fd['fd_type'] == 4) {
            // 非指定SKU的直接用订单总额计算
            if ($this->fd['condition_type'] != 3) {
                $discount_price = round(bcmul($this->order['total_price'], bcdiv(bcsub(100,$value['active_value']), 100, 2), 3),2);
            } else {
                // 指定SKU，优惠金额以商品单价计算
                $discount_price = round(bcmul(
                    $this->sku['total_sod_price'],
                    bcdiv(
                        bcsub(100,$value['active_value']),
                        100,
                        2),
                    3),2);
                actionLog($discount_price,'指定SKU优惠折扣金额');
                $discount_price = $this->clampFdDiscount($discount_price, $this->sku['total_sod_price']);
                if (bccomp($discount_price, '0', 4) > 0) {
                    // 修改订单详情商品总价
                    $this->updateSaleOrdersDetails([
                        'sod_id' => $this->sku['sod_id'],
                        'total_sod_price' => $this->subtractFdDiscount($this->sku['total_sod_price'], $discount_price),
                        'discount_price' => bcadd(strval($this->sku['discount_price'] ?? 0), $discount_price, 4)]);
                    actionLog($this->getLS(),'【SQL】指定SKU折扣');
                }
            }
        }
        $discount_price = $this->clampFdDiscount($discount_price, $this->fd['condition_type'] == 3 ? ($this->sku['total_sod_price'] ?? 0) : $this->order['total_price']);
        if (bccomp($discount_price, '0', 4) > 0) $this->countContent['discount_price'] = bcadd($this->countContent['discount_price'],$discount_price,2);
        if ($mc_id) $this->countContent['mc_id'] = $mc_id;
    }

    /**
     * 计算完活动结果，处理订单与订单详情
     * @return mixed
     */
    private function handleActivityFd()
    {
        $flag[] = 1;
        $updateOrder = [];
        actionLog($this->countContent,'过滤后的最终优惠');
        if ($this->countContent['discount_price']) {
            $currentDiscount = $this->clampFdDiscount($this->countContent['discount_price'], $this->order['total_price']);
            if (bccomp($currentDiscount, '0', 4) <= 0) return true;
            if (!$this->order['retail_price']) $updateOrder['retail_price'] = $this->order['total_price'];
            $updateOrder['discount_price'] = bcadd($this->order['discount_price'], $currentDiscount,2);
            $updateOrder['total_price'] = $this->subtractFdDiscount($this->order['total_price'], $currentDiscount);
            actionLog($this->order,'订单数据');
            $details = $this->getSaleOrdersDetailsList(['order_id' => $this->order['order_id']]);
            if (!$details) return $this->lang("VActivityFd.sod_no_data");
            $details = $details->toArray();
            $remainDiscount = $currentDiscount;
            $eligibleDetails = array_values(array_filter($details, function ($detail) {
                return intval($detail['is_gift'] ?? 2) === 2;
            }));
            $num = count($eligibleDetails);
            foreach ($eligibleDetails as $dk => $dv) {
                actionLog($dv, '商品数据');
                if ($this->fd['condition_type'] != 3) {
                    // 商品优惠金额 = 订单优惠金额 * 商品金额占比 = 订单优惠金额 *  （商品总金额 / 订单总金额）
                    $sodDiscountPrice = round(bcmul($currentDiscount, bcdiv($dv['total_sod_price'], $this->order['total_price'], 5), 3),2);
                    actionLog($sodDiscountPrice, '商品优惠金额');
                    if ($sodDiscountPrice < 0.01) $sodDiscountPrice = 0;
                    if ($num - 1 == $dk && $sodDiscountPrice < $remainDiscount) {
                        $sodDiscountPrice = $remainDiscount;
                        actionLog($sodDiscountPrice,'当前商品为最后一个商品，并且计算的优惠金额小于剩余优惠金额，重置为剩下的优惠金额');
                    }
                    $sodDiscountPrice = $this->clampFdDiscount($sodDiscountPrice, $dv['total_sod_price']);
                    $sodDiscountPrice = $this->clampFdDiscount($sodDiscountPrice, $remainDiscount);
                    if ($sodDiscountPrice < $remainDiscount) {
                        $remainDiscount -= $sodDiscountPrice;
                        actionLog($remainDiscount,"剩下的优惠金额，减去 {$dk} 的优惠金额 {$sodDiscountPrice}");
                    }

                    $updateSod['sod_id'] = $dv['sod_id'];
                    $updateSod['discount_price'] = bcadd($dv['discount_price'], $sodDiscountPrice, 2);
                    $updateSod['total_sod_price'] = $this->subtractFdDiscount($dv['total_sod_price'], $sodDiscountPrice);
                    actionLog($dv, '修改商品优惠数据');
                    $flag[] = $this->updateSaleOrdersDetails($updateSod);
                    actionLog($this->getLS(), '【SQL】处理订单详情信息');
                }
            }
        }
        if ($this->countContent['mc_id']) {
            $mc = $this->getMachineChannelFind(['mc_id' => $this->countContent['mc_id']],
                'mc_id,channel_code,mg_id,g_id,g_name,gc_id,gc_name,pic,sku,cost_price,market_price, retail_price,batch_number,manufacture_time,sell_by_date,bar_code,shelf_way');
            if ($mc) {
                $mc = $mc->toArray();
                $insertSod = $mc;
                $insertSod['is_gift'] = 1;
                $insertSod['quantity'] = 1;
                $insertSod['total_sod_price'] = 0;
                $insertSod['retail_price'] = $mc['retail_price'];
                $insertSod['discount_price'] = $mc['retail_price'];
                $insertSod['order_id'] = $this->order['order_id'];
                $flag[] = $this->addSaleOrdersDetails($insertSod);
                actionLog($this->getLS(),'【SQL】添加赠品订单详情信息');
                $updateOrder['total_quantity'] = $this->order['total_quantity']++;
                $updateOrder['discount_price'] =  $this->order['discount_price'] + $insertSod['discount_price'];
                $updateOrder['retail_price'] = $this->order['retail_price'] + $insertSod['discount_price'];
                actionLog($this->getLS(),'【SQL】处理订单信息');
            }
        }
        if ($updateOrder) {
            $updateOrder['order_id'] = $this->order['order_id'];
            $updateOrder['order_type'] = $this->order['order_type'] == 1 ? 5 : 6;
            $updateOrder['fd_id'] = $this->fd['fd_id'] ?? 0;
            $flag[] = $this->updateSaleOrders($updateOrder);
            actionLog($this->getLS(),'【SQL】处理订单信息');
        }
        return $this->checkFlag($flag);
    }

    protected function clampFdDiscount($discount, $amount)
    {
        $discount = bcadd(strval($discount), '0', 4);
        $amount = bcadd(strval($amount), '0', 4);
        if (bccomp($discount, '0', 4) < 0 || bccomp($amount, '0', 4) <= 0) return '0.0000';
        return bccomp($discount, $amount, 4) > 0 ? $amount : $discount;
    }

    protected function subtractFdDiscount($amount, $discount)
    {
        $amount = bcadd(strval($amount), '0', 4);
        $discount = $this->clampFdDiscount($discount, $amount);
        $result = bcsub($amount, $discount, 4);
        return bccomp($result, '0', 4) < 0 ? '0.0000' : $result;
    }

}
