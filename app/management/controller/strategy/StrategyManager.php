<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/30
 * Time: 17:53
 */

namespace app\management\controller\strategy;


use app\management\controller\Common;

class StrategyManager extends Common
{

    /**
     * 绑定账号
     * @return mixed
     */
    public function bind()
    {
        $postData = input();
        $result = $this->app->strategyManager->add($postData);
//        dump($result);
        return $result;
    }

    /**
     * 查询绑定账号列表
     * @return mixed
     */
    public function getList()
    {
        $postData = input();
        $where = $this->getWhere($postData);
        $pageNum = $postData['pageNum'] ?? 0;
        return $this->app->strategyManager->getList($where, $pageNum, "*", "sm_id desc");
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData);
        return $this->app->strategyManager->getFind($where, "*", "sm_id desc");
    }

    /**
     * 取消绑定账号
     * @return array|mixed|string
     */
    public function unbind()
    {
        $id = input('sm_id');
        if (!$id) return returnState(100, '策略ID不能为空');
        return $this->app->strategyManager->del($id);
    }


}