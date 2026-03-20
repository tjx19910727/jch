<?php

/**
 * Created by VSCode.
 * User: Alex-jixiang
 * Date: 2025/12/08
 * Time: 14:00
 */

namespace app\AppFactory\Kernel\Traits\Card;

use app\AppFactory\Kernel\Model\Card\CardModel;
use app\AppFactory\Kernel\Model\Card\CardPointsChangeLogsModel;
use app\AppFactory\Kernel\Model\Card\CardBalanceChangeLogsModel;
use app\AppFactory\Kernel\Traits\ReturnTrait;
use app\AppFactory\Kernel\Traits\WeiCheng\WcBaseTrait;
use think\facade\Db;

trait CardTrait
{
    use WcBaseTrait;
    use ReturnTrait;


    public function getCardColumn($where, $column, $key = "")
    {
        return CardModel::getColumn($where, $column, $key = "");
    }

    public function getCardCount($where, $field = '*', $order = '')
    {
        return CardModel::getFind($where, $field, $order);
    }

    public function getCardList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return CardModel::getList($where, $pageNum, $field, $order, $eachFun, $group);
    }

    public function getCardFind($where, $field = "*", $order = "")
    {
        return CardModel::getFind($where, $field, $order);
    }

    public function getCardSum($where, $sum)
    {
        return CardModel::getSum($where, $sum);
    }

    public function addCard($insert)
    {
        $data = CardModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }
    public function addCardLists($insert)
    {
        return CardModel::insertAll($insert);
    }
    public function updateCard($update, $where = [], $field = [])
    {
        return CardModel::update($update, $where, $field);
    }

    public function delCard($where)
    {
        return CardModel::whereDel($where);
    }

    public function getCardPointsChangeLogs($where, $field = '*', $order = '')
    {
        return CardPointsChangeLogsModel::getFind($where, $field, $order);
    }

    public function getCardPointsChangeLogsList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return CardPointsChangeLogsModel::getList($where, $pageNum, $field, $order, $eachFun, $group);
    }

    public function getCardPointsChangeLogsSum($where, $sum)
    {
        return CardPointsChangeLogsModel::getSum($where, $sum);
    }

    public function addCardPointsChangeLogs($insert)
    {
        $data = CardPointsChangeLogsModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    public function updateCardPointsChangeLogs($update, $where = [], $field = [])
    {
        return CardPointsChangeLogsModel::update($update, $where, $field);
    }

    public function delCardPointsChangeLogs($where)
    {
        return CardPointsChangeLogsModel::whereDel($where);
    }

    //积分变化接口  band_id 预留字段，后续可能绑定微程会员id，或者平台id，绑定账户不尽相同
    public function changePoints($card_no, $points_changed, $change_type, $trade_no = '', $reasons = '', $bind_id = '')
    {
        try {
            $this->startTrans();
            $card = $this->getCardFind(['card_no' => $card_no], 'card_no,points');
            if (!$card)  $this->addCard(['card_no' => $card_no, 'points' => $points_changed]);

            $points_before_change = $card['points'] ?? 0;
            $points = 0;

            if ($points_changed >= 0) {
                if ($change_type == 1) $points = $points_before_change + $points_changed;
                if ($change_type == 2) $points = $points_before_change - $points_changed;
            } else {
                $points_changed_abs = abs($points_changed);
                if ($change_type == 1) $points = $points_before_change - $points_changed_abs;
                if ($change_type == 2) $points = $points_before_change + $points_changed_abs;
            }

            $insert = [
                'card_no' => $card_no,
                'points_before_change' => $points_before_change,
                'points_changed' => $points_changed,
                'points' => $points,
                'change_type' => $change_type,
                'reasons' => $reasons,
                'trade_no' => $trade_no,
                'bind_id' => $bind_id,
                'created_at' => date('Y-m-d H:i:s'),
            ];

            $log_id =  $this->addCardPointsChangeLogs($insert);
            $this->updateCard(['points' => $points], ['card_no' => $card_no]);
            $this->commitTrans();
            return ['card_no' => $card_no, 'points_changed' => $points_changed,  'trade_no' => $trade_no, 'bind_id' => $bind_id];
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionLog("修改卡积分失败");
            actionException($e, 1);
            return false;
        }
        return $log_id;
    }

    public function getCardBalanceChangeLogs($where, $field = '*', $order = '')
    {
        return CardBalanceChangeLogsModel::getFind($where, $field, $order);
    }

    public function getCardBalanceChangeLogsList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return CardBalanceChangeLogsModel::getList($where, $pageNum, $field, $order, $eachFun, $group);
    }

    public function addCardBalanceChangeLogs($insert)
    {
        $data = CardBalanceChangeLogsModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    // 余额变化接口
    public function changeBalance($data)
    {
        $card_no = $data['card_no'] ?? '';
        $balance_changed = $data['balance_changed'] ?? 0;
        $change_type = $data['change_type'] ?? 0;
        $trade_no = $data['trade_no'] ?? '';
        $remark = $data['remark'] ?? '';

        try {
            $this->startTrans();
            $card = $this->getCardFind(['card_no' => $card_no], 'card_no,balance');
            if (!$card) {
                return $this->r(100, $this->lang("VCard.card_no_no_data"));
            }
            $balance_before_change = $card['balance'] ?? 0.00;
            if(!$balance_before_change < $balance_changed){
                return $this->r(100, $this->lang("VCard.balance_not_enough"));
            }
            $balance = 0.00;
            if ($change_type == 1) { // 增加
                $balance = bcadd($balance_before_change, $balance_changed, 2);
            } elseif ($change_type == 2) { // 减少
                $balance = bcsub($balance_before_change, $balance_changed, 2);
            }

            $insert = [
                'card_no' => $card_no,
                'balance_before_change' => $balance_before_change,
                'balance_changed' => $balance_changed,
                'balance' => $balance,
                'change_type' => $change_type,
                'balance_type' => 2,//后台充值
                'trade_no' => $trade_no,
                'activity_id' => 0,
                'reasons' => '',
                'remark' => $remark,
                'created_at' => date('Y-m-d H:i:s'),
            ];

            $log_id = $this->addCardBalanceChangeLogs($insert);
            $this->updateCard(['balance' => $balance], ['card_no' => $card_no]);
            $this->commitTrans();
            return [
                'card_no' => $card_no,
                'balance_changed' => $balance_changed,
                'balance' => $balance,
                'trade_no' => $trade_no,
                'log_id' => $log_id
            ];
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionLog("修改卡余额失败: " . $e->getMessage());
            actionException($e, 1);
            return $this->r(100, $this->lang("VCard.balance_action_fail") .'：'. $e->getMessage());
        }
    }
}
