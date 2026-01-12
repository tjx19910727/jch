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
use think\facade\Db;

trait CardTrait
{
    public function getCardCount($where, $field = '*', $order = '')
    {
        return CardModel::getFind($where, $field, $order);
    }

    public function getCardList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return CardModel::getList($where, $pageNum, $field, $order, $eachFun, $group);
    }

    public function getCardFind($where,$field = "*",$order = "")
    {
        return CardModel::getFind($where, $field, $order);
    }

    public function getCardSum($where, $sum)
    {
        return CardModel::getSum($where, $sum);
    }

    public function addCard($insert)
    {
        !isset($this->manager['manager_id']) ? :$insert['creator'] = $this->manager['manager_id'];
        $data = CardModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    public function updateCard($update, $where = [], $field = [])
    {
        !isset($this->manager['manager_id']) ? : $update['updator'] = $this->manager['manager_id'];
        return CardModel::update($update, $where, $field);
    }

    public function delCard($where)
    {
        return CardModel::whereDel($where);
    }

    public function getCardPointsChangeLogsCount($where, $field = '*', $order = '')
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
        try{
            $this->startTrans();
            $card = $this->getCardFind(['card_no' => $card_no], 'card_no,points');
            if(!$card)  $this->addCard(['card_no' => $card_no, 'points' => $points_changed]);

            $points_before_change = $card['points'] ?? 0;
            $points = 0;
            if($change_type == 1) $points = $points_before_change + $points_changed;
            if($change_type == 2) $points = $points_before_change - $points_changed;
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
            return ['card_no' => $card_no, 'points_changed' => $points_changed, 'points' => $points, 'trade_no' => $trade_no];
        } catch (\Exception $e) {
            return false;
            $this->rollbackTrans();
            actionLog("修改卡积分失败");
            actionException($e, 1);
        }
        return $log_id;

    }
    
}
