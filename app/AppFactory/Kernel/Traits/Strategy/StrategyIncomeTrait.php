<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/10
 * Time: 14:30
 */

namespace app\AppFactory\Kernel\Traits\Strategy;


use app\AppFactory\Kernel\Model\Strategy\StrategyIncomeModel;

trait StrategyIncomeTrait
{
    public function getStrategyIncomeFind($where,$field = "*", $order = "")
    {
        return StrategyIncomeModel::getFind($where,$field,$order);
    }

    /**
     * 通过门店ID获取分润策略
     * @param $store_id
     * @return StrategyIncomeModel[]|array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getStrategyIncomeByStoreId($store_id)
    {
        $where['store_id'] = $store_id;
        $where['s_type'] = 3;
        $field = "si.si_id,si.income_value,si.transfer_method";
        return StrategyIncomeModel::getJoinStrategyStoreList($where,$field,'si.si_id desc');
    }

    public function getStrategyIncomeList($where,$pageNum = 0, $field = "*", $order = "")
    {
        return StrategyIncomeModel::getList($where,$pageNum,$field,$order);
    }

    public function addStrategyIncome($insert)
    {
        if (isset($this->manager['manager_id'])) {
            $insert['creator'] = $this->manager['manager_id'];
        }
        $si = StrategyIncomeModel::create($insert);
        return $si->si_id;
    }

    public function updateStrategyIncome($update, $where = [], $field = [])
    {
        return StrategyIncomeModel::update($update,$where,$field);
    }

    public function delStrategyIncome($where)
    {
        return StrategyIncomeModel::destroy($where);
    }
}