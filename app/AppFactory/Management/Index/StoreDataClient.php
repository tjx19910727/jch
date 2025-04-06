<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/15
 * Time: 9:33
 */

namespace app\AppFactory\Management\Index;


use app\AppFactory\Kernel\Traits\Store\StoreOnlineTrait;
use app\AppFactory\Kernel\Traits\Store\StoreTrait;
use app\AppFactory\Management\ManagementClient;

class StoreDataClient extends ManagementClient
{
    use StoreTrait;
    use StoreOnlineTrait;

    public function initWhere(&$where)
    {
        $childIds = $this->app->authManager->getChildIdList($this->manager['manager_id']);
        $childIds[] = $this->manager['manager_id'];
        $where[] = ['store_manager', 'in', $childIds];
    }

    /**
     * 获取门店汇总数据
     * @return array|string
     */
    public function getSummary($where = [])
    {
        if (!$where) {
            $this->initWhere($where);
        }

        // 总门店数量
        $data['totalStore'] = $this->getStoreCount($where);

        // 云值守门店数量
        $whereUnattended = $where;
        $whereUnattended[] = ['store_type', 'like', '%1%'];
        $data['storeUn'] = $this->getStoreCount($whereUnattended);

        // 云仓门店数量
        $whereWh = $where;
        $whereWh[] = ['store_type', 'like', '%2%'];
        $data['storeWh'] = $this->getStoreCount($whereWh);

        // 总在线时长
        $whereOnline = $where;
        $data['duration'] = $this->getStoreOnlineSum($whereOnline,'duration');

        return $this->r(200, '汇总成功', $data);
    }

}