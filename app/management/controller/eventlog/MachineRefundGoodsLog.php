<?php

namespace app\management\controller\eventlog;

use app\management\controller\Common;
use think\facade\Db;

class MachineRefundGoodsLog extends Common
{
    /**
     * 设备端客户提交退货日志分页列表。
     * @return array|\think\response\Json
     */
    public function getList()
    {
        try {
            $postData = input();
            $pageNum = intval($postData['pageNum'] ?? 20);
            if ($pageNum <= 0) $pageNum = 20;
            $pageNum = min($pageNum, 200);

            $query = Db::name('machine_refund_goods_log')->alias('mrgl')
                ->leftJoin('machine m', 'm.m_id = mrgl.m_id')
                ->leftJoin('auth_organization ao', 'ao.ao_id = mrgl.ao_id');

            $mIds = $this->app->machine->resolvePermittedMachineIds();
            if ($mIds !== null) {
                if (!$mIds) {
                    $query->whereRaw('1 = 0');
                } else {
                    $query->whereIn('mrgl.m_id', $mIds);
                }
            }

            $likeFields = [
                'machine_id' => 'mrgl.machine_id',
                'machine_name' => 'm.machine_name',
                'trade_no' => 'mrgl.trade_no',
                'mobile' => 'mrgl.mobile',
                'input_code' => 'mrgl.input_code',
            ];
            foreach ($likeFields as $param => $field) {
                if (isset($postData[$param]) && trim((string)$postData[$param]) !== '') {
                    $query->where($field, 'like', '%' . trim((string)$postData[$param]) . '%');
                }
            }
            foreach (['mrgl_id', 'm_id', 'ao_id', 'order_id', 'verify_type', 'verify_status'] as $field) {
                if (isset($postData[$field]) && $postData[$field] !== '') {
                    $query->where('mrgl.' . $field, '=', intval($postData[$field]));
                }
            }
            if (!empty($postData['create_time']) && strpos($postData['create_time'], '~') !== false) {
                $parts = array_map('trim', explode('~', $postData['create_time'], 2));
                if (count($parts) === 2 && $parts[0] !== '' && $parts[1] !== '') {
                    $query->whereBetween('mrgl.create_time', [strtotime($parts[0]), strtotime($parts[1])]);
                }
            }

            $list = $query
                ->field('mrgl.mrgl_id,mrgl.m_id,mrgl.machine_id,m.machine_name,mrgl.ao_id,
                    ao.organization_name,mrgl.order_id,mrgl.trade_no,mrgl.mobile,mrgl.input_code,
                    mrgl.verify_type,mrgl.verify_status,mrgl.pic_out_goods_box,
                    mrgl.video_out_goods_box,mrgl.video_refund_goods,mrgl.create_time,mrgl.update_time')
                ->order('mrgl.mrgl_id desc')
                ->paginate($pageNum, false, ['query' => request()->param()]);

            $list = $list->each(function ($item) {
                $item['verify_type_name'] = intval($item['verify_type']) === 2 ? '特殊编码' : '订单号后四位';
                $item['verify_status_name'] = intval($item['verify_status']) === 1 ? '校验成功' : '校验失败';
                foreach (['pic_out_goods_box', 'video_out_goods_box', 'video_refund_goods'] as $field) {
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
}
