<?php
/**
 * Created by VSCode.
 * User: Alex-jixiang
 * Date: 2025/12/08
 * Time: 15:42
 */

namespace app\AppFactory\Management\Card;


use app\AppFactory\Kernel\Traits\Card\CardTrait;

use app\AppFactory\Management\ManagementClient;
class CardClient extends ManagementClient 
{
    use CardTrait;

    public function getCardInfoList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return $this->rQ($this->getCardList($where, $pageNum, $field, $order, $eachFun, $group));
    }

    public function addCardInfo($postData)
    {
        return $this->rA($this->addCard($postData));
    }

    public function updateCardInfo($update, $where = [], $field = [])
    {
        return $this->rU($this->updateCard($update, $where, $field));
    }

    public function delCardInfo($where)
    {
        return $this->rD($this->delCard($where));
    }

    public function getCardPointsChangeLogsInfoList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return $this->rQ($this->getCardPointsChangeLogsList($where, $pageNum, $field, $order, $eachFun, $group));
    }
    
    public function addCardPointsChangeLogsInfo($postData)
    {
        return $this->rA($this->addCardPointsChangeLogs($postData));
    }

    public function updateCardPointsChangeLogsInfo($update, $where = [], $field = [])
    {
        return $this->rU($this->updateCardPointsChangeLogs($update, $where, $field));
    }

    public function delCardPointsChangeLogsInfo($where)
    {
        return $this->rD($this->delCardPointsChangeLogs($where));
    } 

    public function changeCardPoints($card_no, $change_points, $change_type, $oredr_id = '', $reason = '', $bind_id = ''){
        $res = $this->changePoints($card_no, $change_points, $change_type, $oredr_id, $reason, $bind_id);
        if($bind_id){
            $this->app->api
        }
        return $this->rA();
    }
}