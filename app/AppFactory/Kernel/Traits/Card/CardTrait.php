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

trait CardTrait
{
    use WcBaseTrait;
    use ReturnTrait;
    use \app\AppFactory\Kernel\Traits\Card\CardBalanceBucketsTrait;


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

    /**
     * 获取当前可用余额汇总（实时按有效期过滤）
     * @param string $card_no
     * @return array
     */
    public function getCardBalanceSummary($card_no)
    {
        $now = time();
        $row = $this->getCardBalanceBucketSummaryRow($card_no, $now);

        return [
            'available_balance' => number_format($row['available_balance'] ?? 0, 2, '.', ''),
            'principal_balance' => number_format($row['principal_balance'] ?? 0, 2, '.', ''),
            'gift_balance' => number_format($row['gift_balance'] ?? 0, 2, '.', ''),
            'refundable_balance' => number_format($row['refundable_balance'] ?? 0, 2, '.', ''),
        ];
    }

    /**
     * 新增余额分笔记录
     * @param array $insert
     * @return int|string
     */
    public function addCardBalanceBucket($insert)
    {
        return $this->insertCardBalanceBucket($insert);
    }

    /**
     * 扣减余额分笔（先到期先扣）
     * @param string $card_no
     * @param float|string $amount
     * @return array
     * @throws \Exception
     */
    public function consumeCardBalanceBuckets($card_no, $amount)
    {
        $amount = number_format((float)$amount, 2, '.', '');
        if (bccomp($amount, '0', 2) <= 0) {
            return [
                'allocations' => [],
                'consumed_amount' => '0.00',
            ];
        }

        $now = time();
        $rows = $this->getCardBalanceBucketsForConsume($card_no, $now);

        $remainNeed = $amount;
        $allocations = [];

        foreach ($rows as $row) {
            if (bccomp($remainNeed, '0', 2) <= 0) {
                break;
            }
            $currentRemain = number_format((float)($row['remain_amount'] ?? 0), 2, '.', '');
            if (bccomp($currentRemain, '0', 2) <= 0) {
                continue;
            }

            $consume = bccomp($currentRemain, $remainNeed, 2) >= 0 ? $remainNeed : $currentRemain;
            $afterRemain = bcsub($currentRemain, $consume, 2);

            $this->updateCardBalanceBucketRemain($row['id'], $afterRemain);

            $allocations[] = [
                'bucket_id' => intval($row['id']),
                'amount' => $consume,
                'amount_type' => intval($row['amount_type'] ?? 1),
                'expire_at' => intval($row['expire_at'] ?? 0),
            ];
            $remainNeed = bcsub($remainNeed, $consume, 2);
        }

        if (bccomp($remainNeed, '0', 2) > 0) {
            throw new \Exception($this->lang("VCard.balance_not_enough"));
        }

        return [
            'allocations' => $allocations,
            'consumed_amount' => $amount,
        ];
    }

    /**
     * 按扣减分摊明细回补余额分笔（用于退款）
     * @param string $card_no
     * @param array $allocations
     * @param float|string $refundAmount
     * @return array
     */
    public function restoreCardBalanceBucketsByAllocations($card_no, $allocations, $refundAmount)
    {
        $refundAmount = number_format((float)$refundAmount, 2, '.', '');
        $remain = $refundAmount;
        $restored = [];

        if (!is_array($allocations)) {
            $allocations = [];
        }

        foreach ($allocations as $allocation) {
            if (bccomp($remain, '0', 2) <= 0) {
                break;
            }
            $bucketId = intval($allocation['bucket_id'] ?? 0);
            $allocAmount = number_format((float)($allocation['amount'] ?? 0), 2, '.', '');
            if ($bucketId <= 0 || bccomp($allocAmount, '0', 2) <= 0) {
                continue;
            }

            $restoreAmount = bccomp($allocAmount, $remain, 2) >= 0 ? $remain : $allocAmount;

            $row = $this->findCardBalanceBucketForUpdate($bucketId, $card_no);

            if ($row) {
                $currentRemain = number_format((float)($row['remain_amount'] ?? 0), 2, '.', '');
                $totalAmount = number_format((float)($row['total_amount'] ?? 0), 2, '.', '');
                $afterRemain = bcadd($currentRemain, $restoreAmount, 2);
                if (bccomp($afterRemain, $totalAmount, 2) > 0) {
                    $afterRemain = $totalAmount;
                }
                $actualRestore = bcsub($afterRemain, $currentRemain, 2);
                if (bccomp($actualRestore, '0', 2) > 0) {
                    $this->updateCardBalanceBucketRemain($bucketId, $afterRemain);
                    $restored[] = [
                        'bucket_id' => $bucketId,
                        'amount' => $actualRestore,
                    ];
                    $remain = bcsub($remain, $actualRestore, 2);
                }
            }
        }

        // 回补有剩余时，补到一条新的本金永久余额分笔，避免退款金额丢失
        if (bccomp($remain, '0', 2) > 0) {
            $newId = $this->addCardBalanceBucket([
                'card_no' => $card_no,
                'batch_no' => 'RF' . date('YmdHis') . mt_rand(1000, 9999),
                'source_type' => 'order_refund',
                'source_no' => '',
                'amount_type' => 1,
                'refund_eligible' => 1,
                'total_amount' => $remain,
                'remain_amount' => $remain,
                'expire_at' => 0,
            ]);
            $restored[] = [
                'bucket_id' => intval($newId),
                'amount' => $remain,
            ];
            $remain = '0.00';
        }

        return [
            'restored' => $restored,
            'refund_amount' => $refundAmount,
        ];
    }

    // 余额变化接口
    public function changeBalance($data)
    {
        $card_no = $data['card_no'] ?? '';
        $balance_changed = number_format((float)($data['balance_changed'] ?? 0), 2, '.', '');
        $gift_balance = number_format((float)($data['gift_balance'] ?? 0), 2, '.', '');
        $change_type = $data['change_type'] ?? 0;
        $trade_no = $data['trade_no'] ?? ('CB' . date('YmdHis') . mt_rand(1000, 9999));
        $remark = $data['remark'] ?? '';
        $use_expire = intval($data['use_expire'] ?? 0);
        $expire_at = intval($data['expire_at'] ?? 0);

        try {
            $this->startTrans();
            $card = $this->getCardFind(['card_no' => $card_no], 'card_no');
            if (!$card) {
                return $this->r(100, $this->lang("VCard.card_no_no_data"));
            }

            if ($use_expire === 1 && $expire_at <= 0) {
                $this->rollbackTrans();
                return $this->r(100, $this->lang("VCard.expire_at_require"));
            }

            $finalExpireAt = $use_expire === 1 ? $expire_at : 0;

            $summaryBefore = $this->getCardBalanceSummary($card_no);
            $balance_before_change = $summaryBefore['available_balance'];
            $deltaAmount = '0.00';
            $remarkData = [
                'remark' => $remark,
                'use_expire' => $use_expire,
                'expire_at' => $finalExpireAt,
                'buckets' => [],
            ];

            if ($change_type == 1) { // 增加
                if (bccomp($balance_changed, '0', 2) <= 0 && bccomp($gift_balance, '0', 2) <= 0) {
                    $this->rollbackTrans();
                    return $this->r(100, $this->lang("VCard.balance_amount_error"));
                }
                $batchNo = 'RC' . date('YmdHis') . mt_rand(1000, 9999);
                if (bccomp($balance_changed, '0', 2) > 0) {
                    $bucketId = $this->addCardBalanceBucket([
                        'card_no' => $card_no,
                        'batch_no' => $batchNo,
                        'source_type' => 'recharge',
                        'source_no' => $trade_no,
                        'amount_type' => 1,
                        'refund_eligible' => 1,
                        'total_amount' => $balance_changed,
                        'remain_amount' => $balance_changed,
                        'expire_at' => $finalExpireAt,
                    ]);
                    $remarkData['buckets'][] = [
                        'bucket_id' => intval($bucketId),
                        'amount' => $balance_changed,
                        'amount_type' => 1,
                    ];
                    $deltaAmount = bcadd($deltaAmount, $balance_changed, 2);
                }
                if (bccomp($gift_balance, '0', 2) > 0) {
                    $bucketId = $this->addCardBalanceBucket([
                        'card_no' => $card_no,
                        'batch_no' => $batchNo,
                        'source_type' => 'gift',
                        'source_no' => $trade_no,
                        'amount_type' => 2,
                        'refund_eligible' => 0,
                        'total_amount' => $gift_balance,
                        'remain_amount' => $gift_balance,
                        'expire_at' => $finalExpireAt,
                    ]);
                    $remarkData['buckets'][] = [
                        'bucket_id' => intval($bucketId),
                        'amount' => $gift_balance,
                        'amount_type' => 2,
                    ];
                    $deltaAmount = bcadd($deltaAmount, $gift_balance, 2);
                }
            } elseif ($change_type == 2) { // 减少
                if (bccomp($balance_changed, '0', 2) <= 0) {
                    $this->rollbackTrans();
                    return $this->r(100, $this->lang("VCard.balance_amount_error"));
                }
                $consumeResult = $this->consumeCardBalanceBuckets($card_no, $balance_changed);
                $remarkData['allocations'] = $consumeResult['allocations'];
                $deltaAmount = $balance_changed;
            } else {
                $this->rollbackTrans();
                return $this->r(100, $this->lang("VCard.change_type_in"));
            }

            $summaryAfter = $this->getCardBalanceSummary($card_no);
            $balance = $summaryAfter['available_balance'];

            $insert = [
                'card_no' => $card_no,
                'balance_before_change' => $balance_before_change,
                'balance_changed' => $deltaAmount,
                'balance' => $balance,
                'change_type' => $change_type,
                'balance_type' => 2,//后台充值
                'trade_no' => $trade_no,
                'activity_id' => 0,
                'reasons' => '',
                'remark' => json_encode($remarkData, JSON_UNESCAPED_UNICODE),
                'created_at' => date('Y-m-d H:i:s'),
            ];

            $log_id = $this->addCardBalanceChangeLogs($insert);
            $this->commitTrans();
            return [
                'card_no' => $card_no,
                'balance_changed' => $deltaAmount,
                'balance' => $balance,
                'principal_balance' => $summaryAfter['principal_balance'],
                'gift_balance' => $summaryAfter['gift_balance'],
                'refundable_balance' => $summaryAfter['refundable_balance'],
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

    public function updatePwd($data)
    {
        $update['password'] = md5($data['password'] . $this->salt);
        $where['card_no'] = $data['card_no'];
        return CardModel::update($update,$where);
    }
}
