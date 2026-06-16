<?php

namespace app\management\controller\eventlog;

use app\management\controller\Common;
use think\facade\Db;

class RemoteActionLog extends Common
{
    /**
     * 后台远程退货操作日志分页列表。
     * @return array|\think\response\Json
     */
    public function getRemoteRefundGoodsList()
    {
        try {
            $postData = input();
            $pageNum = $this->getPageNum($postData);
            $query = Db::name('remote_action_log')->alias('ral')
                ->leftJoin('machine m', 'm.machine_id = ral.machine_id')
                ->leftJoin('sale_orders so', 'so.order_id = ral.order_id')
                ->leftJoin('sale_orders_details sod', 'sod.sod_id = ral.sod_id')
                ->leftJoin('auth_manager am', 'am.manager_id = ral.manager_id')
                ->leftJoin('auth_organization ao', 'ao.ao_id = m.ao_id')
                ->whereIn('ral.type', [
                    'remote_refund_goods',
                    'remote_refund_goods_img',
                    'pickUpDoorOpen',
                    'pickUpDoorClose',
                    'recycGoods',
                ]);

            $this->applyMachineAuth($query, 'm.m_id');
            $this->applyFilters($query, $postData);

            $list = $query
                ->field('ral.id,ral.machine_id,m.m_id,m.machine_name,m.ao_id,ao.organization_name,
                    ral.type,ral.msgType,ral.order_id,ral.sod_id,ral.goods_id,ral.channel_code,
                    ral.status,ral.operator_at,ral.manager_id,ral.field,
                    so.trade_no,sod.g_name,sod.sku,sod.pic,sod.remote_refund_status,sod.refund_photo,
                    am.account manager_account,am.nickname manager_nickname')
                ->order('ral.id desc')
                ->paginate($pageNum, false, ['query' => request()->param()]);

            $statusMap = [1 => '已发送', 2 => '设备已接收', 3 => '操作成功', 4 => '操作失败'];
            $typeMap = [
                'remote_refund_goods' => '远程退货拍照',
                'remote_refund_goods_img' => '远程退货拍照',
                'pickUpDoorOpen' => '打开取货门',
                'pickUpDoorClose' => '关闭取货门',
                'recycGoods' => '回收商品',
            ];
            $list = $list->each(function ($item) use ($statusMap, $typeMap) {
                $status = intval($item['status'] ?? 0);
                $item['status_name'] = $statusMap[$status] ?? ('未知状态#' . $status);
                $item['type_name'] = $typeMap[$item['type']] ?? $item['type'];
                $item['manager_name'] = $item['manager_nickname'] ?: $item['manager_account'];
                foreach (['field', 'pic', 'refund_photo'] as $field) {
                    if (!empty($item[$field])) $item[$field] = checkStrDomain($item[$field]);
                }
                return $item;
            });
            return returnState(200, lang('query_success'), $list);
        } catch (\Exception $e) {
            actionException($e, 1);
            return returnTryCatch($e->getMessage());
        }
    }

    protected function applyFilters($query, $postData)
    {
        $likeFields = [
            'machine_id' => 'ral.machine_id',
            'machine_name' => 'm.machine_name',
            'trade_no' => 'so.trade_no',
            'g_name' => 'sod.g_name',
            'sku' => 'sod.sku',
            'channel_code' => 'ral.channel_code',
        ];
        foreach ($likeFields as $param => $field) {
            if (isset($postData[$param]) && trim((string)$postData[$param]) !== '') {
                $query->where($field, 'like', '%' . trim((string)$postData[$param]) . '%');
            }
        }
        $equalFields = [
            'id' => 'ral.id',
            'm_id' => 'm.m_id',
            'ao_id' => 'm.ao_id',
            'order_id' => 'ral.order_id',
            'sod_id' => 'ral.sod_id',
            'goods_id' => 'ral.goods_id',
            'manager_id' => 'ral.manager_id',
        ];
        foreach ($equalFields as $param => $field) {
            if (isset($postData[$param]) && $postData[$param] !== '') {
                $query->where($field, '=', intval($postData[$param]));
            }
        }
        if (isset($postData['status']) && trim((string)$postData['status']) !== '') {
            $query->whereIn('ral.status', array_map('intval', explode(',', (string)$postData['status'])));
        }
        if (isset($postData['manager_name']) && trim((string)$postData['manager_name']) !== '') {
            $keyword = trim((string)$postData['manager_name']);
            $query->where(function ($subQuery) use ($keyword) {
                $subQuery->where('am.nickname', 'like', '%' . $keyword . '%')
                    ->whereOr('am.account', 'like', '%' . $keyword . '%');
            });
        }
        $this->applyTimeRange($query, $postData['operator_at'] ?? '', 'ral.operator_at');
    }

    protected function applyMachineAuth($query, $field)
    {
        $mIds = $this->app->machine->resolvePermittedMachineIds();
        if ($mIds === null) return;
        if (!$mIds) {
            $query->whereRaw('1 = 0');
            return;
        }
        $query->whereIn($field, $mIds);
    }

    protected function applyTimeRange($query, $range, $field)
    {
        if (!$range || strpos($range, '~') === false) return;
        $parts = array_map('trim', explode('~', $range, 2));
        if (count($parts) === 2 && $parts[0] !== '' && $parts[1] !== '') {
            $query->whereBetween($field, [$parts[0], $parts[1]]);
        }
    }

    protected function getPageNum($postData)
    {
        $pageNum = intval($postData['pageNum'] ?? 20);
        if ($pageNum <= 0) $pageNum = 20;
        return min($pageNum, 200);
    }
}
