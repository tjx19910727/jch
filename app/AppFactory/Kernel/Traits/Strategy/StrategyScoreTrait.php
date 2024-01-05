<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/12/7
 * Time: 17:49
 */

namespace app\AppFactory\Kernel\Traits\Strategy;


use app\AppFactory\Kernel\Model\Strategy\StrategyScoreModel;

trait StrategyScoreTrait
{
    /**
     * 获取积分策略列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return StrategyScoreModel|StrategyScoreModel[]|array|\think\Collection|\think\Paginator
     * @throws \Exception
     */
    public function getStrategyScoreList($where, $pageNum = 0, $field = "*", $order = "ss_id desc")
    {
        return StrategyScoreModel::getList($where, $pageNum, $field, $order);
    }

    /**
     * 获取一条积分策略
     * @param $where
     * @param string $field
     * @param string $order
     * @return StrategyScoreModel|array|mixed|null|\think\Model
     */
    public function getStrategyScoreFind($where,$field = "*", $order = "")
    {
        return StrategyScoreModel::getFind($where,$field,$order);
    }

    /**
     * 添加积分策略
     * @param $insert
     * @return mixed
     */
    public function addStrategyScore($insert)
    {
        if (isset($this->manager['manager_id'])) $insert['creator'] = $this->manager['manager_id'];
        $ss = StrategyScoreModel::create($insert);
        return $ss->ss_id;
    }

    /**
     * 修改积分策略
     * @param $update
     * @param array $where
     * @param array $field
     * @return StrategyScoreModel
     */
    public function updateStrategyScore($update,$where = [], $field = [])
    {
        if (isset($this->manager['manager_id'])) $update['update_id'] = $this->manager['manager_id'];
        return StrategyScoreModel::update($update,$where,$field);
    }
}