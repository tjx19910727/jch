<?php

namespace app\management\controller\fault_notice;

use app\management\controller\Common;

/**
 * 故障通知总览看板后台接口。
 */
class FaultDashboard extends Common
{
    /**
     * 请求地址：/management/fault_notice.fault_dashboard/getOverview
     */
    public function getOverview()
    {
        return $this->app->faultNotice->getOverview();
    }

    /**
     * 请求地址：management/fault_notice.fault_dashboard/getTrend
     */
    public function getTrend()
    {
        $postData = input();
        $level = intval($postData['level'] ?? 0);
        return $this->app->faultNotice->getTrend($level);
    }

    /**
     * 请求地址：management/fault_notice.fault_dashboard/getTopRanking
     * top默认10，允许首页与排行详情页按需指定数量。
     */
    public function getTopRanking()
    {
        $postData = input();
        $top = intval($postData['top'] ?? 10);
        $top = $top > 0 ? min($top, 100) : 10;
        $level = intval($postData['level'] ?? 0);
        return $this->app->faultNotice->getTopRanking($top, $level);
    }

    /**
     * 请求地址：management/fault_notice.fault_dashboard/getMachineTopRanking
     * top默认10；level为0或不传时查询全部等级。
     */
    public function getMachineTopRanking()
    {
        $postData = input();
        $top = intval($postData['top'] ?? 10);
        $top = $top > 0 ? min($top, 100) : 10;
        $level = intval($postData['level'] ?? 0);
        return $this->app->faultNotice->getMachineTopRanking($top, $level);
    }
}
