<?php

namespace app\management\controller\machine;

use app\management\controller\Common;
use think\facade\Db;

class RemoteActionLog extends Common
{
    /**
     * 远程出货记录列表。
     *
     * 支持筛选：
     * m_id：设备主键，支持逗号分隔，例如 127,88,255
     * trade_no：订单号，模糊查询
     * sod_id：子订单编号，模糊查询
     *
     * @return array|\think\response\Json
     */
    public function getList()
    {
        $postData = input();
        $where = $this->buildWhere($postData);
        $pageNum = $postData['pageNum'] ?? 0;

        return $this->app->remoteActionLog->getRemoteOutGoodsList($where, $pageNum);
    }

    /**
     * 导出远程出货记录，筛选条件与列表一致。
     *
     * @return array|\think\response\Json
     */
    public function export()
    {
        $where = $this->buildWhere(input());

        return $this->app->remoteActionLog->exportRemoteOutGoodsList($where);
    }

    /**
     * 获取无订单远程出货日志视频；无数据时按日志ID通知设备补传。
     *
     * @return array|\think\response\Json
     */
    public function getVideo()
    {
        $id = intval(input('id'));
        if ($id <= 0) return returnValidate('远程出货日志ID不能为空');

        $query = Db::name('remote_action_log')->alias('ral')
            ->leftJoin('machine m', 'm.machine_id = ral.machine_id')
            ->where('ral.id', $id)
            ->where('ral.type', 'remoteOutGoods')
            ->whereRaw('(ral.sod_id IS NULL OR ral.sod_id = 0)');
        $permittedMIds = $this->app->machine->resolvePermittedMachineIds();
        if ($permittedMIds !== null) {
            $query->whereIn('m.m_id', $permittedMIds ?: [0]);
        }
        $log = $query->field('ral.id,ral.machine_id,ral.type,ral.sod_id,ral.transaction_video,
                ral.video_total,ral.video_count,ral.video_status,ral.video_request_at,ral.video_updated_at')
            ->find();
        if (!$log) return returnState(100, '未找到可访问的无订单远程出货日志');

        $video = $this->app->remoteActionLog->formatRemoteOutGoodsVideo($log);
        if ($video['video_complete']) return returnState(200, '查询成功', $video);

        $now = date('Y-m-d H:i:s');
        Db::startTrans();
        try {
            $lockedLog = Db::name('remote_action_log')->where(['id' => $id])->lock(true)->find();
            if (!$lockedLog) {
                Db::rollback();
                return returnState(100, '远程出货日志已不存在');
            }
            $lockedVideo = $this->app->remoteActionLog->formatRemoteOutGoodsVideo($lockedLog);
            if ($lockedVideo['video_complete']) {
                Db::commit();
                return returnState(200, '查询成功', $lockedVideo);
            }
            $requestAt = !empty($lockedLog['video_request_at']) ? strtotime($lockedLog['video_request_at']) : 0;
            if ($requestAt && time() - $requestAt < 20) {
                Db::commit();
                return returnState(200, '视频文件正在上传，请稍后重试', $lockedVideo);
            }
            $updated = Db::name('remote_action_log')->where(['id' => $id])->update([
                'video_status' => 1,
                'video_request_at' => $now,
            ]);
            if ($updated === false) {
                Db::rollback();
                return returnState(100, '更新视频获取状态失败');
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            actionException($e, 1, 'remoteOutGoodsVideo');
            return returnTryCatch($e->getMessage());
        }
        $videoKey = 'remote_out_goods_log_' . $id;
        try {
            $result = $this->app->machine->sendToMachine(
                ['machine_id' => $log['machine_id']],
                'transactionVideo',
                [
                    'trade_no' => $videoKey,
                    'video_scene' => 'remote_action_log',
                    'log_id' => $id,
                    'video_key' => $videoKey,
                ]
            );
        } catch (\Exception $e) {
            Db::name('remote_action_log')->where(['id' => $id, 'video_request_at' => $now])->update([
                'video_status' => 3,
            ]);
            actionException($e, 1, 'remoteOutGoodsVideo');
            return returnTryCatch($e->getMessage());
        }
        if (!is_object($result)) {
            Db::name('remote_action_log')->where(['id' => $id, 'video_request_at' => $now])->update([
                'video_status' => 3,
            ]);
            return $this->app->machine->rFail($this->app->machine->lang('VMachine.' . $result));
        }

        $video['video_status'] = 1;
        $video['video_status_name'] = '获取中';
        return returnState(200, '正在从机器端获取视频文件，请稍后重试', $video);
    }

    /**
     * 列表和导出共用筛选条件。
     *
     * @param array $postData
     * @return array
     */
    protected function buildWhere($postData)
    {
        $filterData = [
            'm.m_id' => $postData['m_id'] ?? '',
            'so.trade_no' => $postData['trade_no'] ?? '',
            'ral.sod_id' => $postData['sod_id'] ?? '',
        ];
        $where = $this->getWhere($filterData, false, [
            'm.m_id' => 'in',
            'so.trade_no' => 'like',
            'ral.sod_id' => 'like',
        ]);

        // getWhere 可能根据菜单数据权限追加 ao_id，关联查询时需明确归属 machine 表。
        $where = $this->formatAoIdWhereWithPrefix($where, 'm.');

        // 同时沿用设备授权范围，避免列表和导出越权查询设备日志。
        $permittedMIds = $this->app->machine->resolvePermittedMachineIds();
        if ($permittedMIds !== null) {
            $where[] = ['m.m_id', 'in', $permittedMIds ?: [0]];
        }

        return $where;
    }
}
