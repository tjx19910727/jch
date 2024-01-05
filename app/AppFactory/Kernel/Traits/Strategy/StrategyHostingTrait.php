<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/8
 * Time: 15:40
 */

namespace app\AppFactory\Kernel\Traits\Strategy;


use app\AppFactory\Kernel\Model\Strategy\StrategyHostingModel;
use think\facade\Cache;

trait StrategyHostingTrait
{
    public function getStrategyHostingList($where, $pageNum = 0, $field = "*", $order = "")
    {
        return StrategyHostingModel::getList($where, $pageNum, $field, $order);
    }

    public function getStrategyHostingFind($where, $field = "*", $order = "")
    {
        return StrategyHostingModel::getFind($where, $field, $order);
    }

    public function addStrategyHosting($insert)
    {
        if (isset($this->manager['manager_id']))
            $insert['creator'] = $this->manager['manager_id'];
        $st = StrategyHostingModel::create($insert);
        return $st->st_id;
    }

    public function updateStrategyHosting($update, $where = [], $field = [])
    {
        if (isset($this->manager['manager_id']))
            $update['update_id'] = $this->manager['manager_id'];
        $result = StrategyHostingModel::update($update, $where, $field);
        if ($result) {
            $st = $this->getStrategyHostingFind(['st_id' => $update['st_id']])->toArray();
            Cache::set("StrategyHosting" . $st['st_id'], $st);
        }
        return $result;
    }

    public function delStrategyHosting($where)
    {
        return StrategyHostingModel::destroy($where);
    }

    /**
     * 通过门店ID获取托管策略
     * @param $store_id
     * @return array
     */
    public function getStrategyHostingByStoreId($store_id)
    {
        $ss = $this->getStrategyStoreFind(['store_id' => $store_id, 's_type' => 6], '*', 'ss_id desc');
        if (!$ss) return $this->rFail("门店未绑定托管规则");
        $st = Cache::get("StrategyHosting" . $ss['s_id']);
        if (!$st) {
            $field = "st_id,st_name,charge_type,charge_value,charge_max_limit,cycle,status,creator,update_id,create_time,update_time";
            $st = $this->getStrategyHostingFind(['st_id' => $ss['s_id'], 'status' => 1], $field)->toArray();
            if (!$st) return $this->rFail("查无收费规则信息");
            Cache::set("StrategyHosting" . $st['st_id'], $st);
        }
        return $st;
    }
}