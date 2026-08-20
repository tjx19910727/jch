<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/15
 * Time: 14:30
 */

namespace app\AppFactory\Kernel\Traits\Activity;


use app\AppFactory\Kernel\Model\Activity\Pick\ActivityPickModel;

trait ActivityPickTrait
{
    public function getActivityPickFind($where,$field = "*",$order = "id desc")
    {
        return ActivityPickModel::getFind($where,$field,$order);
    }

    public function getActivityPickList($where,$pageNum = 0,$field = "*", $order = "id desc",$eachFunc = "")
    {
        return ActivityPickModel::getList($where,$pageNum,$field,$order,$eachFunc);
    }

    public function getActivityPickListByMachine($where,$field = "*", $order = "id desc")
    {
        return ActivityPickModel::getListByMachine($where,$field,$order);
    }

    public function addActivityPick($insert)
    {
        $insert['creator'] = ($this->manager['manager_id'] ?? 0);
        $ap = ActivityPickModel::create($insert);
        return $ap->id;
    }

    public function updateActivityPick($update,$where = [],$field = [])
    {
        $update['update_id'] = ($this->manager['manager_id'] ?? 0);
        return ActivityPickModel::update($update,$where,$field);
    }

    public function delActivityPick($where)
    {
        return ActivityPickModel::whereDel($where);
    }

    /**
     * 根据设备编号获取取货码活动
     * @return ActivityPickModel[]|array|\think\Collection
     */
    public function getActivityPickByMachine()
    {
        $where = "`am`.`m_id` = " . $this->machine['m_id'] . "  AND `start_time` < " . strtotime(date("Y-m-d H:i:s")) . " AND `status` < 3 AND (
        `end_time` is null or `end_time` > " . strtotime(date("Y-m-d H:i:s")) . ")";
        $field = "id,pick_name,desc,bg_pic,start_time,end_time,pick_type,status";
        $ap = $this->getActivityPickListByMachine($where, $field);
        if ($ap) {
            $ap = $ap->toArray();
            $agField = "g_id,g_name,pic,sku,market_price,retail_price,gc_id,gc_name";
            foreach ($ap as $key => $value) {
                $update = [];
                if ($value['status'] == 1) {
                    $update['status'] = 2;
                    $value['status'] = 2;
                }
                if ($value['end_time'] > 0 && $value['end_time'] < time() && $value['status'] != 3) {
                    $update['status'] = 3;
                    $value['status'] = 3;
                }
                if ($update) $this->updateActivityPick($update,['id' => $value['id']]);
                $ap[$key] = $value;
                $gIds = $this->getMachineChannelColumn(['m_id' => $this->machine['m_id'],'status' => 1],'g_id');
                $whereAg = [];
                $whereAg['a_id'] = $value['id'];
                $whereAg['a_type'] = 4;
                if ($gIds) $whereAg[] = ['g_id','in',$gIds];
                $ap[$key]['ag'] = $this->getActivityGoodsList($whereAg, 0, $agField);
            }
        }
        return $ap;
    }

    /**
     * 终端使用取货码获取取货码活动信息
     * @return mixed
     */
    public function getActivityPickByCode()
    {
        // 该类型取货码关联订单而非活动，走 activity_pick 表 inner join 查询会匹配不到（查无活动）
        // 精确限定 ap_id=0 与 pick_type=3，不影响活动取货码（pick_type=1/2, ap_id>0）的原有逻辑
        $apcDirect = $this->getActivityPickCodeFind([
            'code'      => $this->data['pick_code'],
            'pick_type' => 3,
            'ap_id'     => 0,
            'status'    => 1,
        ], 'apc_id,ap_id,code,order_id,trade_no,m_id,machine_id,machine_name,pick_type,status,used_time');
        if ($apcDirect) {
            return $apcDirect->toArray();
        }

        //$where['code'] = $this->data['pick_code'];
        $where['apc.code'] = $this->data['pick_code'];
        //$fieldAc = "apc_id,ap_id,code,order_id,trade_no,m_id,machine_id,machine_name,pick_type,status,used_time";
        // $apc = $this->getActivityPickCodeFind($where, $fieldAc);
        //修复相同提货码下，有未开始的活动，导致活动时间内的取货码不能使用的问题
        $apc = $this->getActivityPickCodeFindWithPick($where);
        if ($apc) {
            $apc = $apc->toArray();
            // 检查取货码使用状态
            if ($apc) {
                // 已使用
                if ($apc['status'] == 2) return $this->lang("VActivityPickCode.status2");
                // 已过期
                if ($apc['status'] == 3) return $this->lang("VActivityPickCode.status3");
                // 已作废
                if ($apc['status'] == 4) return $this->lang("VActivityPickCode.status4");
                // 使用中
                if ($apc['status'] == 5) return $this->lang("VActivityPickCode.status5");
            }
            if ($apc['ap_id'] > 0) {
                $ap = $this->getActivityPickFind(['id' => $apc['ap_id']], 'id,pick_name,desc,bg_pic,start_time,end_time,pick_type,status');
                if ($ap) {
                    $ap = $ap->toArray();
                    // 开始时间大于当前时间，取货活动还未开始的
                    if ($ap["start_time"] > time()) {
                        return $this->lang("VActivityPick.not_begin");
                    }
                    // 有设置结束时间，并且结束时间小于当前时间，活动已结束
                    if ($ap["end_time"] > 0 && $ap['end_time'] < time()) {
                        // 修改取货码活动为3.已过期
                        $this->updateActivityPick(['id' => $ap['id'], 'status' => 3]);
                        // 修改取货码使用记录为3.已过期
                        $this->updateActivityPickCode(['status' => 3], ['ap_id' => $ap['id'], 'status' => 1]);
                        return $this->lang("VActivityPick.finished");
                    }
                    // 取货码状态由1.未开始修改为2.进行中
                    if ($ap['status'] == 1) $this->updateActivityPick(['status' => 2], ['id' => $ap['id']]);
                    // 有指定商品且不是全部商品，查询指定商品列表
                    $gIds = $this->getMachineChannelColumn(['m_id' => $this->machine['m_id'],'status' => 1],'g_id');
                    $whereAg = [];
                    $whereAg['a_id'] = $ap['id'];
                    $whereAg['a_type'] = 4;
                    if ($gIds) $whereAg[] = ['g_id','in',$gIds];
                    $ag = $this->getActivityGoodsList($whereAg, 0,
                        'g_id,g_name,pic,sku,market_price,retail_price,gc_id,gc_name'
                    );
                    $ap['ag'] = $ag->toArray();
                }
                $apc['ap'] = $ap;
            }
            return $apc;
        }
        return $this->lang('VActivityPick.ap_not_data');
    }

    /**
     * 订单使用取货码
     * @param $trade_no
     * @param $order_id
     * @return mixed
     * @throws \Exception
     */
    public function orderUsePickCode($trade_no,$order_id)
    {
        // 通过取货码获取取货码活动信息，判断使用条件
        $ap = $this->getActivityPickByCode();
        if (is_string($ap)) {
            return $ap;
        }

        // 系统随机派送
        if ($ap['apc']['pick_type'] == 1) {
            if (!$ap['ag']) {
                return $this->lang("VActivityPick.ag_not_data");
            }
            $apg_id = array_column($ap['ag'], 'g_id');
            $mc = $this->getMachineChannelColumn(['m_id' => $this->machine['m_id'],['g_id', 'in', $apg_id]],'mc_id');
            $mc_count = count($mc);
            $num = random_int(1,$mc_count);
            if (is_string($this->data['carList'])) $this->data['carList'] = array();
            $this->data['carList'][] = ["mc_id" => $mc[($num - 1)],'quantity' => 1];
        }
        $updatePc['status'] = 5;
        $updatePc['order_id'] = $order_id;
        $updatePc['trade_no'] = $trade_no;
        $updatePc['m_id'] = $this->machine['m_id'];
        $updatePc['machine_id'] = $this->machine['machine_id'];
        $updatePc['machine_name'] = $this->machine['machine_name'];
        $this->updateActivityPickCode($updatePc, ['code' => $this->data['pick_code'],'status' => 1]);
        actionLog($this->getLS(),'修改提货码使用记录');
        $update['order_type'] = 3;
        $update['pay_status'] = 2;
        $update['pay_type'] = 0;
        $update['pay_method'] = 1;
        $update['pay_time'] = time();
        $update['pay_code'] = $this->data['pick_code'];
        $update['apc_id'] = $ap['apc']['apc_id'] ?? 0;
        return $update;
    }
}