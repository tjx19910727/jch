<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/30
 * Time: 17:39
 */

namespace app\AppFactory\Kernel\Traits\Strategy;


use app\AppFactory\Kernel\Model\Strategy\StrategyManagerModel;

trait StrategyManagerTrait
{
    public function getStrategyManagerFind($where, $field = "*",$order = "")
    {
        $data = StrategyManagerModel::getFind($where,$field,$order);
        return $data;
    }

    public function getStrategyManagerList($where,$pageNum = 0,$field = "*", $order = "",$eachFn = null, $group = "")
    {
        $data = StrategyManagerModel::getList($where,$pageNum,$field,$order,$eachFn,$group);
        return $data;
    }

    public function addStrategyManager($insert)
    {
        $data = StrategyManagerModel::create($insert);
        return $data->sm_id;
    }

    public function updateStrategyManager($update,$where = [], $field = [])
    {
        return StrategyManagerModel::update($update,$where,$field);
    }

    public function delStrategyManager($where)
    {
        return StrategyManagerModel::destroy($where);
    }


}