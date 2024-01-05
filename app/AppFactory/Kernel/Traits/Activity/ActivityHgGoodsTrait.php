<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/12/6
 * Time: 13:57
 */

namespace app\AppFactory\Kernel\Traits\Activity;


use app\AppFactory\Kernel\Model\Activity\ActivityHgGoodsModel;

trait ActivityHgGoodsTrait
{

    /**
     * 获取换购活动内容列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return ActivityHgGoodsModel|ActivityHgGoodsModel[]|array|\think\Collection|\think\Paginator
     * @throws \Exception
     */
    public function getActivityHgGoodsList($where,$pageNum = 0, $field = "*", $order = "")
    {
        return ActivityHgGoodsModel::getList($where,$pageNum,$field,$order);
    }

    /**
     * 添加换购活动内容
     * @param $insert
     * @return mixed
     */
    public function addActivityHgGoods($insert)
    {
        $ag = ActivityHgGoodsModel::create($insert);
        return $ag->ag_id;
    }

    /**
     * 修改换购活动内容
     * @param $update
     * @param array $where
     * @param array $field
     * @return ActivityHgGoodsModel
     */
    public function updateActivityHgGoods($update,$where = [],$field = [])
    {
        return ActivityHgGoodsModel::update($update,$where,$field);
    }

    /**
     * 删除换购活动内容
     * @param $where
     * @return bool
     */
    public function delActivityHgGoods($where)
    {
        return ActivityHgGoodsModel::destroy($where);
    }
}