<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/9/26
 * Time: 17:14
 */

namespace app\AppFactory\Kernel\Traits\Store;


use app\AppFactory\Kernel\Model\Store\StoreHostingModel;

trait StoreHostingTrait
{
    public function getStoreHostingList($where,$pageNum = 0,$field = "*", $order = "update_time desc")
    {
        return StoreHostingModel::getList($where,$pageNum,$field,$order);
    }

    public function getStoreHostingFind($where,$field = "*",$order = "")
    {
        return StoreHostingModel::getFind($where,$field,$order);
    }

    public function addStoreHosting($insert)
    {
        $sh = StoreHostingModel::create($insert);
        return $sh->id;
    }

    public function updateStoreHosting($update,$where = [], $field = [])
    {
        return StoreHostingModel::update($update,$where,$field);
    }


    // 循环计算托管
//    private function loopCalculateAmount($hosting,$totalAmount = 0)
//    {
//        // 以开始时间获取当天结束时间节点，日期23:59:59
//        $hosting['end'] = strtotime(date("Y-m-d 23:59:59",$hosting['start'] + $hosting['cycle'] * 86400));
//        // 结束节点小于结束时间点，以结束节点作为结束时间计算总金额
//        if ($hosting['end'] < $hosting['end_time']) {
//            $totalAmount += $this->getTotalAmount($hosting);
//            // 以结束节点加1秒为下次循环的开始时间
//            $hosting['start'] = $hosting['end'] + 1;
//            $totalAmount = $this->loopCalculateAmount($hosting,$totalAmount);
//        }
//        // 结束节点大于等于结束时间点，使用结束时间计算总金额
//        if ($hosting['end'] >= $hosting['end_time']) {
//            $totalAmount += $this->getTotalAmount($hosting);
//        }
//        return $totalAmount;
//    }

    /**
     * 计算托管费用及生成托管详情记录
     * @param $hosting
     * @return float
     */
    public function getTotalAmount($hosting,$order)
    {
        // 计算当前订单的托管费用
        // 统计今天已产生的托管费用，与上限的差额，
        // 差额大于当前订单的托管费用时，以订单托管费用为准，当差额小于当前订单托管费用，以差额为托管费用。
        // 统计总托管金额
        // 生成托管详情记录
        // 修改托管记录总托管金额、结束时间、时长
        // 获取店长余额，减店长余额
        // 余额等于0或负数时停止托管状态。修改托管记录状态为已结束，修改门店为值守模式，停止托管时，发送托管结束至托管频道


        $money = bcmul($order['total_price'],bcdiv($hosting['charge_value'],100,3),3);

        $todayTotalAmount = $this->getStoreHostingDetailsSum(['store_id' => $hosting['store_id'],['create_time','>=', date("Y-m-d 00:00:00",time())]],'amount');
        $todayBalance = bcsub($hosting['charge_max_limit'],$todayTotalAmount,3);

        if ($todayBalance < $money) $money = $todayBalance;

        $totalAmount = $this->getStoreHostingDetailsValue(['store_id' => $hosting['store_id']],'hosting_total_amount','shd_id');
        $totalAmount = bcadd($totalAmount,$money,3);

        $updateHosting["id"] = $hosting['id'];
        $updateHosting["end_time"] = time();
        $updateHosting["duration"] = bcsub($updateHosting['end_time'], $hosting['start_time']);
        $updateHosting["amount"] = $totalAmount;

        $insertDetails = [
            "sh_id" => $hosting['st_id'],
            "order_id" => $order['order_id'],
            "order_trade_no" => $order['trade_no'],
            "store_id" => $hosting['store_id'],
            "store_name" => $hosting['store_name'],
            "terminal_no" => $hosting['terminal_no'],
            "order_total_amount" => $order['total_price'],
            "charge_type" => $hosting['charge_type'],
            "charge_value" => $hosting['charge_value'],
            "hosting_total_amount" => $totalAmount,
            "amount" => $money,
        ];

        $manager_balance = $this->getAuthManagerValue(['manager_id' => $hosting['store_manager']],'balance');
        $manager_balance = bcsub($manager_balance,$money,2);
        if ($manager_balance <= 0) {
            $flag[] = $this->updateStore(['store_id' => $hosting['store_id'],'store_mode' => 2]);
            $this->store['store_id'] = $hosting['store_id'];
            $this->store['store_mode'] = 3;
            $this->handleCancel($hosting);
            $sendData = $hosting;
            $this->sendGateway("watchman" . $hosting['watch_man'],$sendData,"cancelHosting");
        }

        $flag[] = $this->addStoreHostingDetails($insertDetails);
        $flag[] = $this->updateAuthManager(['manager_id' => $hosting['store_manager'],'balance' => $manager_balance]);
        $flag[] = $this->updateStoreHosting($updateHosting);
        actionLog($flag,'结算托管FLAG');
        return flag_check($flag);
    }
}