<?php

namespace app\AppFactory\Kernel\Traits\Card;

use app\AppFactory\Kernel\Model\Card\CardBalanceBucketsModel;

trait CardBalanceBucketsTrait
{
    /**
     * 余额分笔表名
     * @return string
     */
    protected function getCardBalanceBucketTable()
    {
        return 'card_balance_buckets';
    }

    /**
     * 统计卡分笔余额汇总
     * @param string $card_no
     * @param int $now
     * @return array|null
     */
    protected function getCardBalanceBucketSummaryRow($card_no, $now)
    {
        $now = intval($now);
        return CardBalanceBucketsModel::where('card_no', $card_no)
            ->fieldRaw("IFNULL(SUM(CASE WHEN (expire_at = 0 OR expire_at >= {$now}) THEN remain_amount ELSE 0 END),0) AS available_balance, IFNULL(SUM(CASE WHEN amount_type = 1 AND (expire_at = 0 OR expire_at >= {$now}) THEN remain_amount ELSE 0 END),0) AS principal_balance, IFNULL(SUM(CASE WHEN amount_type = 2 AND (expire_at = 0 OR expire_at >= {$now}) THEN remain_amount ELSE 0 END),0) AS gift_balance, IFNULL(SUM(CASE WHEN expire_at > 0 AND expire_at >= {$now} THEN remain_amount ELSE 0 END),0) AS expire_balance, IFNULL(SUM(CASE WHEN refund_eligible = 1 AND (expire_at = 0 OR expire_at >= {$now}) THEN remain_amount ELSE 0 END),0) AS refundable_balance")
            ->find();
    }

    /**
     * 新增分笔
     * @param array $insert
     * @return int|string
     */
    protected function insertCardBalanceBucket($insert)
    {
        $insert['created_at'] = $insert['created_at'] ?? date('Y-m-d H:i:s');
        $insert['updated_at'] = $insert['updated_at'] ?? date('Y-m-d H:i:s');
        return CardBalanceBucketsModel::insertGetId($insert);
    }

    /**
     * 获取可扣减分笔并加锁
     * @param string $card_no
     * @param int $now
     * @return array
     */
    protected function getCardBalanceBucketsForConsume($card_no, $now, $amountType = null)
    {
        $query = CardBalanceBucketsModel::where('card_no', $card_no)
            ->where('remain_amount', '>', 0)
            ->whereRaw("(expire_at = 0 OR expire_at >= {$now})")
            ->orderRaw("CASE WHEN expire_at = 0 THEN 1 ELSE 0 END ASC, expire_at ASC, amount_type ASC, id ASC");

        if ($amountType !== null) {
            $query->where('amount_type', intval($amountType));
        }

        return $query->lock(true)
            ->select()
            ->toArray();
    }

    /**
     * 更新分笔剩余金额
     * @param int $id
     * @param string $remainAmount
     * @return int
     */
    protected function updateCardBalanceBucketRemain($id, $remainAmount)
    {
        return CardBalanceBucketsModel::where('id', $id)
            ->update([
                'remain_amount' => $remainAmount,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    /**
     * 按id和卡号查分笔并加锁
     * @param int $bucketId
     * @param string $card_no
     * @return array|null
     */
    protected function findCardBalanceBucketForUpdate($bucketId, $card_no)
    {
        return CardBalanceBucketsModel::where('id', $bucketId)
            ->where('card_no', $card_no)
            ->lock(true)
            ->find();
    }
}
