<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/11/11
 * Time: 9:43
 */

namespace app\AppFactory\Kernel\Traits\Strategy;


use app\AppFactory\Kernel\Model\Strategy\StrategyWithdrawalModel;

trait StrategyWithdrawalTrait
{
    public function getStrategyWithdrawalList($where,$pageNum = 0,$field = "*", $order = "sw_id desc")
    {
        return StrategyWithdrawalModel::getList($where,$pageNum,$field,$order);
    }

    public function getStrategyWithdrawalFind($where,$field = "*",$order = "sw_id desc")
    {
        return StrategyWithdrawalModel::getFind($where,$field,$order);
    }

    public function getSwJoinSm($where,$field = "*",$order = "")
    {
        return StrategyWithdrawalModel::getFindJoinManager($where,$field,$order);
    }

    public function addStrategyWithdrawal($insert)
    {
        $insert['creator'] = $this->manager['manager_id'] ?? 0;
        $sw = StrategyWithdrawalModel::create($insert);
        return $sw->sw_id;
    }

    public function updateStrategyWithdrawal($update, $where = [], $field = [])
    {
        $update['update_id'] = $this->manager['manager_id'] ?? 0;
        return StrategyWithdrawalModel::update($update,$where,$field);
    }

    public function delStrategyWithdrawal($where)
    {
        return StrategyWithdrawalModel::destroy($where);
    }
}