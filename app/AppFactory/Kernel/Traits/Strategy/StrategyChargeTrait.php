<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/8
 * Time: 15:40
 */

namespace app\AppFactory\Kernel\Traits\Strategy;


use app\AppFactory\Kernel\Model\Strategy\StrategyChargeModel;
use think\facade\Cache;

trait StrategyChargeTrait
{
    public function getStrategyChargeList($where,$pageNum = 0,$field = "*",$order = "")
    {
        return StrategyChargeModel::getList($where,$pageNum,$field,$order);
    }

    public function getStrategyChargeFind($where,$field = "*",$order = "")
    {
        return StrategyChargeModel::getFind($where,$field,$order);
    }

    public function addStrategyCharge($insert)
    {
        $insert['creator'] = $this->manager['manager_id'];
        $sc = StrategyChargeModel::create($insert);
        return $sc->sc_id;
    }

    public function updateStrategyCharge($update,$where = [], $field = [])
    {
        $update['update_id'] = $this->manager['manager_id'];
        $result = StrategyChargeModel::update($update,$where,$field);
        if ($result) {
            $sc = $this->getStrategyChargeFind(['sc_id' => $update['sc_id']])->toArray();
            Cache::set("strategyCharge" . $sc['sc_id'],$sc);
        }
        return $result;
    }

    public function delStrategyCharge($where)
    {
        return StrategyChargeModel::destroy($where);
    }

    /**
     * 通过门店ID获取收费策略
     * @param $store_id
     * @return array
     */
    public function getStrategyChargeByStoreId($store_id)
    {
        $ss = $this->getStrategyStoreFind(['store_id' => $store_id,'s_type' => 2],'*','ss_id desc');
        if (!$ss) return $this->rFail("门店未绑定收费规则");
        $sc = Cache::get("strategyCharge" . $ss['s_id']);
        if (!$sc) {
            $field = "sc_id,charge_name,numerical_value,cycle,charge_type,charge_mode,creator";
            $sc = $this->getStrategyChargeFind(['sc_id' => $ss['s_id'], 'status' => 1], $field)->toArray();
            if (!$sc) return $this->rFail("查无收费规则信息");
            Cache::set("strategyCharge" . $sc['sc_id'],$sc);
        }
        return $sc;
    }
}