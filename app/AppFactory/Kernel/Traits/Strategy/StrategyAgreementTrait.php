<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/8
 * Time: 15:40
 */

namespace app\AppFactory\Kernel\Traits\Strategy;


use app\AppFactory\Kernel\Model\Strategy\StrategyAgreementModel;
use think\facade\Cache;

trait StrategyAgreementTrait
{
    public function getStrategyAgreementList($where, $pageNum = 0, $field = "*", $order = "")
    {
        return StrategyAgreementModel::getList($where, $pageNum, $field, $order);
    }

    public function getStrategyAgreementFind($where, $field = "*", $order = "")
    {
        return StrategyAgreementModel::getFind($where, $field, $order);
    }

    public function addStrategyAgreement($insert)
    {
        if (isset($this->manager['manager_id']))
            $insert['creator'] = $this->manager['manager_id'];
        $sa = StrategyAgreementModel::create($insert);
        return $sa->sa_id;
    }

    public function updateStrategyAgreement($update, $where = [], $field = [])
    {
        if (isset($this->manager['manager_id']))
            $update['update_id'] = $this->manager['manager_id'];
        $result = StrategyAgreementModel::update($update, $where, $field);
        if ($result) {
            $sa = $this->getStrategyAgreementFind(['sa_id' => $update['sa_id']])->toArray();
            Cache::set("StrategyAgreement" . $sa['sa_id'], $sa);
        }
        return $result;
    }

    public function delStrategyAgreement($where)
    {
        return StrategyAgreementModel::destroy($where);
    }

    /**
     * 通过门店ID获取门店免责协议
     * @param $store_id
     * @return array
     */
    public function getStrategyAgreementByStoreId($store_id)
    {
        $ss = $this->getStrategyStoreFind(['store_id' => $store_id, 's_type' => 7], '*', 'ss_id desc');
        if (!$ss) return $this->rFail("门店未绑定免责协议");
        $sa = Cache::get("StrategyAgreement" . $ss['s_id']);
        if (!$sa) {
            $field = "*";
            $sa = $this->getStrategyAgreementFind(['sa_id' => $ss['s_id'], 'status' => 1,'type' => 1], $field);
            if (!$sa) return $this->rFail("查无免责协议");
            $sa = $sa->toArray();
            Cache::set("StrategyAgreement" . $sa['sa_id'], $sa);
        }
        return $sa;
    }
}