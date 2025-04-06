<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/31
 * Time: 14:15
 */

namespace app\AppFactory\Management\Index;


use app\AppFactory\Kernel\Traits\Store\StoreTrait;
use app\AppFactory\Kernel\Traits\Warehouse\WarehouseCheckTrait;
use app\AppFactory\Management\ManagementClient;

class CargoDamageDataClient extends ManagementClient
{
    use StoreTrait;
    use WarehouseCheckTrait;

    /**
     * 汇总数据
     * @param $where
     * @return array|string
     */
    public function getSummary($where = [])
    {
        if (!$where) {
            $childIds = $this->app->authManager->getChildIdList($this->manager['manager_id']);
            $childIds[] = $this->manager['manager_id'];
            $storeIds = $this->getStoreColumn([['store_manager', 'in', $childIds]], 'store_id');
            $where[] = ['store_id', 'in', $storeIds];
        }

        $where['status'] = 2;

        $whereToday = $where;
        $whereToday[] = ['create_time','>',strtotime(date("Y-m-d 00:00:00"))];
        $data['todayCargoDamageAmount'] = $this->getWarehouseCheckSum($whereToday,'cargo_damage_amount');

        $whereMonth = $where;
        $whereMonth[] = ['create_time','>',strtotime(date("Y-m-01 00:00:00"))];
        $data['monthCargoDamageAmount'] = $this->getWarehouseCheckSum($whereMonth,'cargo_damage_amount');

        $whereYear = $where;
        $whereYear[] = ['create_time','>',strtotime(date("Y-01-01 00:00:00"))];
        $data['yearCargoDamageAmount'] = $this->getWarehouseCheckSum($whereYear,'cargo_damage_amount');

        return $this->r(200,'汇总货损成功',$data);
    }
}