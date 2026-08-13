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
        $namedLockAcquired = false;
        Db::startTrans();
        try {
            $namedLockAcquired = $this->acquireTransNoLock();
            if (!$namedLockAcquired) throw new \Exception('仓库单号生成繁忙，请稍后重试');

            $materialManager = $this->getMaterialManager($normalized['material_manager_id'], $aoId);

            $prePlanMap = [];
            $preOrderId = 0;
            if (in_array($normalized['type'], [3, 4], true)) {
                $preData = $this->getPreReplenishmentData(
                    $normalized['type'],
                    $normalized['record_no'],
                    $aoId,
                    $normalized['details']
                );
                $normalized['details'] = $preData['details'];
                $prePlanMap = $preData['plan_map'];
                $preOrderId = intval($preData['order_id']);
            }

            $transId = $this->generateTransId();
            $idempotencyKey = $this->generateIdempotencyKey();
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
                    'remark' => $requestDetail['remark'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $totalChanged += $quantity;
            }

            $mainId = Db::name('warehouse_trans')->insertGetId([
                'trans_id' => $transId,
                'idempotency_key' => $idempotencyKey,
                'ao_id' => $aoId,
                'type' => $normalized['type'],
                'record_no' => $normalized['record_no'] ?: null,
                'business_at' => $normalized['business_at'],
                'total_changed' => $totalChanged,
                'manager_id' => intval($this->manager['manager_id'] ?? 0),
                'manager_name' => strval($this->manager['nickname'] ?? ($this->manager['account'] ?? '')),
                'material_manager_id' => intval($materialManager['manager_id']),
                'material_manager_name' => strval($materialManager['nickname'] ?: $materialManager['account']),
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

            if ($preOrderId > 0) {
                $this->refreshPreReplenishmentMaterialSummary($preOrderId, $normalized['record_no']);
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
        $field = 'id,trans_id,ao_id,type,record_no,business_at,total_changed,manager_id,manager_name,material_manager_id,material_manager_name,remark,created_at,updated_at';
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

    /**
     * 导出单个仓库变化单的商品明细。
     * @param array $where 主单查询条件（id 或 trans_id）
     * @return array|\think\response\Json
     */
    public function exportTransDetails($where)
    {
        $trans = $this->getWarehouseTransFind($where, 'id,trans_id,type,record_no,business_at,manager_name,material_manager_name,remark,created_at');
        if (!$trans) return $this->rFail('仓库变化记录不存在');
        $trans = is_object($trans) && method_exists($trans, 'toArray') ? $trans->toArray() : $trans;

        $details = $this->getWarehouseTransDetailsList(['warehouse_trans_id' => intval($trans['id'])]);
        $details = $details ? (is_object($details) && method_exists($details, 'toArray') ? $details->toArray() : $details) : [];
        if (!$details) return $this->rFail('该仓库变化单没有商品明细');
        $details = $this->attachGoodsPicToDetails($details);

        $typeText = $this->typeNames[intval($trans['type'])] ?? '未知';
        $list = [];
        foreach ($details as $detail) {
            $list[] = [
                'trans_id' => strval($trans['trans_id']),
                'type_text' => $typeText,
                'record_no' => strval($trans['record_no'] ?? ''),
                'business_at' => strval($trans['business_at'] ?? ''),
                'pic' => strval($detail['pic'] ?? ''),
                'goods_name' => strval($detail['goods_name'] ?? ''),
                'sku' => strval($detail['sku'] ?? ''),
                'bar_code' => strval($detail['bar_code'] ?? ''),
                'source_stocks' => intval($detail['source_stocks'] ?? 0),
                'changed' => intval($detail['changed'] ?? 0),
                'now_stocks' => intval($detail['now_stocks'] ?? 0),
                'material_manager_name' => strval($trans['material_manager_name'] ?? ''),
                'manager_name' => strval($trans['manager_name'] ?? ''),
                'detail_remark' => strval($detail['remark'] ?? ''),
                'created_at' => strval($trans['created_at'] ?? ''),
            ];
        }

        $title = [
            'trans_id' => '仓库单号',
            'type_text' => '类型',
            'record_no' => '预补货单号',
            'business_at' => '业务时间',
            'pic' => '商品图片',
            'goods_name' => '商品名称',
            'sku' => 'SKU',
            'bar_code' => '条码',
            'source_stocks' => '变化前库存',
            'changed' => '变化数量',
            'now_stocks' => '变化后库存',
            'material_manager_name' => '物料操作人',
            'manager_name' => '经办人',
            'detail_remark' => '明细备注',
            'created_at' => '创建时间',
        ];
        $filename = '仓库变化单详情-' . strval($trans['trans_id']) . '-' . date('YmdHis');
        return $this->sendToExport('仓库管理-仓库变化单详情', $filename, $title, $list, [
            'imageFields' => ['pic'],
            'imageWidth' => 120,
            'imageHeight' => 80,
        ]);
    }

    public function getPreReplenishmentGoodsList($recordNo)
    {
        $aoId = intval($this->manager['ao_id'] ?? 0);
        $where = ['record_no' => $recordNo];
        if ($aoId > 1) $where['ao_id'] = $aoId;
        $order = Db::name('pre_replenishment_order')
            ->where($where)
            ->field('id,record_no,biz_status,material_status,material_plan_quantity,material_issued_quantity,material_returned_quantity,material_net_quantity')
            ->find();
        if (!$order) return $this->r(100, '预补货单不存在或无权查看');

        $rows = Db::name('pre_replenishment_detail')
            ->where(['order_id' => intval($order['id'])])
            ->field('sku,SUM(plan_quantity) plan_quantity,SUM(COALESCE(actual_quantity,0)) actual_quantity')
            ->group('sku')
            ->order('sku asc')
            ->select()
            ->toArray();
        $skuList = array_values(array_unique(array_filter(array_map(function ($row) {
            return strval($row['sku'] ?? '');
        }, $rows))));
        $goodsRows = $skuList ? GoodsModel::whereIn('sku', $skuList)
            ->field('g_id,g_name,sku,bar_code,pic,stocks,status')
            ->select()
            ->toArray() : [];
        $goodsMap = [];
        foreach ($goodsRows as $goods) $goodsMap[strval($goods['sku'])] = $goods;

        $list = [];
        foreach ($rows as $row) {
            $sku = strval($row['sku'] ?? '');
            $goods = $goodsMap[$sku] ?? [];
            $goodsId = intval($goods['g_id'] ?? 0);
            $returned = $goodsId > 0 ? $this->getPreReplenishmentReturned($recordNo, $goodsId) : 0;
            $netQuantity = $goodsId > 0 ? $this->getPreReplenishmentNetQuantity($recordNo, $goodsId) : 0;
            $planQuantity = intval($row['plan_quantity']);
            $list[] = [
                'goods_id' => $goodsId,
                'goods_name' => strval($goods['g_name'] ?? ''),
                'sku' => $sku,
                'bar_code' => strval($goods['bar_code'] ?? ''),
                'pic' => strval($goods['pic'] ?? ''),
                'stocks' => intval($goods['stocks'] ?? 0),
                'goods_status' => isset($goods['status']) ? intval($goods['status']) : 0,
                'is_goods_linked' => $goodsId > 0 ? 1 : 0,
                'plan_quantity' => $planQuantity,
                'actual_quantity' => intval($row['actual_quantity']),
                'issued_quantity' => $netQuantity + $returned,
                'returned_quantity' => $returned,
                'net_quantity' => $netQuantity,
                'remaining_issue_quantity' => max(0, $planQuantity - $netQuantity),
            ];
        }

        return $this->r(200, '操作成功', [
            'record_no' => strval($order['record_no']),
            'biz_status' => intval($order['biz_status']),
            'material_status' => intval($order['material_status']),
            'material_plan_quantity' => intval($order['material_plan_quantity']),
            'material_issued_quantity' => intval($order['material_issued_quantity']),
            'material_returned_quantity' => intval($order['material_returned_quantity']),
            'material_net_quantity' => intval($order['material_net_quantity']),
            'list' => $list,
        ]);
    }

    protected function refreshPreReplenishmentMaterialSummary($orderId, $recordNo)
    {
        $summary = Db::name('warehouse_trans_details')->alias('d')
            ->join('warehouse_trans t', 't.id = d.warehouse_trans_id')
            ->where('t.record_no', $recordNo)
            ->whereIn('t.type', [3, 4])
            ->field('COALESCE(SUM(CASE WHEN t.type = 4 THEN -d.changed ELSE 0 END),0) issued_quantity,COALESCE(SUM(CASE WHEN t.type = 3 THEN d.changed ELSE 0 END),0) returned_quantity')
            ->find();
        $issuedQuantity = max(0, intval($summary['issued_quantity'] ?? 0));
        $returnedQuantity = max(0, intval($summary['returned_quantity'] ?? 0));
        $netQuantity = max(0, $issuedQuantity - $returnedQuantity);
        $planQuantity = max(0, intval(Db::name('pre_replenishment_detail')
            ->where(['order_id' => intval($orderId)])
            ->sum('plan_quantity')));
        if ($issuedQuantity <= 0) {
            $materialStatus = 0;
        } elseif ($netQuantity <= 0) {
            $materialStatus = 4;
        } elseif ($planQuantity > 0 && $netQuantity >= $planQuantity) {
            $materialStatus = 2;
        } elseif ($returnedQuantity > 0) {
            $materialStatus = 3;
        } else {
            $materialStatus = 1;
        }

        $updated = Db::name('pre_replenishment_order')->where(['id' => intval($orderId)])->update([
            'material_status' => $materialStatus,
            'material_plan_quantity' => $planQuantity,
            'material_issued_quantity' => $issuedQuantity,
            'material_returned_quantity' => $returnedQuantity,
            'material_net_quantity' => $netQuantity,
        ]);
        if ($updated === false) throw new \Exception('预补货单物料状态及数量更新失败');
    }

    protected function normalizeCreateData($postData)
    {
        $type = intval($postData['type'] ?? 0);
        $recordNo = trim(strval($postData['record_no'] ?? ''));
        if (in_array($type, [3, 4], true) && $recordNo === '') {
            return $this->r(100, '预补货退料或出库时record_no必填');
        }
        if (in_array($type, [1, 2], true)) $recordNo = '';

        $normalizedDetails = [];
        $shouldParseDetails = in_array($type, [1, 2], true)
            || (in_array($type, [3, 4], true) && array_key_exists('details', $postData) && $postData['details'] !== null && $postData['details'] !== []);
        if ($shouldParseDetails) {
            $details = $postData['details'] ?? [];
            if (!is_array($details) || !$details) return $this->r(100, '商品明细不能为空');
            if (count($details) > 500) return $this->r(100, '单次商品明细不能超过500条');

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
                $detailRemark = trim(strval($detail['remark'] ?? ''));
                if (mb_strlen($detailRemark, 'UTF-8') > 255) {
                    return $this->r(100, '第' . ($index + 1) . '条商品明细备注长度不能超过255');
                }
                $normalizedDetails[$goodsId] = [
                    'quantity' => intval($quantityRaw),
                    'remark' => $detailRemark === '' ? $this->buildDetailRemark($type, $recordNo) : $detailRemark,
                ];
            }
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
            'material_manager_id' => intval($postData['material_manager_id'] ?? 0),
            'business_at' => $businessAt,
            'remark' => trim(strval($postData['remark'] ?? '')) ?: null,
            'details' => $normalizedDetails,
        ];
    }

    protected function buildDetailRemark($type, $recordNo = '')
    {
        $remark = $this->typeNames[intval($type)] ?? '库存变化';
        if (in_array(intval($type), [3, 4], true) && $recordNo !== '') {
            $remark .= '，关联预补货单' . $recordNo;
        }
        return $remark;
    }

    protected function getMaterialManager($managerId, $aoId)
    {
        $where = ['manager_id' => intval($managerId), 'status' => 1];
        if ($aoId > 1) $where['ao_id'] = $aoId;
        $manager = Db::name('auth_manager')->where($where)->field('manager_id,account,nickname,ao_id')->find();
        if (!$manager) throw new \Exception('物料操作人不存在、已停用或不属于当前组织');
        return $manager;
    }

    protected function getPreReplenishmentData($type, $recordNo, $aoId, $requestedDetails = [])
    {
        $where = ['record_no' => $recordNo];
        if ($aoId > 1) $where['ao_id'] = $aoId;
        $order = Db::name('pre_replenishment_order')->where($where)->field('id,record_no,ao_id,biz_status')->find();
        if (!$order) throw new \Exception('预补货单不存在或无权操作');

        if ($type === 4 && intval($order['biz_status']) !== 1) {
            throw new \Exception('只有未补货单据才能生成预补货出库');
        }
        if ($type === 3) {
            // 暂时允许预补货未完成时退料，保留原校验代码供后续恢复。
            // if (!in_array(intval($order['biz_status']), [2, 3], true)) {
            //     throw new \Exception('预补货完成后才能生成退料');
            // }
            if (!Db::name('warehouse_trans')->where(['type' => 4, 'record_no' => $recordNo])->count()) {
                throw new \Exception('该预补货单尚未生成仓库出库记录');
            }
        }

        $rows = Db::name('pre_replenishment_detail')
            ->where(['order_id' => intval($order['id'])])
            ->field('sku,SUM(plan_quantity) plan_quantity,SUM(COALESCE(actual_quantity,0)) actual_quantity')
            ->group('sku')
            ->select()
            ->toArray();
        if (!$rows) throw new \Exception('预补货单没有商品明细');

        $skuList = array_values(array_unique(array_filter(array_map(function ($row) {
            return strval($row['sku'] ?? '');
        }, $rows))));
        $goodsRows = GoodsModel::whereIn('sku', $skuList)->field('g_id,sku')->select()->toArray();
        $goodsMap = [];
        foreach ($goodsRows as $goods) $goodsMap[strval($goods['sku'])] = intval($goods['g_id']);

        if (in_array($type, [3, 4], true) && $requestedDetails) {
            $allowedGoodsIds = array_flip(array_values($goodsMap));
            foreach ($requestedDetails as $goodsId => &$requestedDetail) {
                if (!isset($allowedGoodsIds[intval($goodsId)])) {
                    throw new \Exception('商品ID ' . $goodsId . ' 与该预补货单无关，无关商品可以通过其他出入库方式操作');
                }
            }
            unset($requestedDetail);
        }

        $details = [];
        $planMap = [];
        foreach ($rows as $row) {
            $sku = strval($row['sku'] ?? '');
            if (!isset($goodsMap[$sku])) throw new \Exception('预补货商品SKU ' . $sku . ' 未关联goods商品');
            $planQuantity = intval($row['plan_quantity']);
            $actualQuantity = intval($row['actual_quantity']);
            $goodsId = $goodsMap[$sku];
            $netQuantity = $this->getPreReplenishmentNetQuantity($recordNo, $goodsId);
            if ($type === 4) {
                $quantity = max(0, $planQuantity - $netQuantity);
            } else {
                $returned = $this->getPreReplenishmentReturned($recordNo, $goodsId);
                $quantity = max(0, $planQuantity - $actualQuantity - $returned);
            }
            $planMap[$sku] = $planQuantity;
            if ($quantity <= 0) continue;
            $details[$goodsId] = [
                'quantity' => $quantity,
                'remark' => $this->buildDetailRemark($type, $recordNo),
            ];
        }
        if (in_array($type, [3, 4], true) && $requestedDetails) $details = $requestedDetails;
        if (!$details) {
            throw new \Exception($type === 3 ? '该预补货单没有可退料商品' : '该预补货单没有可出库商品');
        }
        return ['order_id' => intval($order['id']), 'details' => $details, 'plan_map' => $planMap];
    }

    protected function validatePreReplenishmentQuantity($type, $recordNo, $goods, $quantity, $planMap)
    {
        $sku = strval($goods['sku'] ?? '');
        $planQuantity = intval($planMap[$sku] ?? 0);
        if ($planQuantity <= 0) throw new \Exception('商品SKU ' . $sku . ' 不在该预补货单中');

        $netQuantity = $this->getPreReplenishmentNetQuantity($recordNo, intval($goods['g_id']));

        if ($type === 4 && $netQuantity + $quantity > $planQuantity) {
            throw new \Exception('商品SKU ' . $sku . ' 预补货出库累计数量不能超过计划数量' . $planQuantity);
        }
        if ($type === 3 && $quantity > $netQuantity) {
            throw new \Exception('商品SKU ' . $sku . ' 退料数量不能超过当前净领料数量' . $netQuantity);
        }
    }

    protected function getPreReplenishmentNetQuantity($recordNo, $goodsId)
    {
        $netChanged = intval(Db::name('warehouse_trans_details')->alias('d')
            ->join('warehouse_trans t', 't.id = d.warehouse_trans_id')
            ->where('t.record_no', $recordNo)
            ->whereIn('t.type', [3, 4])
            ->where('d.goods_id', intval($goodsId))
            ->sum('d.changed'));
        return max(0, -$netChanged);
    }

    protected function getPreReplenishmentReturned($recordNo, $goodsId)
    {
        return intval(Db::name('warehouse_trans_details')->alias('d')
            ->join('warehouse_trans t', 't.id = d.warehouse_trans_id')
            ->where(['t.record_no' => $recordNo, 't.type' => 3, 'd.goods_id' => intval($goodsId)])
            ->sum('d.changed'));
    }

    protected function formatTrans($trans)
    {
        if (is_object($trans) && method_exists($trans, 'toArray')) $trans = $trans->toArray();
        $trans['type_text'] = $this->typeNames[intval($trans['type'])] ?? '未知';
        $details = $this->getWarehouseTransDetailsList(['warehouse_trans_id' => intval($trans['id'])]);
        $details = $details ? (is_object($details) && method_exists($details, 'toArray') ? $details->toArray() : $details) : [];
        $trans['details'] = $this->attachGoodsPicToDetails($details);
        return $trans;
    }

    /**
     * 为仓库变化明细批量附加商品图片（pic），按 goods_id 批量查询后合并。
     * @param array $details
     * @return array
     */
    protected function attachGoodsPicToDetails($details)
    {
        if (!$details) return $details;

        $goodsIds = [];
        foreach ($details as $detail) {
            $goodsId = intval($detail['goods_id'] ?? 0);
            if ($goodsId > 0) $goodsIds[$goodsId] = $goodsId;
        }

        $picMap = [];
        if ($goodsIds) {
            $goodsRows = GoodsModel::whereIn('g_id', array_values($goodsIds))
                ->field('g_id,pic')
                ->select()
                ->toArray();
            foreach ($goodsRows as $goods) {
                $picMap[intval($goods['g_id'])] = strval($goods['pic'] ?? '');
            }
        }

        foreach ($details as &$detail) {
            $goodsId = intval($detail['goods_id'] ?? 0);
            $detail['pic'] = $picMap[$goodsId] ?? '';
        }
        unset($detail);

        return $details;
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

    /**
     * 幂等键由后台生成，仅用于数据库唯一约束和请求追踪。
     */
    protected function generateIdempotencyKey()
    {
        return 'WT' . date('YmdHis') . bin2hex(random_bytes(8));
    }
}
