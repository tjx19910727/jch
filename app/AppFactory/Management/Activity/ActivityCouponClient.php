<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/4
 * Time: 14:04
 */

namespace app\AppFactory\Management\Activity;


use app\AppFactory\Kernel\Traits\Activity\ActivityCouponTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityCouponUsedTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityGoodsTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityMachineTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Traits\WeiCheng\WcGoodsTrait;
use app\AppFactory\Management\ManagementClient;

class ActivityCouponClient extends ManagementClient
{
    use GoodsTrait,MachineTrait,WcGoodsTrait;
    use ActivityGoodsTrait,ActivityMachineTrait;
    use ActivityCouponTrait, ActivityCouponUsedTrait;

    public function getAcAgAmList($where,$pageNum = 0, $field = "*", $order = "")
    {
        return $this->rQ($this->getActivityCouponList($where,$pageNum,$field,$order,function ($ac) {
            // 优惠券状态由1.未开始修改为2.进行中
            if ($ac['status'] == 1 && $ac['start_date'] > strtotime(date("Y-m-d"))) {
                $this->updateActivityCoupon(['status' => 2], ['c_id' => $ac['c_id']]);
                $ac['status'] = 2;
            }
            // 有设置结束时间，并且结束时间小于当前时间，活动已结束
            if ($ac['status'] < 4 && $ac["end_date"] > 0 && $ac['end_date'] < time()) {
                // 修改优惠券活动为3.已过期
                $this->updateActivityCoupon(['c_id' => $ac['c_id'], 'status' => 3]);
                // 修改随机码优惠券使用记录为3.已过期
                if (!$ac['code']) $this->updateActivityCouponUsed(['status' => 3], ['c_id' => $ac['c_id'], 'status' => 1]);
                $ac['status'] = 3;
            }
            $whereA = ['a_type' => 1, "a_id" => $ac['c_id']];
            $ac['goodsList'] = $this->getActivityGoodsList(array_merge($whereA, ['goods_source' => 1]),0,'ag_id,g_id,g_name,sku,market_price,retail_price,goods_source,source_no');
            $ac['onlineGoodsList'] = $this->getActivityGoodsList(array_merge($whereA, ['goods_source' => 2]),0,'ag_id,g_id,g_name,sku,market_price,retail_price,goods_source,source_no');
            $ac['machineList'] = $this->getActivityMachineList($whereA,0,'am_id,m_id,machine_id,machine_name');
            return $ac;
        }));
    }

    public function getAcAgAmFind($where,$field = "*")
    {
        $ac = $this->getActivityCouponFind($where,$field);
        if ($ac) {
            $whereA = ['a_type' => 1, "a_id" => $ac['c_id']];
            $ac['goodsList'] = $this->getActivityGoodsList(array_merge($whereA, ['goods_source' => 1]),0,'ag_id,g_id,g_name,sku,market_price,retail_price,goods_source,source_no');
            $ac['onlineGoodsList'] = $this->getActivityGoodsList(array_merge($whereA, ['goods_source' => 2]),0,'ag_id,g_id,g_name,sku,market_price,retail_price,goods_source,source_no');
            $ac['machineList'] = $this->getActivityMachineList($whereA,0,'am_id,m_id,machine_id,machine_name');
        }
        return $this->rQ($ac);
    }

    /**
     * 添加优惠券活动
     * @param $postData
     * @return array|string
     */
    public function addAc($postData)
    {
        $machineList = [];
        $goodsList = [];
        $onlineGoodsList = [];
        if (isset($postData['machineList'])) {
            $machineList = $postData['machineList'];
            unset($postData['machineList']);
        }
        if (isset($postData['goodsList'])) {
            $goodsList = $postData['goodsList'];
            unset($postData['goodsList']);
        }
        if (isset($postData['onlineGoodsList'])) {
            $onlineGoodsList = $this->normalizeOnlineGoodsList($postData['onlineGoodsList']);
            unset($postData['onlineGoodsList']);
        }
        if ($postData['start_date'] && $postData['start_date'] <= strtotime(date("Y-m-d"))) {
            $postData['status'] = 2;
        }
        if (!empty($postData['code'])) {
            $check = $this->getActivityCouponFind(['code' => $postData['code'],'status' => 2],'c_id');
            if ($check) return $this->r(100,'当前优惠码已存在，不能重复使用');
        }
        if (!isset($postData['ao_id'])) $postData['ao_id'] = $this->manager['ao_id'];
        $this->startTrans();
        try {
            $a_id = $this->addActivityCoupon($postData);
            if ($a_id) {
                $insert = [
                    "a_id" => $a_id,
                    "a_type" => 1,
                ];
                if ($postData['designated_machine'] == 2) {
                    $amResult = $this->addAm($insert, $machineList);
                    if ($amResult !== true) {
                        $this->rollbackTrans();
                        return $this->rFail($amResult);
                    }
                }
                if ($postData['designated_goods'] == 2 || $postData['designated_goods'] == 3) {
                    if ($goodsList) {
                        $agResult = $this->addAg($insert, $goodsList);
                        if ($agResult !== true) {
                            $this->rollbackTrans();
                            return $this->rFail($agResult);
                        }
                    }
                    if (!$goodsList && !$onlineGoodsList) {
                        $this->rollbackTrans();
                        return $this->rFail('请选择商品');
                    }
                }
                $agResult = $this->addOnlineAg($insert, $onlineGoodsList);
                if ($agResult !== true) {
                    $this->rollbackTrans();
                    return $this->rFail($agResult);
                }
                $this->commitTrans();
                return $this->r(200, $this->lang("add_success"));
            }
            $this->rollbackTrans();
            return $this->rFail($this->lang("add_fail"));
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e,1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 修改优惠券信息
     * @param $postData
     * @return array|bool|string
     */
    public function updateAc($postData)
    {
        $machineList = [];
        $goodsList = [];
        $onlineGoodsList = [];
        $hasOnlineGoodsList = false;
        if (isset($postData['machineList'])) {
            $machineList = $postData['machineList'];
        }
        if (isset($postData['goodsList'])) {
            $goodsList = $postData['goodsList'];
        }
        if (isset($postData['onlineGoodsList'])) {
            $hasOnlineGoodsList = true;
            $onlineGoodsList = $this->normalizeOnlineGoodsList($postData['onlineGoodsList']);
            unset($postData['onlineGoodsList']);
        }
        if (isset($postData['code']) && $postData['code']) {
            // 更新时排除当前优惠券自身，仅拦截其他生效中/未开始的同码优惠券。
            $check = $this->getActivityCouponFind([
                'code' => $postData['code'],
                ['status', 'in', [1, 2]],
                ['c_id', '<>', $postData['c_id']],
            ], 'c_id');
            if ($check) return $this->r(100,'当前优惠码已存在，不能重复使用');
        }

        $flag[] = 1;
        $this->startTrans();

        try {
            $this->updateActivityCoupon($postData);
            $insert = [
                "a_id" => $postData['c_id'],
                "a_type" => 1,
            ];
            if ($machineList && $postData['designated_machine'] == 2) {
                $oldAmList = $this->getActivityMachineColumn(['a_id' => $postData['c_id'], 'a_type' => 1], 'machine_id');
                $delAmList = array_diff($oldAmList, $machineList);
                $addAmList = array_diff($machineList, $oldAmList);
                if ($addAmList) {
                    $amResult = $this->addAm($insert, $addAmList);
                    if ($amResult !== true) {
                        $this->rollbackTrans();
                        return $this->rFail($amResult);
                    }
                    $flag[] = 1;
                }
                if ($delAmList) $flag[] = $this->delActivityMachine(['a_id' => $postData['c_id'], 'a_type' => 1, ['machine_id', 'in', $delAmList]]);
            }
            if ($goodsList && ($postData['designated_goods'] == 2 || $postData['designated_goods'] == 3)) {
                $oldAgList = $this->getActivityGoodsColumn(['a_id' => $postData['c_id'], 'a_type' => 1], 'g_id');
                $delAgList = array_diff($oldAgList, $goodsList);
                $addAgList = array_diff($goodsList, $oldAgList);
                if ($delAgList) {
                    $flag[] = $this->delActivityGoods(['a_id' => $postData['c_id'], 'a_type' => 1, ['g_id', 'in', $delAgList]]);
                }
                if ($addAgList) {
                    $agResult = $this->addAg($insert, $goodsList);
                    if ($agResult !== true) {
                        $this->rollbackTrans();
                        return $this->rFail($agResult);
                    }
                    $flag[] = 1;
                }
            }
            if ($hasOnlineGoodsList) {
                $this->delActivityGoods(['a_id' => $postData['c_id'], 'a_type' => 1, 'goods_source' => 2]);
                $agResult = $this->addOnlineAg($insert, $onlineGoodsList);
                if ($agResult !== true) {
                    $this->rollbackTrans();
                    return $this->rFail($agResult);
                }
            }
            actionLog($flag,'修改结果集');
            $check = $this->checkFlag($flag);
            return $this->checkTrans($check);
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e,1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /** 线上商品独立于 designated_goods，按逗号分隔字符串接收。 */
    protected function normalizeOnlineGoodsList($value)
    {
        if (is_array($value)) return $value;
        $value = trim(strval($value));
        if ($value === '') return [];
        $decoded = json_decode($value, true);
        if (is_array($decoded)) return $decoded;
        return array_values(array_filter(array_map('trim', explode(',', $value)), function ($item) {
            return $item !== '';
        }));
    }

    /**
     * 优惠券主动下架
     * @param $where
     * @return bool|string
     */
    public function activeTakeDown($where)
    {
        $this->startTrans();
        try {
            $flag[] = $this->updateActivityCoupon(['status' => 4], $where, ['status']);
            $where['status'] = 1;
            $flag[] = $this->updateActivityCouponUsed(['status' => 4], $where, ['status']);
            $result = $this->checkFlag($flag);
            return $this->checkTrans($result);
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e,1);
            return $this->rTryCatch($e->getMessage());
        }
    }
}
