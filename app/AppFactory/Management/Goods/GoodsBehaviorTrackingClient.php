<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/6/24
 * Time: 0:00
 */

namespace app\AppFactory\Management\Goods;

use app\AppFactory\Kernel\Traits\Goods\GoodsBehaviorTrackingTrait;
use app\AppFactory\Management\ManagementClient;

class GoodsBehaviorTrackingClient extends ManagementClient
{
    use GoodsBehaviorTrackingTrait;

    public function getTrackingList($where, $pageNum = 0, $field = "*", $order = "")
    {
        $mIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], "m_id");
        if ($mIds) $where[] = ['m_id', 'in', $mIds];
        return $this->rQ($this->getGoodsBehaviorTrackingList($where, $pageNum, $field, $order));
    }
}
