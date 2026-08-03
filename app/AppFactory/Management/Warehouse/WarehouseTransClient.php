<?php

namespace app\AppFactory\Management\Warehouse;

use app\AppFactory\Kernel\Model\Goods\GoodsModel;
use app\AppFactory\Kernel\Traits\Warehouse\WarehouseTransTrait;
use app\AppFactory\Management\ManagementClient;
use think\facade\Db;

class WarehouseTransClient extends ManagementClient
{
    use WarehouseTransTrait;

    protected $typeNames = [
        1 => '入库',
        2 => '出库',
        3 => '预补货退料',
        4 => '预补货出库',
    ];

    /**
     * 创建仓库变化单，并在同一事务内更新 goods.stocks。
     */
    public function createTrans($postData)
    {
        $normalized = $this->normalizeCreateData($postData);
        if (!is_array($normalized)) return $normalized;

        $aoId = intval($this->manager['ao_id'] ?? 0);
        $existing = $this->findByIdempotencyKey($normalized['idempotency_key'], $aoId);
        if ($existing) {
            return $this->r(200, '请求已处理', $this->formatTrans($existing));
        }

        $namedLockAcquired = false;
        Db::startTrans();
        try {
            $namedLockAcquired = $this->acquireTransNoLock();
            if (!$namedLockAcquired) throw new \Exception('仓库单号生成繁忙，请稍后重试');

            // 名锁等待期间可能已由另一请求完成，锁内必须再次检查。
            $existing = $this->findByIdempotencyKey($normalized['idempotency_key'], $aoId);
            if ($existing) {
                Db::commit();
                return $this->r(200, '请求已处理', $this->formatTrans($existing));
            }

            $prePlanMap = [];
            if (in_array($normalized['type'], [3, 4], true)) {
                $prePlanMap = $this->getPreReplenishmentPlan($normalized['record_no'], $aoId);
            }

            $transId = $this->generateTransId();
            $now = date('Y-m-d H:i:s');
            $detailRows = [];
            $totalChanged = 0;
            $goodsIds = array_keys($normalized['details']);
            sort($goodsIds, SORT_NUMERIC);

            foreach ($goodsIds as $goodsId) {
                $requestDetail = $normalized['details'][$goodsId];
                $goods = GoodsModel::where(['g_id' => $goodsId])
                    ->field('g_id,g_name,sku,bar_code,stocks,status')
                    ->lock(true)
                    ->find();
                if (!$goods) throw new \Exception('商品ID ' . $goodsId . ' 不存在');
                $goods = $goods->toArray();

                $quantity = intval($requestDetail['quantity']);
                $changed = in_array($normalized['type'], [1, 3], true) ? $quantity : -$quantity;
                $sourceStocks = intval($goods['stocks'] ?? 0);
                $nowStocks = $sourceStocks + $changed;
                if ($nowStocks < 0) {
                    throw new \Exception('商品 ' . ($goods['g_name'] ?: $goodsId) . ' 库存不足，当前库存为' . $sourceStocks);
                }

                if (in_array($normalized['type'], [3, 4], true)) {
                    $this->validatePreReplenishmentQuantity(
                        $normalized['type'],
                        $normalized['record_no'],
                        $goods,
                        $quantity,
                        $prePlanMap
                    );
                }

                $updated = GoodsModel::where(['g_id' => $goodsId, 'stocks' => $sourceStocks])
                    ->update(['stocks' => $nowStocks]);
                if (!$updated) throw new \Exception('商品 ' . ($goods['g_name'] ?: $goodsId) . ' 库存更新失败');

                $detailRows[] = [
                    'trans_id' => $transId,
                    'goods_id' => $goodsId,
                    'sku' => strval($goods['sku'] ?? ''),
                    'goods_name' => strval($goods['g_name'] ?? ''),
                    'bar_code' => strval($goods['bar_code'] ?? ''),
                    'source_stocks' => $sourceStocks,
                    'changed' => $changed,
                    'now_stocks' => $nowStocks,
                    'remark' => $requestDetail['remark'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $totalChanged += $quantity;
            }

            $mainId = Db::name('warehouse_trans')->insertGetId([
                'trans_id' => $transId,
                'idempotency_key' => $normalized['idempotency_key'],
                'ao_id' => $aoId,
                'type' => $normalized['type'],
                'record_no' => $normalized['record_no'] ?: null,
                'business_at' => $normalized['business_at'],
                'total_changed' => $totalChanged,
                'manager_id' => intval($this->manager['manager_id'] ?? 0),
                'manager_name' => strval($this->manager['nickname'] ?? ($this->manager['account'] ?? '')),
                'remark' => $normalized['remark'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            if (!$mainId) throw new \Exception('仓库变化主记录写入失败');

            foreach ($detailRows as &$detailRow) $detailRow['warehouse_trans_id'] = $mainId;
            unset($detailRow);
            if (Db::name('warehouse_trans_details')->insertAll($detailRows) !== count($detailRows)) {
                throw new \Exception('仓库变化明细写入失败');
            }

            Db::commit();
            return $this->r(200, '操作成功', [
                'id' => intval($mainId),
                'trans_id' => $transId,
                'type' => $normalized['type'],
                'type_text' => $this->typeNames[$normalized['type']],
                'total_changed' => $totalChanged,
                'details' => $detailRows,
            ]);
        } catch (\Exception $e) {
            Db::rollback();
            actionException($e, 1);
            return $this->r(100, $e->getMessage());
        } finally {
            if ($namedLockAcquired) $this->releaseTransNoLock();
        }
    }

    public function getTransList($where = [], $pageNum = 0)
    {
        $field = 'id,trans_id,ao_id,type,record_no,business_at,total_changed,manager_id,manager_name,remark,created_at,updated_at';
        $list = $this->getWarehouseTransList($where, $pageNum, $field, 'id desc');
        if (is_object($list) && method_exists($list, 'each')) {
            $list->each(function ($item) {
                $item['type_text'] = $this->typeNames[intval($item['type'])] ?? '未知';
                return $item;
            });
        }
        return $this->rQ($list);
    }

    public function getTransFind($where)
    {
        $trans = $this->getWarehouseTransFind($where);
        if (!$trans) return $this->r(100, '仓库变化记录不存在');
        $trans = $this->formatTrans($trans);
        return $this->rQ($trans);
    }

    protected function normalizeCreateData($postData)
    {
        $type = intval($postData['type'] ?? 0);
        $recordNo = trim(strval($postData['record_no'] ?? ''));
        if (in_array($type, [3, 4], true) && $recordNo === '') {
            return $this->r(100, '预补货退料或出库时record_no必填');
        }
        if (in_array($type, [1, 2], true)) $recordNo = '';

        $details = $postData['details'] ?? [];
        if (!is_array($details) || !$details) return $this->r(100, '商品明细不能为空');
        if (count($details) > 500) return $this->r(100, '单次商品明细不能超过500条');

        $normalizedDetails = [];
        foreach ($details as $index => $detail) {
            if (!is_array($detail)) return $this->r(100, '第' . ($index + 1) . '条商品明细格式错误');
            $goodsId = intval($detail['goods_id'] ?? 0);
            $quantityRaw = $detail['quantity'] ?? null;
            if ($goodsId <= 0) return $this->r(100, '第' . ($index + 1) . '条商品ID格式错误');
            if (!is_numeric($quantityRaw) || floatval($quantityRaw) != intval($quantityRaw) || intval($quantityRaw) <= 0) {
                return $this->r(100, '第' . ($index + 1) . '条商品数量必须为大于0的整数');
            }
            if (isset($normalizedDetails[$goodsId])) {
                return $this->r(100, '同一单据中商品ID ' . $goodsId . ' 不能重复');
            }
            $remark = trim(strval($detail['remark'] ?? ''));
            if (mb_strlen($remark, 'UTF-8') > 255) return $this->r(100, '明细备注长度不能超过255');
            $normalizedDetails[$goodsId] = [
                'quantity' => intval($quantityRaw),
                'remark' => $remark === '' ? null : $remark,
            ];
        }

        $businessAt = trim(strval($postData['business_at'] ?? ''));
        if ($businessAt === '') {
            $businessAt = date('Y-m-d H:i:s');
        } else {
            $time = strtotime($businessAt);
            if (!$time) return $this->r(100, '业务发生时间格式错误');
            $businessAt = date('Y-m-d H:i:s', $time);
        }

        return [
            'type' => $type,
            'record_no' => $recordNo,
            'idempotency_key' => trim(strval($postData['idempotency_key'] ?? '')),
            'business_at' => $businessAt,
            'remark' => trim(strval($postData['remark'] ?? '')) ?: null,
            'details' => $normalizedDetails,
        ];
    }

    protected function getPreReplenishmentPlan($recordNo, $aoId)
    {
        $where = ['record_no' => $recordNo];
        if ($aoId > 1) $where['ao_id'] = $aoId;
        $order = Db::name('pre_replenishment_order')->where($where)->field('id,record_no,ao_id')->find();
        if (!$order) throw new \Exception('预补货单不存在或无权操作');

        $rows = Db::name('pre_replenishment_detail')
            ->where(['order_id' => intval($order['id'])])
            ->field('sku,SUM(plan_quantity) plan_quantity')
            ->group('sku')
            ->select()
            ->toArray();
        $map = [];
        foreach ($rows as $row) $map[strval($row['sku'])] = intval($row['plan_quantity']);
        return $map;
    }

    protected function validatePreReplenishmentQuantity($type, $recordNo, $goods, $quantity, $planMap)
    {
        $sku = strval($goods['sku'] ?? '');
        $planQuantity = intval($planMap[$sku] ?? 0);
        if ($planQuantity <= 0) throw new \Exception('商品SKU ' . $sku . ' 不在该预补货单中');

        $netChanged = intval(Db::name('warehouse_trans_details')->alias('d')
            ->join('warehouse_trans t', 't.id = d.warehouse_trans_id')
            ->where('t.record_no', $recordNo)
            ->whereIn('t.type', [3, 4])
            ->where('d.goods_id', intval($goods['g_id']))
            ->sum('d.changed'));
        $outstanding = max(0, -$netChanged);

        if ($type === 4 && $outstanding + $quantity > $planQuantity) {
            throw new \Exception('商品SKU ' . $sku . ' 预补货出库累计数量不能超过计划数量' . $planQuantity);
        }
        if ($type === 3 && $quantity > $outstanding) {
            throw new \Exception('商品SKU ' . $sku . ' 退料数量不能超过已出未退数量' . $outstanding);
        }
    }

    protected function findByIdempotencyKey($key, $aoId)
    {
        $where = ['idempotency_key' => $key];
        if ($aoId > 1) $where['ao_id'] = $aoId;
        return $this->getWarehouseTransFind($where);
    }

    protected function formatTrans($trans)
    {
        if (is_object($trans) && method_exists($trans, 'toArray')) $trans = $trans->toArray();
        $trans['type_text'] = $this->typeNames[intval($trans['type'])] ?? '未知';
        $details = $this->getWarehouseTransDetailsList(['warehouse_trans_id' => intval($trans['id'])]);
        $trans['details'] = $details ? $details->toArray() : [];
        return $trans;
    }

    protected function acquireTransNoLock()
    {
        $result = Db::query("SELECT GET_LOCK('warehouse_trans_no', 3) AS lock_result");
        return !empty($result) && intval($result[0]['lock_result'] ?? 0) === 1;
    }

    protected function releaseTransNoLock()
    {
        try {
            Db::query("SELECT RELEASE_LOCK('warehouse_trans_no')");
        } catch (\Exception $e) {
            actionException($e, 1);
        }
    }

    protected function generateTransId()
    {
        for ($i = 0; $i < 20; $i++) {
            $transId = 'G' . date('YmdHis');
            if (!Db::name('warehouse_trans')->where(['trans_id' => $transId])->count()) return $transId;
            usleep(100000);
        }
        throw new \Exception('仓库单号生成失败，请重试');
    }
}
