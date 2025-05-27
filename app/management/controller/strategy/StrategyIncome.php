<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/10
 * Time: 14:34
 */

namespace app\management\controller\strategy;


use app\management\controller\Common;

class StrategyIncome extends Common
{
    protected $validatePath = 'app\management\validate\VStrategyIncome.';

    /**
     * 查询分润策略列表
     * @return mixed
     */
    public function getList()
    {
        $postData = input();
        $where = $this->getWhere($postData,false,['income_name' => 'like']);
        $pageNum = $postData['pageNum'] ?? 0;
        return $this->app->strategyIncome->getList($where,$pageNum,"*", "si_id desc");
    }

    /**
     * 查询一条分润策略信息
     * @return array|string
     */
    public function getFind()
    {
        $si_id = input("si_id");
        return $this->app->strategyIncome->getFind(['si_id' => $si_id]);
    }

    /**
     * 添加分润策略
     * @return array|mixed|string
     */
    public function add()
    {
        $postData = input();
        try { $this->validate($postData,$this->validatePath .'addSi');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        return $this->app->strategyIncome->add($postData);
    }

    /**
     * 修改分润策略
     * @return array|mixed|string
     */
    public function update()
    {
        $postData = input();
        try { $this->validate($postData,$this->validatePath .'updateSi');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        return $this->app->strategyIncome->update($postData);
    }

    /**
     * 删除一条分润策略
     * @return mixed
     */
    public function del()
    {
        $si_id = input("si_id");
        return $this->app->strategyIncome->del($si_id);
    }
}