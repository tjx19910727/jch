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
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersTrait;
use app\AppFactory\Management\ManagementClient;

class ActivityPickClient extends ManagementClient
{
    use ActivityPickTrait,ActivityPickCodeTrait,ActivityGoodsTrait,ActivityMachineTrait;
    use MachineTrait,GoodsTrait,SaleOrdersTrait;


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
        if ($postData['start_time'] && $postData['start_time'] <= time()) {
            $postData['status'] = 2;
        }
        $this->startTrans();
        try {
            $a_id = $this->addActivityPick($postData);
            if ($a_id) {
                $insert = [
                    "a_id" => $a_id,
                    "a_type" => 4,
                ];
                $amResult = $this->addAm($insert, $machineList);
                if ($amResult !== true) {
                    $this->rollbackTrans();
                    return $this->rFail($amResult);
                }
                $agResult = $this->addAg($insert, $goodsList);
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
            return $this->rValidate($e->getMessage());
        }
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

        try {
            $flag[] = $this->updateActivityPick($postData, [], ['pick_name', 'desc', 'bg_pic', 'start_time', 'end_time']);
            $insert = [
                "a_id" => $postData['id'],
                "a_type" => 4,
            ];
            if ($machineList) {
                $oldAmList = $this->getActivityMachineColumn(['a_id' => $postData['id'], 'a_type' => 4], 'machine_id');
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
                if ($delAmList) $flag[] = $this->delActivityMachine(['a_id' => $postData['id'], 'a_type' => 4, ['machine_id', 'in', $delAmList]]);
            }
            if ($goodsList) {
                $oldAgList = $this->getActivityGoodsColumn(['a_id' => $postData['id'], 'a_type' => 4], 'g_id');
                $delAgList = array_diff($oldAgList, $goodsList);
                $addAgList = array_diff($goodsList, $oldAgList);
                if ($addAgList) {
                    $agResult = $this->addAg($insert, $goodsList);
                    if ($agResult !== true) {
                        $this->rollbackTrans();
                        return $this->rFail($agResult);
                    }
                    $flag[] = 1;
                }
                if ($delAgList) $flag[] = $this->delActivityGoods(['a_id' => $postData['id'], 'a_type' => 4, ['g_id', 'in', $delAgList]]);
            }
            $check = $this->checkFlag($flag);
            return $this->checkTrans($check);
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e,1);
            return $this->rValidate($e->getMessage());
        }
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
        try {
            $flag[] = $this->updateActivityPick(['status' => 4], $where, ['status']);
            $where['status'] = 1;
            $flag[] = $this->updateActivityPickCode(['status' => 4], [['ap_id', 'in', $postData['id']]], ['status']);
            $result = $this->checkFlag($flag);
            return $this->checkTrans($result);
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e,1);
            return $this->rValidate($e->getMessage());
        }
    }

    /**
     * 取货码查询取货活动
     * @param $postData
     * @return array|\think\response\Json
     */
    public function getByAmCode($postData)
    {
        try {
            $pickCode = $this->getActivityPickCodeFind(['code' => $postData['code']], 'apc_id,ap_id,code,status,used_time,pick_type');
            if ($pickCode) {
                if ($pickCode['status'] == 2) return $this->r(1112, $this->lang("VActivityPickCode.status2"));
                if ($pickCode['status'] == 3) return $this->r(1113, $this->lang("VActivityPickCode.status3"));
                if ($pickCode['status'] == 4) return $this->r(1114, $this->lang("VActivityPickCode.status4"));
                if ($pickCode['status'] == 5) return $this->r(1114, $this->lang("VActivityPickCode.status5"));
                $pick = $this->getActivityPickFind(['id' => $pickCode['ap_id']], 'id,pick_name,desc,start_time,end_time,pick_type,status');
                if (!$pick) return $this->r(100, $this->lang("VActivityPick.pick_no_data"));
                $pick['pickCode'] = $pickCode;
                $am = $this->getActivityMachineFind(['a_id' => $pick['id'], 'a_type' => 4, 'm_id' => $postData['m_id']], 'am_id');
                if (!$am) return $this->r(100, $this->lang("VActivityPick.machine_no_data"));
                if ($pick['status'] == 1) {
                    if ($pick['start_time'] < time()) {
                        return $this->r(1101, $this->lang("VActivityPick.status1"));
                    }
                    $pick['status'] = 2;
                    $this->updateActivityPick(['id' => $pick['id'], 'status' => 2]);
                }
                if ($pick['status'] == 2) {
                    if ($pick['end_time'] < time()) {
                        $this->updateActivityPick(['id' => $pick['id'], 'status' => 3]);
                        return $this->r(1103, $this->lang("VActivityPick.status3"));
                    }
                }
                if ($pick['status'] == 3) return $this->r(1103, $this->lang("VActivityPick.status3"));
                if ($pick['status'] == 4) return $this->r(1104, $this->lang("VActivityPick.status4"));
                if ($pick['pick_type'] == 3) {
                    $order = $this->getSaleOrdersFind(['order_id' => $pickCode['order_id']], 'order_id,trade_no,pay_time');
                    if ($order) {
                        $order = $order->toArray();
                        $order['details'] = $this->getSaleOrdersDetailsList(['order_id' => $order['order_id']], 0, 'g_name,pic,sku,quantity');
                        if ($order['details']) $order['details'] = $order['details']->toArray();
                    }
                    $pick['order'] = $order;
                }
                if ($pick['pick_type'] == 2) {
                    $ag = $this->getActivityGoodsList(['a_id' => $pick['id'], 'a_type' => 4], 0, 'ag_id,g_name,pic,sku');
                    if ($ag) {
                        $pick['activity_goods'] = $ag->toArray();
                    }
                }
                return $this->r(200, $this->lang("query_success"), $pick);
            }
            return $this->rFail($this->lang("VActivityPickCode.pick_code_no_data"));
        } catch (\Exception $e) {
            actionException($e,1);
            return $this->rTryCatch($e->getMessage());
        }
    }
}