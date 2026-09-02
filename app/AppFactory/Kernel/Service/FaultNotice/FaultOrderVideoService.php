<?php

namespace app\AppFactory\Kernel\Service\FaultNotice;

use app\AppFactory\AppFactory;
use app\AppFactory\Kernel\Model\SaleOrders\SaleOrdersVideoModel;
use think\facade\Cache;
use think\facade\Db;

/**
 * 微信故障详情页订单视频服务。
 *
 * 订单号和设备信息只取自故障事件，不接收前端指定，避免越权查询其他订单视频。
 */
class FaultOrderVideoService
{
    const REQUEST_CACHE_SECONDS = 60;

    /**
     * 获取与故障事件严格匹配的订单及优先级最高的出货失败明细。
     */
    public function getOrderInfo($event)
    {
        $order = $this->getMatchedOrder($event);
        if (!$order) {
            return [];
        }

        $detail = (array)Db::name('sale_orders_details')
            ->where('order_id', intval($order['order_id']))
            ->whereRaw('((success_quantity = 0 AND fail_quantity = 0) OR fail_quantity > 0)')
            ->field('sod_id,channel_code,success_quantity,fail_quantity')
            ->order('fail_quantity desc,sod_id asc')
            ->find();

        return [
            'order_id' => intval($order['order_id']),
            'trade_no' => strval($order['trade_no'] ?? ''),
            'channel_code' => trim(strval($detail['channel_code'] ?? '')),
        ];
    }

    public function getStatus($event)
    {
        $order = $this->getMatchedOrder($event);
        if (!$order) {
            return $this->result(false, 'unavailable', '订单不存在或与故障设备不匹配');
        }

        $rows = Db::name('sale_orders_video')
            ->where([
                'video_type' => SaleOrdersVideoModel::TYPE_SALE_ORDER,
                'relation_id' => intval($order['order_id']),
            ])
            ->field('sov_id,trade_no,segment_no,transaction_video,video_total,create_time')
            ->order('segment_no asc,sov_id asc')
            ->select()
            ->toArray();

        if ($rows) {
            $videos = [];
            $videoTotal = 0;
            $latestCreateTime = 0;
            foreach ($rows as $row) {
                $path = trim(strval($row['transaction_video'] ?? ''));
                if ($path === '') {
                    continue;
                }
                $segmentNo = max(0, intval($row['segment_no'] ?? 0));
                $videoTotal = max($videoTotal, intval($row['video_total'] ?? 0));
                $latestCreateTime = max($latestCreateTime, intval($row['create_time'] ?? 0));
                $videos[$segmentNo] = [
                    'video_name' => strval($row['trade_no'] ?? $order['trade_no']) . '-' . $segmentNo,
                    'video_url' => checkStrDomain($path),
                    'segment_no' => $segmentNo,
                ];
            }
            ksort($videos, SORT_NUMERIC);
            $videos = array_values($videos);
            $complete = ($videoTotal > 0 && count($videos) >= $videoTotal)
                || ($latestCreateTime > 0 && time() - $latestCreateTime > 60);
            return $this->result(
                true,
                $complete ? 'ready' : 'uploading',
                $complete ? '视频获取完成' : '视频文件正在上传，请稍后查看',
                $videos
            );
        }

        $legacyVideo = trim(strval($order['transaction_video'] ?? ''));
        if ($legacyVideo !== '') {
            return $this->result(true, 'ready', '视频获取完成', [[
                'video_name' => strval($order['trade_no']) . '-0',
                'video_url' => checkStrDomain($legacyVideo),
                'segment_no' => 0,
            ]]);
        }

        if (Cache::get($this->getRequestCacheKey($event))) {
            return $this->result(true, 'requested', '已向设备请求视频，请稍后查看');
        }

        return $this->result(true, 'idle', '暂未获取订单视频');
    }

    public function requestVideo($event)
    {
        $status = $this->getStatus($event);
        if (!$status['success'] || in_array($status['status'], ['ready', 'uploading'], true)) {
            return $status;
        }

        $tradeNo = trim(strval($event['trade_no'] ?? ''));
        $machineId = trim(strval($event['machine_id'] ?? ''));
        if ($tradeNo === '' || $machineId === '') {
            return $this->result(false, 'unavailable', '故障事件缺少订单编号或设备编号');
        }

        $cacheKey = $this->getRequestCacheKey($event);
        if (Cache::get($cacheKey)) {
            return $this->result(true, 'requested', '已向设备请求视频，请稍后查看');
        }

        $machine = (array)Db::name('machine')
            ->where('m_id', intval($event['m_id'] ?? 0))
            ->where('machine_id', $machineId)
            ->field('m_id,machine_id,online,mac_address,signKey')
            ->find();
        if (!$machine) {
            return $this->result(false, 'unavailable', '故障设备不存在');
        }
        if (intval($machine['online'] ?? 0) !== 1) {
            return $this->result(false, 'offline', '设备当前不在线，无法获取订单视频');
        }

        $key = trim(strval($machine['signKey'] ?? '')) ?: trim(strval(env('api.md5Key', '')));
        if ($key === '') {
            return $this->result(false, 'unavailable', '设备签名密钥不存在，无法获取订单视频');
        }

        Cache::set($cacheKey, 1, self::REQUEST_CACHE_SECONDS);
        try {
            $app = AppFactory::machine([
                'machine_id' => $machineId,
                'key' => $key,
                'mac' => strval($machine['mac_address'] ?? ''),
            ]);
            $result = $app->sendMq->sendMq('transactionVideo', ['trade_no' => $tradeNo]);
            $resultData = obj2arr($result);
            if (!is_array($resultData) || intval($resultData['state'] ?? 0) !== 200) {
                Cache::delete($cacheKey);
                return $this->result(
                    false,
                    'failed',
                    '视频请求下发失败：' . strval($resultData['msg'] ?? '未知错误')
                );
            }
            actionLog([
                'me_id' => intval($event['me_id'] ?? 0),
                'machine_id' => $machineId,
                'trade_no' => $tradeNo,
            ], '微信故障详情页请求订单视频', 'faultOrderVideo');
            return $this->result(true, 'requested', '已向设备请求视频，请稍后查看');
        } catch (\Throwable $e) {
            Cache::delete($cacheKey);
            actionException($e, 1, 'faultOrderVideo');
            return $this->result(false, 'failed', '视频请求下发失败：' . $e->getMessage());
        }
    }

    protected function getMatchedOrder($event)
    {
        $tradeNo = trim(strval($event['trade_no'] ?? ''));
        if ($tradeNo === '') {
            return [];
        }
        return (array)Db::name('sale_orders')
            ->where('trade_no', $tradeNo)
            ->where('m_id', intval($event['m_id'] ?? 0))
            ->where('machine_id', strval($event['machine_id'] ?? ''))
            ->where('ao_id', intval($event['ao_id'] ?? 0))
            ->field('order_id,trade_no,m_id,machine_id,ao_id,transaction_video')
            ->find();
    }

    protected function getRequestCacheKey($event)
    {
        return 'wx_fault_order_video_request:' . md5(
            strval($event['machine_id'] ?? '') . '|' . strval($event['trade_no'] ?? '')
        );
    }

    protected function result($success, $status, $message, $videos = [])
    {
        return [
            'success' => (bool)$success,
            'status' => strval($status),
            'message' => strval($message),
            'videos' => is_array($videos) ? $videos : [],
        ];
    }
}
