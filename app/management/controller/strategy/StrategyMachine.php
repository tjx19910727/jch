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
        $postData['ao_id'] = $this->manager['ao_id'];
        return $this->app->strategyMachine->addStrategyMachine($postData);
    }

    public function unbind()
    {
        $postData = input();
        return $this->app->strategyMachine->del($postData);
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

    /**
     * 导出收款绑定
     * @return array|string
     */
    public function exportStrategyMachine()
    {
        $postData = input();
        $where = $this->getWhere($postData);
        return $this->app->strategyMachine->exportStrategyMachine($where);
    }
}