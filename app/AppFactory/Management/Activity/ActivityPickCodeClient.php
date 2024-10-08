<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/15
 * Time: 14:38
 */

namespace app\AppFactory\Management\Activity;


use app\AppFactory\Kernel\Support\Excel;
use app\AppFactory\Kernel\Traits\Activity\ActivityGoodsTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityMachineTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityPickCodeTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityPickTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersTrait;
use app\AppFactory\Management\ManagementClient;

class ActivityPickCodeClient extends ManagementClient
{
    use ActivityPickCodeTrait, ActivityPickTrait, ActivityGoodsTrait, ActivityMachineTrait;
    use SaleOrdersTrait, MachineTrait, GoodsTrait;

    public $data;

    /**
     * 批量添加
     * @param $postData
     * @return array|string
     * @throws \Exception
     */
    public function addMore($postData)
    {
        $field = "id,start_time,end_time,pick_type,status";
        $pick = $this->getActivityPickFind(['id' => $postData['id']], $field);
        if (!$pick) return $this->r(100, $this->lang("VActivityPickCode.pick_code_no_data"));
        $pick = $pick->toArray();
        if ($pick['status'] == 3) return $this->r(100, $this->lang("VActivityPickCode.status3"));
        if ($pick['status'] == 4) return $this->r(100, $this->lang("VActivityPickCode.status4"));
        $update = [];
        if ($pick['start_time'] < time()) {
            $update['status'] = 2;
        }
        if ($pick['end_time'] > 0 && $pick['end_time'] < time()) {
            $update['status'] = 3;
            $this->updateActivityPick($update);
            return $this->r(100, $this->lang("VActivityPickCode.status3"));
        }
        if ($update) {
            $update['id'] = $pick['id'];
            $this->updateActivityPick($update);
        }

        $insertAll = [];
        $codeList = $this->getActivityPickCodeColumn([], 'code');
        for ($i = 0; $i < $postData['quantity']; $i++) {
            $insert['ap_id'] = $pick['id'];
            $insert['pick_type'] = $pick['pick_type'];
            while (1) {
                $code = $this->leftHandZero(random_int(00000000, 99999999), 8);
                if (!in_array($code, $codeList)) {
                    $codeList[] = $code;
                    break;
                }
            }
            $insert['code'] = $code;
            $insertAll[] = $insert;
        }
        $result = $this->addActivityPickCodeMore($insertAll);
        return $this->rAction($result);
    }

    /**
     * 导出未使用提货码
     * @param $postData
     * @return array|string
     */
    public function exportCode($postData)
    {
        $ap = $this->getActivityPickFind(['id' => $postData['id']], "pick_name");
        if (!$ap) return $this->r(100, '查无提货码活动信息');
        $list = $this->getActivityPickCodeList(['ap_id' => $postData['id'], 'status' => 1], 0, 'code');
        if ($list) {
            $list = $list->toArray();
            $title = ["code" => "提货码"];
            $filename = "【" . $ap['pick_name'] . "】提货码-" . date("YmdHis");
            return $this->sendToExport("营销活动-取货码活动", $filename, $title, $list);
        }
        return $this->rFail("查无提货码");
    }

    /**
     * 导出使用报表
     * @param $postData
     * @return array|string
     */
    public function exportUsedList($postData)
    {
        $ap = $this->getActivityPickFind(['id' => $postData['id']], "pick_name,`desc`");
        if (!$ap) return $this->r(100, '查无提货码信息');
        $list = $this->getActivityPickCodeList(['ap_id' => $postData['id']], 0,
            '("' . $ap['pick_name'] . '") pick_name,("' . $ap['desc'] . '") `desc`,machine_id,machine_name,
                code,trade_no,
                (CASE pick_type WHEN 1 THEN "未使用" WHEN 2 THEN "已使用" WHEN 3 THEN "已过期" WHEN 4 THEN "已作废" WHEN 5 THEN "使用中" END ) status, used_time');
        if ($list) {
            $list = $list->toArray();
            $title = [
                "code" => "提货码",
                "machine_id" => "设备编号",
                "machine_name" => "设备名称",
                "trade_no" => "订单编号",
                "status" => "激活状态",
                "used_time" => "使用时间",
            ];
            $filename = "【" . $ap['pick_name'] . "】使用报表-" . date("Ymd");
            return $this->sendToExport("营销活动-取货码活动", $filename, $title, $list);
        }
        return $this->rFail("查无使用报表信息");
    }

    /**
     * 使用核销码
     * @param $postData
     * @return array|bool|string|\think\response\Json
     */
    public function usePickCode($postData)
    {
        $this->startTrans();
        try {
            $time = time();
            $this->data = $postData;
            $apc = $this->getActivityPickCodeFind(['code' => $this->data['pick_code']], 'apc_id,ap_id,code,order_id,trade_no,m_id,machine_id,machine_name,pick_type,status,used_time', 'apc_id desc');
            if (!$apc) {
                $this->rollbackTrans();
                return $this->r(100, $this->lang("VActivityPickCode.pick_code_no_data"));
            }
            $apc = $apc->toArray();

            if ($apc['status'] == 2) return $this->r(1112, $this->lang("VActivityPickCode.status2"), $apc);
            if ($apc['status'] == 3) return $this->r(1113, $this->lang("VActivityPickCode.status3"), $apc);
            if ($apc['status'] == 4) return $this->r(1114, $this->lang("VActivityPickCode.status4"), $apc);
            if ($apc['status'] == 5) return $this->r(1114, $this->lang("VActivityPickCode.status5"), $apc);

            $ap = $this->getActivityPickFind(['id' => $apc['ap_id']], 'id,pick_name,desc,bg_pic,start_time,end_time,pick_type,status');
            // 开始时间大于当前时间，取货活动还未开始的
            if ($ap["start_time"] > $time) {
                $this->rollbackTrans();
                return $this->r(1101, $this->lang("VActivityPick.not_begin"), $ap);
            }
            // 有设置结束时间，并且结束时间小于当前时间，活动已结束
            if ($ap["end_time"] > 0 && $ap['end_time'] < $time) {
                // 修改取货码活动为3.已过期
                $this->updateActivityPick(['id' => $ap['id'], 'status' => 3]);
                // 修改取货码使用记录为3.已过期
                $this->updateActivityPickCode(['status' => 3], ['ap_id' => $ap['id'], 'status' => 1]);
                $this->commitTrans();
                return $this->r(1103, $this->lang("VActivityPick.finished"), $ap);
            }
            if ($ap['status'] == 4) return $this->r(1104, $this->lang("VActivityPick.status4"), $ap);
            // 取货码活动状态由1.未开始修改为2.进行中
            if ($ap['status'] == 1) $flag[] = $this->updateActivityPick(['status' => 2], ['id' => $ap['id']]);
            $machine = $this->getMachineFind(['m_id' => $this->data['m_id']], 'm_id,machine_name,machine_id,ao_id');
            if (!$machine) {
                $this->rollbackTrans();
                return $this->r(100, $this->lang("VMachine.machine_no_data"));
            }
            if ($apc['pick_type'] == 1) {
                $this->rollbackTrans();
                return $this->r(100, $this->lang("VActivityPickCode.pick_type1"), $ap);
            }
            if ($apc['pick_type'] == 3) {
                $order = $this->getSaleOrdersFind(['order_id' => $apc['order_id']], 'order_id,trade_no,pay_time');
                if ($order) {
                    $order = $order->toArray();
                    $order['details'] = $this->getSaleOrdersDetailsList(['order_id' => $order['order_id']], 0, 'g_name,pic,sku,quantity');
                    if ($order['details']) $order['details'] = $order['details']->toArray();
                }
            } else {
                if (!isset($this->data['g_id'])) {
                    $this->rollbackTrans();
                    return $this->r(300, $this->lang("VActivityPickCode.g_id_require"));
                }
                $trade_no = date("YmdHis") . $machine['m_id'] . $this->get_rand_string(6, "num");
                $order = [
                    "trade_no" => $trade_no,
                    "mch_no" => $trade_no,
                    "m_id" => $machine['m_id'],
                    "machine_name" => $machine['machine_name'],
                    "machine_id" => $machine['machine_id'],
                    "ao_id" => $machine['ao_id'],
                    "order_type" => 3,
                    "pay_status" => 2,
                    "pay_type" => 0,
                    "pay_method" => 1,
                    "pay_time" => time(),
                    "pay_code" => $this->data['pick_code'],
                    "total_quantity" => 1,
                    "create_date" => strtotime(date("Y-m-d")),
                ];
                $order_id = $this->addSaleOrders($order);
                if (!$order_id) {
                    $this->rollbackTrans();
                    return $this->rFail($this->lang("create_order_fail"));
                }
                $order['order_id'] = $order_id;
                $g = $this->getGoodsFind(['g_id' => $this->data['g_id']], 'g_id,g_name,pic,sku,gc_id,gc_name,bar_code');
                if (!$g) {
                    $this->rollbackTrans();
                    return $this->rFail($this->lang("VActivityPickCode.goods_no_data"));
                }
                $details = [
                    "order_id" => $order_id,
                    "channel_position" => 3,
                    "shelf_way" => 4,
                    "g_id" => $g['g_id'],
                    "g_name" => $g['g_name'],
                    "pic" => $g['pic'],
                    "sku" => $g['sku'],
                    "gc_id" => $g['gc_id'],
                    "gc_name" => $g['gc_name'],
                    "quantity" => 1,
                    "success_quantity" => 1,
                    "bar_code" => $g['bar_code'],
                ];
                $sod_id = $this->addSaleOrdersDetails($details);
                if (!$sod_id) {
                    $this->rollbackTrans();
                    return $this->r(100, $this->lang("VSubCar.make_order_details_fail"));
                }
                $details['sod_id'] = $sod_id;
                $order['details'][] = $details;
            }
            $updatePc['order_id'] = $order['order_id'];
            $updatePc['trade_no'] = $order['trade_no'];
            $updatePc['status'] = 2;
            $updatePc['m_id'] = $machine['m_id'];
            $updatePc['machine_id'] = $machine['machine_id'];
            $updatePc['machine_name'] = $machine['machine_name'];
            $updatePc['used_time'] = time();
            $updateOrder['order_id'] = $order['order_id'];
            $updateOrder['out_status'] = 4;
            $updateOrder['out_time'] = time();
            $flag[] = $this->updateActivityPickCode($updatePc, ['code' => $this->data['pick_code'], 'status' => 1]);
            actionLog($this->getLS(), '修改提货码使用记录');
            $flag[] = $this->updateSaleOrders($updateOrder);
            $result = $this->checkFlag($flag);
            return $this->checkTrans($result);
        } catch (\Exception $e) {
            $this->rollbackTrans();
            return $this->rTryCatch($e->getMessage());
        }
    }
}