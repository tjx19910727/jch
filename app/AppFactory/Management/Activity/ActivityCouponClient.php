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
use app\AppFactory\Management\ManagementClient;

class ActivityCouponClient extends ManagementClient
{
    use GoodsTrait,MachineTrait;
    use ActivityGoodsTrait,ActivityMachineTrait;
    use ActivityCouponTrait, ActivityCouponUsedTrait;

    public function getAcAgAmList($where,$pageNum = 0, $field = "*", $order = "")
    {
        return $this->rQ($this->getActivityCouponList($where,$pageNum,$field,$order,function ($ac) {
            $whereA = ['a_type' => 1, "a_id" => $ac['c_id']];
            $ac['goodsList'] = $this->getActivityGoodsList($whereA,0,'ag_id,g_id,g_name,sku,market_price,retail_price');
            $ac['machineList'] = $this->getActivityMachineList($whereA,0,'am_id,m_id,machine_id,machine_name');
            return $ac;
        }));
    }

    public function getAcAgAmFind($where,$field = "*")
    {
        $ac = $this->getActivityCouponFind($where,$field);
        if ($ac) {
            $whereA = ['a_type' => 1, "a_id" => $ac['c_id']];
            $ac['goodsList'] = $this->getActivityGoodsList($whereA,0,'ag_id,g_id,g_name,sku,market_price,retail_price');
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
        if (isset($postData['machineList'])) {
            $machineList = $postData['machineList'];
            unset($postData['machineList']);
        }
        if (isset($postData['goodsList'])) {
            $goodsList = $postData['goodsList'];
            unset($postData['goodsList']);
        }
        if ($postData['start_date'] && $postData['start_date'] <= strtotime(date("Y-m-d"))) {
            $postData['status'] = 2;
        }
        $this->startTrans();
        $a_id = $this->addActivityCoupon($postData);
        if ($a_id) {
            $insert = [
                "a_id" => $a_id,
                "a_type" => 1,
            ];
            if ($postData['designated_machine'] == 2) {
                $amResult = $this->addAm($insert,$machineList);
                if ($amResult !== true) {
                    $this->rollbackTrans();
                    return $this->rFail($amResult);
                }
            }
            if ($postData['designated_goods'] == 2 || $postData['designated_goods'] == 3) {
                $agResult = $this->addAg($insert,$goodsList);
                if ($agResult !== true) {
                    $this->rollbackTrans();
                    return $this->rFail($agResult);
                }
            }
            $this->commitTrans();
            return $this->r(200,$this->lang("add_success"));
        }
        $this->rollbackTrans();
        return $this->rFail($this->lang("add_fail"));
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
        if (isset($postData['machineList'])) {
            $machineList = $postData['machineList'];
        }
        if (isset($postData['goodsList'])) {
            $goodsList = $postData['goodsList'];
        }
        $flag[] = 1;
        $this->startTrans();
        $flag[] = $this->updateActivityCoupon($postData);

        $insert = [
            "a_id" => $postData['c_id'],
            "a_type" => 1,
        ];
        if ($machineList && $postData['designated_machine'] == 2) {
            $oldAmList = $this->getActivityMachineColumn(['a_id' => $postData['c_id'],'a_type' => 1],'machine_id');
            $delAmList = array_diff($oldAmList,$machineList);
            $addAmList = array_diff($machineList,$oldAmList);
            if ($addAmList) {
                $amResult = $this->addAm($insert, $addAmList);
                if ($amResult !== true) {
                    $this->rollbackTrans();
                    return $this->rFail($amResult);
                }
                $flag[] = 1;
            }
            if ($delAmList) $flag[] = $this->delActivityMachine(['a_id' => $postData['c_id'],'a_type' => 1, ['machine_id','in', $delAmList]]);
        }
        if ($goodsList && ($postData['designated_goods'] == 2 || $postData['designated_goods'] == 3)) {
            $oldAgList = $this->getActivityGoodsColumn(['a_id' => $postData['c_id'],'a_type' => 1],'g_id');
            $delAgList = array_diff($oldAgList,$goodsList);
            $addAgList = array_diff($goodsList,$oldAgList);
            if ($addAgList) {
                $agResult = $this->addAg($insert, $goodsList);
                if ($agResult !== true) {
                    $this->rollbackTrans();
                    return $this->rFail($agResult);
                }
                $flag[] = 1;
            }
            if ($delAgList) $flag[] = $this->delActivityGoods(['a_id' => $postData['c_id'],'a_type' => 1,['g_id','in',$delAgList]]);
        }
        $check = $this->checkFlag($flag);
        return $this->checkTrans($check);
    }

    /**
     * 优惠券主动下架
     * @param $where
     * @return bool|string
     */
    public function activeTakeDown($where)
    {
        $this->startTrans();
        $flag[] = $this->updateActivityCoupon(['status' => 4],$where,['status']);
        $where['status'] = 1;
        $flag[] = $this->updateActivityCouponUsed(['status' => 4],$where,['status']);
        $result = $this->checkFlag($flag);
        return $this->checkTrans($result);
    }
}