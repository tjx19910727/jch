<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/26
 * Time: 8:45
 */

namespace app\management\controller\activity;


use app\management\controller\Common;

class ActivityLotteryUsed extends Common
{

    protected $field = "*";
//    protected $validatePath = 'app\management\validate\V.';

    /**
     * 查询抽奖活动使用记录列表
     * @return mixed
     */
    public function getList()
    {
        $pageNum = input('pageNum');
        $al_id = input('al_id');
        $where['al_id'] = $al_id;
        return $this->app->activityLotteryUsed->getUsedList($where,$pageNum,$this->field,'alu_id desc');
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->activityLotteryUsed->getFind($where,$this->field);
    }

    /**
     * 获取抽奖使用活动商品列表
     * @return array|string
     */
    public function getGoodsList()
    {
        $alu_id = input("alu_id");
        $pageNum = input('pageNum');
        $where['alu_id'] = $alu_id;
        return $this->app->activityLotteryUsed->getUsedGoodsList($where,$pageNum,"*");
    }

    /**
     * 导出活动使用列表
     * @return array|string
     */
    public function exportAlUsed()
    {
        $al_id = input('al_id');
        return $this->app->activityLotteryUsed->exportList($al_id);
    }
}