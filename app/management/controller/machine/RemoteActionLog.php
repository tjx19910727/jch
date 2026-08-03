<?php

namespace app\management\controller\machine;

use app\management\controller\Common;

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
