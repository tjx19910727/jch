<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/15
 * Time: 14:38
 */

namespace app\AppFactory\Management\Activity;


use app\AppFactory\Kernel\Traits\Activity\ActivityGoodsTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityMachineTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityPickCodeTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityPickTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Management\ManagementClient;

class ActivityPickClient extends ManagementClient
{
    use ActivityPickTrait,ActivityPickCodeTrait,ActivityGoodsTrait,ActivityMachineTrait;
    use MachineTrait,GoodsTrait;


    public function getApAgAmList($where,$pageNum = 0, $field = "*",$order = "")
    {
        $apList = $this->getActivityPickList($where,$pageNum,$field,$order,function ($ap) {
            $whereA = ['a_type' => 4, "a_id" => $ap['id']];
            $ap['goodsList'] = $this->getActivityGoodsList($whereA,0,'ag_id,g_id,g_name,sku,market_price,retail_price');
            $ap['machineList'] = $this->getActivityMachineList($whereA,0,'am_id,m_id,machine_id,machine_name');
            return $ap;
        });
        return $this->rQ($apList);
    }

    public function getApAgAmFind($where,$field = "*")
    {
        $ap = $this->getActivityPickFind($where,$field);
        if ($ap) {
            $whereA = ['a_type' => 4, "a_id" => $ap['id']];
            $ap['goodsList'] = $this->getActivityGoodsList($whereA,0,'ag_id,g_id,g_name,sku,market_price,retail_price');
            $ap['machineList'] = $this->getActivityMachineList($whereA,0,'am_id,m_id,machine_id,machine_name');
        }
        return $this->rQ($ap);
    }

    /**
     * 添加提货码活动
     * @param $postData
     * @return array|string
     */
    public function addAp($postData)
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
        $this->startTrans();
        $a_id = $this->addActivityPick($postData);
        if ($a_id) {
            $insert = [
                "a_id" => $a_id,
                "a_type" => 4,
            ];
            $amResult = $this->addAm($insert,$machineList);
            if ($amResult !== true) {
                $this->rollbackTrans();
                return $this->rFail($amResult);
            }
            $agResult = $this->addAg($insert,$goodsList);
            if ($agResult !== true) {
                $this->rollbackTrans();
                return $this->rFail($agResult);
            }
            $this->commitTrans();
            return $this->r(200,$this->lang("add_success"));
        }
        $this->rollbackTrans();
        return $this->rFail($this->lang("add_fail"));
    }

    /**
     * 修改提货码信息
     * @param $postData
     * @return array|bool|string
     */
    public function updateAp($postData)
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
        $flag[] = $this->updateActivityPick($postData,[],['pick_name','desc','bg_pic','start_time','end_time']);

        $insert = [
            "a_id" => $postData['id'],
            "a_type" => 4,
        ];
        if ($machineList) {
            $oldAmList = $this->getActivityMachineColumn(['a_id' => $postData['id'],'a_type' => 4],'machine_id');
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
            if ($delAmList) $flag[] = $this->delActivityMachine(['a_id' => $postData['id'],'a_type' => 4, ['machine_id','in', $delAmList]]);
        }
        if ($goodsList) {
            $oldAgList = $this->getActivityGoodsColumn(['a_id' => $postData['id'],'a_type' => 4],'g_id');
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
            if ($delAgList) $flag[] = $this->delActivityGoods(['a_id' => $postData['id'],'a_type' => 4,['g_id','in',$delAgList]]);
        }
        $check = $this->checkFlag($flag);
        return $this->checkTrans($check);
    }

    /**
     * 提货码活动主动下架
     * @param $postData
     * @return bool|string
     */
    public function activeTakeDown($postData)
    {
        if (strpos($postData['id'],",") !== false) $where[] = ['id',"in",$postData['id']];
        else $where['id'] = $postData['id'];
        $this->startTrans();
        $flag[] = $this->updateActivityPick(['status' => 4],$where,['status']);
        $where['status'] = 1;
        $flag[] = $this->updateActivityPickCode(['status' => 4],[['ap_id','in',$postData['id']]],['status']);
        $result = $this->checkFlag($flag);
        return $this->checkTrans($result);
    }
}