<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/4/21
 * Time: 11:30
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Kernel\Traits\Machine\SimCardInfoTrait;
use app\AppFactory\Kernel\Traits\Machine\SimSignalLogTrait;
use app\AppFactory\Management\ManagementClient;
use think\facade\Db;

class SimCardInfoClient extends ManagementClient
{
    use SimCardInfoTrait;
    use SimSignalLogTrait;

    public function getListData($where, $pageNum = 0, $field = "a.*", $order = "a.id desc")
    {
        $list = $this->getSimCardInfoListJoinMachine($where, $pageNum, $field, $order);
        return $this->rQ($list);
    }

    public function getFindData($where, $field = "*")
    {
        $data = $this->getSimCardInfoFind($where, $field);
        return $this->rQ($data);
    }

    public function getMachineListData($where, $pageNum = 0, $field = "a.*", $order = "a.id desc")
    {
        $list = $this->getSimCardMachineListJoinMachine($where, $pageNum, $field, $order,function (&$item){
            if (isset($item['machine_usage'])) {
                $item['machine_usage'] = bcdiv($item['machine_usage'], '1024', 0);
            }
        });
        return $this->rQ($list);
    }

    public function getMachineFindData($where, $field = "*")
    {
        $data = $this->getSimCardMachineFind($where, $field);
        //除于1024
        if (isset($data['machine_usage'])) {
            $data['machine_usage'] = bcdiv($data['machine_usage'], '1024', 0);
        }
        return $this->rQ($data);
    }

    public function getSignalListData($where, $pageNum = 0, $field = "a.*", $order = "a.id desc")
    {
        $list = $this->getSimSignalLogListJoinMachine($where, $pageNum, $field, $order);
        return $this->rQ($list);
    }

    public function getSignalFindData($where, $field = "*")
    {
        $data = $this->getSimSignalLogFind($where, $field);
        return $this->rQ($data);
    }

    /**
     * 获取设备某一天的信号折线图（rsrp/sinr）
     * @param mixed $mId
     * @param string $date
     * @return array|\think\response\Json
     */
    public function getSignalDayTrendData($mId, $date = '')
    {
        if ($mId === null || $mId === '') {
            return returnValidate('m_id不能为空');
        }

        if (strpos((string)$mId, ',') !== false) {
            return returnValidate('m_id只支持单条查询');
        }

        if (!preg_match('/^\d+$/', (string)$mId)) {
            return returnValidate('m_id格式错误');
        }

        if ($this->manager['pid'] > 0) {
            $authMIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], 'm_id');
            if (!$authMIds || !in_array($mId, $authMIds)) {
                return $this->rNoData();
            }
        }

        $day = trim($date ?: date('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $day)) {
            return returnValidate('date格式错误，示例：2026-05-27');
        }

        $startAt = $day . ' 00:00:00';
        $endAt = $day . ' 23:59:59';
        $rows = Db::name('sim_signal_log')
            ->where('m_id', $mId)
            ->whereBetweenTime('created_at', $startAt, $endAt)
            ->field('created_at,rsrp,sinr')
            ->order('created_at asc,id asc')
            ->select()
            ->toArray();

        if (!$rows) {
            return returnState(200, lang('query_success'), [
                'cycle' => 'day',
                'date' => $day,
                'm_id' => $mId,
                'series' => [],
            ]);
        }

        $pointsRsrp = [];
        $pointsSinr = [];
        foreach ($rows as $row) {
            $label = date('H:i', strtotime($row['created_at']));
            $pointsRsrp[] = [
                'label' => $label,
                'value' => $row['rsrp'],
            ];
            $pointsSinr[] = [
                'label' => $label,
                'value' => $row['sinr'],
            ];
        }

        return returnState(200, lang('query_success'), [
            'cycle' => 'day',
            'date' => $day,
            'm_id' => $mId,
            'series' => [
                [
                    'name' => 'rsrp',
                    'points' => $pointsRsrp,
                ],
                [
                    'name' => 'sinr',
                    'points' => $pointsSinr,
                ],
            ],
        ]);
    }
}
