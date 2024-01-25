<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/30
 * Time: 17:53
 */

namespace app\management\controller\strategy;


use app\management\controller\Common;

class StrategyMachine extends Common
{
    protected $validatePath = 'app\management\validate\VStrategyMachine.';

    public function bind()
    {
        $postData = input();
        try { $this->validate($postData,$this->validatePath . 'bind');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        return $this->app->strategyMachine->add($postData,0);
    }

    public function unbind()
    {
        $id = input('sm_id');
        if (!$id) return returnState(100,'策略ID不能为空');
        return $this->app->strategyMachine->del($id);
    }

    public function update()
    {
        $postData = input();
        return $this->app->strategyMachine->update($postData);
    }

    public function getList()
    {
        $postData = input();
        $where = $this->getWhere($postData);
        $pageNum = $postData['pageNum'] ?? 0;
        return $this->app->strategyMachine->getList($where,$pageNum,"*","sm_id desc");
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData);
        return $this->app->strategyMachine->getFind($where,"*","sm_id desc");
    }
}