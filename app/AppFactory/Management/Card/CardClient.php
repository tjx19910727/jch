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
        $list = $this->getCardList($where, $pageNum, $field, $order, function ($item) {
            $summary = $this->getCardBalanceSummary($item['card_no']);
            $item['available_balance'] = $summary['available_balance'];
            $item['principal_balance'] = $summary['principal_balance'];
            $item['gift_balance'] = $summary['gift_balance'];
            $item['expire_balance'] = $summary['expire_balance'];
            $item['refundable_balance'] = $summary['refundable_balance'];
            // 新增明确语义字段
            $item['available_balance_no_gift'] = $summary['principal_balance'];
            $item['total_available_balance'] = $summary['available_balance'];
            // 对外兼容字段，余额展示为实时可用余额
            $item['balance'] = $item['total_available_balance'];
            return $item;
        }, $group);
        return $this->rQ($list);
    }

    public function addCardInfo($postData)
    {
        return $this->rA($this->addCard($postData));
    }

    public function addSingleCard($postData)
    {
        try {
            $card_no = $postData['card_no'] ?? '';
            $card_show_no = $postData['card_show_no'] ?? '';
            $count = $this->getCardCount(['card_no' => $card_no]);
            if ($count) {
                return $this->r(100, lang('VCard.card_no_exists'));
            }
            $show_count = $this->getCardCount(['card_show_no' => $card_show_no]);
            if ($show_count) {
                return $this->r(100, lang('VCard.card_show_no_exists'));
            }

            $insert = [
                'card_no' => $card_no,
                'card_show_no' => $card_show_no,
                'name' => $postData['name'] ?? '',
                'status' => $postData['status'] ?? 0,
                'password' => '123456'.md5(config('app.salt') . $card_no),
            ];
            return $this->rA($this->addCard($insert));
        } catch (\Exception $e) {
            actionException($e, 1);
            return $this->rValidate($e->getMessage());
        }
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
            $title = ["card_show_no", "card_no", "name", "balance", "gift_balance", "expire_at", "status"];
            // 当前导入模板不包含标题行，需从第1行开始读取，避免首行数据丢失
            $cards = Excel::importExcel($path, $title, [], 1);
            $import_cards = [];
            $result = true;
            $recharge_data = [];
            foreach($cards as $v){
                $cardNo = intval($v['card_no'] ?? 0);
                $cardShowNo = intval($v['card_show_no'] ?? 0);
                $name = trim((string)($v['name'] ?? ''));
                $balance = number_format((float)($v['balance'] ?? 0), 2, '.', '');
                $giftBalance = number_format((float)($v['gift_balance'] ?? 0), 2, '.', '');
                $expireAt = intval($v['expire_at'] ?? 0);
                $status = intval($v['status'] ?? 0) === 1 ? 1 : 0;

                if(in_array($v['card_no'], $update_card_no_arr)) {
                    $update_card['card_show_no'] = $cardShowNo;
                    $update_card['card_show_no'] = str_pad($update_card['card_show_no'], 10, "0", STR_PAD_LEFT);
                    $update_card['name'] = $name;
                    $update_card['status'] = $status;
                    if ($status === 1) {
                        $update_card['activation_time'] = time();
                    }
                    $result = $this->updateCard([
                        'card_show_no' => $update_card['card_show_no'],
                        'name' => $update_card['name'],
                        'status' => $update_card['status'],
                        'activation_time' => $update_card['activation_time'] ?? 0,
                    ], ['card_no' => $cardNo] );
                }elseif(!in_array($v['card_no'], $card_no_arr)){
                    $import_card['card_no'] = $cardNo;
                    $import_card['card_show_no'] = $cardShowNo;
                    $import_card['card_show_no'] = str_pad($import_card['card_show_no'], 10, "0", STR_PAD_LEFT);
                    $import_card['name'] = $name;
                    $import_card['status'] = $status;
                    $import_card['activation_time'] = $status === 1 ? time() : 0;
                    $import_card['password'] = '123456'.md5(config('app.salt') . $cardNo);
                    $import_cards[] = $import_card;
                }

                $recharge_data[] = [
                    'card_no' => (string)$cardNo,
                    'balance_changed' => $balance,
                    'gift_balance' => $giftBalance,
                    'expire_at' => $expireAt,
                ];
            }

            if (is_object($import_cards)) return $import_cards;
            actionLog($import_cards, '导入的卡数据');
            // dd($import_cards);
            if ($import_cards) {
                $result = $this->addCardLists($import_cards);
            }

            // 按新余额体系导入本金/赠送/有效期
            if ($recharge_data) {
                $idx = 0;
                foreach ($recharge_data as $row) {
                    $idx++;
                    if (bccomp($row['balance_changed'], '0', 2) == 0 && bccomp($row['gift_balance'], '0', 2) == 0) {
                        continue;
                    }
                    $this->changeBalance([
                        'card_no' => $row['card_no'],
                        'balance_changed' => $row['balance_changed'],
                        'gift_balance' => $row['gift_balance'],
                        'change_type' => 1,
                        'trade_no' => 'IMP' . date('YmdHis') . str_pad((string)$idx, 4, '0', STR_PAD_LEFT),
                        'remark' => 'Excel导入余额',
                        'use_expire' => $row['expire_at'] > 0 ? 1 : 0,
                        'expire_at' => $row['expire_at'],
                    ]);
                }
            }

            return $this->rAction($result);

            return $this->r(100, '获取不到Excel文档中的数据');
        } catch (\Exception $e) {
            actionException($e, 1);
            return $this->rValidate($e->getMessage());
        }
    }

    public function exportCards($where)
    {
        $field = 'card_show_no,card_no,name,points,status';
        $list = $this->getCardList($where, 0, $field, 'card_no desc');
        if ($list) {
            $list = $list->toArray();
            if ($list) {
                foreach ($list as $key => $item) {
                    $summary = $this->getCardBalanceSummary($item['card_no']);
                    $item['balance'] = $summary['available_balance'];
                    $item['principal_balance'] = $summary['principal_balance'];
                    $item['gift_balance'] = $summary['gift_balance'];
                    $item['refundable_balance'] = $summary['refundable_balance'];
                    $item['status'] = $item['status'] == 1 ? '激活' : '未激活';
                    $list[$key] = $item;
                }
                $title = [
                    'card_show_no' => '卡面号',
                    'card_no' => '芯片号',
                    'name' => '姓名',
                    'balance' => '可用余额',
                    'principal_balance' => '本金余额',
                    'gift_balance' => '赠送余额',
                    'refundable_balance' => '可退余额',
                    'points' => '积分',
                    'status' => '状态',
                ];
                $filename = '导出卡信息_' . date('YmdHis');
                return $this->sendToExport('卡管理-卡列表', $filename, $title, $list);
            }
        }
        return $this->rFail($this->lang('query_fail'));
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

    public function getCardBalanceLogsList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return $this->rQ($this->getCardBalanceChangeLogsList($where, $pageNum, $field, $order, $eachFun, $group));
    }
}