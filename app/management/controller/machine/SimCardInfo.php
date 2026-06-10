<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/4/21
 * Time: 11:30
 */

namespace app\management\controller\machine;


use app\AppFactory\Kernel\Support\SimiotService\Simiot;
use app\management\controller\Common;

class SimCardInfo extends Common
{
    protected $field = "*";

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, [
            'machine_id' => 'like',
            'iccid' => 'like',
            'msisdn' => 'like',
            'carrier' => 'like',
            'imei' => 'like',
            'device_card_status' => 'like',
        ],'a.');
        $where = $this->formatAoIdWhereWithPrefix($where,'m.');
        return  $this->app->simCardInfo->getListData($where, $pageNum, $this->field, 'a.id desc');
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->simCardInfo->getFindData($where, $this->field);
    }

    /**
     * 获取 sim_card_machine 列表
     * @return array|\think\response\Json
     */
    public function getMachineList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, [
            'machine_id' => 'like',
            'iccid' => 'like',
            'remark' => 'like',
        ],'a.');
        $where = $this->formatAoIdWhereWithPrefix($where,'m.');
        return $this->app->simCardInfo->getMachineListData($where, $pageNum, $this->field, 'a.date desc');
    }

    /**
     * 获取 sim_card_machine 单条数据
     * @return array|\think\response\Json
     */
    public function getMachineFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->simCardInfo->getMachineFindData($where, $this->field);
    }

    /**
     * 获取 sim_signal_log 列表（分页）
     * @return array|\think\response\Json
     */
    public function getSignalList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, [
            'machine_id' => 'like',
            'iccid' => 'like',
        ],'a.');
        $where = $this->formatAoIdWhereWithPrefix($where,'m.');
        return $this->app->simCardInfo->getSignalListData($where, $pageNum, $this->field, 'a.id desc');
    }

    /**
     * 获取 sim_signal_log 单条数据
     * @return array|\think\response\Json
     */
    public function getSignalFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->simCardInfo->getSignalFindData($where, $this->field);
    }

    /**
     * 获取设备每日信号折线图（rsrp/sinr）
     * 参数：m_id（必填，仅支持单条）、date（可选，格式 Y-m-d，默认当天）
     * @return array|\think\response\Json
     */
    public function getSignalDayTrend()
    {
        $mId = input('m_id');
        $date = input('date');
        return $this->app->simCardInfo->getSignalDayTrendData($mId, $date);
    }

    /**
     * 获取流量池信息
     * @return array|\think\response\Json
     */
    public function queryPool()
    {
        $result = Simiot::queryPool();
        if (is_array($result) && isset($result['code']) && intval($result['code']) === 0) {
            return returnState(200, lang('query_success'), $result['result'][0] ?? []);
        }
        return returnState(100, $result['message'] ?? lang('query_fail'), $result);
    }
}
