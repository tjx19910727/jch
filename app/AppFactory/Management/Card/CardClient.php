<?php
/**
 * Created by VSCode.
 * User: Alex-jixiang
 * Date: 2025/12/08
 * Time: 15:42
 */

namespace app\AppFactory\Management\Card;


use app\AppFactory\Kernel\Traits\Card\CardTrait;
use app\AppFactory\Kernel\Support\Excel;

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
        return $this->changePoints($card_no, $change_points, $change_type, $oredr_id, $reason, $bind_id);
    }

    public function importCards($data){
        try {
            $now_cards_lists = $this->getCardList([['card_no', '>', '0']])->toArray();
            $card_no_arr = array_column($now_cards_lists,'card_no');
            $update_card_lists = $this->getCardList(['card_show_no' => null])->toArray();
            $update_card_no_arr = array_column($update_card_lists,'card_no');

            $path = root_path() . "public" . $data['file_path'];
            $title = ["card_show_no", "card_no"];
            $cards = Excel::importExcel($path, $title);
            $import_cards = [];
            $result = true;
            foreach($cards as $v){
                if(in_array($v['card_no'], $update_card_no_arr)) {
                    $update_card['card_show_no'] = intval($v['card_show_no']); 
                    $update_card['card_show_no'] = str_pad($update_card['card_show_no'], 10, "0", STR_PAD_LEFT);
                    $result = $this->updateCard(['card_show_no' => $update_card['card_show_no']], ['card_no' => intval($v['card_no'])] );
                }elseif(!in_array($v['card_no'], $card_no_arr)){
                    $import_card['card_no'] = intval($v['card_no']); 
                    $import_card['card_show_no'] = intval($v['card_show_no']); 
                    $import_card['card_show_no'] = str_pad($import_card['card_show_no'], 10, "0", STR_PAD_LEFT);
                    $import_cards[] = $import_card;
                }
            }

            if (is_object($import_cards)) return $import_cards;
            actionLog($import_cards, '导入的卡数据');
            // dd($import_cards);
            if ($import_cards) {
                $result = $this->addCardLists($import_cards);
            }
            return $this->rAction($result);

            return $this->r(100, '获取不到Excel文档中的数据');
        } catch (\Exception $e) {
            actionException($e, 1);
            return $this->rValidate($e->getMessage());
        }
    }

    public function changeCardBalance($data)
    {
        return $this->changeBalance($data);
    }

    public function changeCardPwd($data)
    {
        if($data['password'] !== $data['confirm_password']) {
            return $this->r(101, $this->lang('VCard.password_not_match'));
        }
        $where['card_no'] = $data['card_no'] ?? 0;
        $card = $this->getCardCount($where);
        if(!$card){
            return $this->r(101, $this->lang('VCard.card_no_no_data'));
        }
        return $this->updatePwd($data);
    }
}