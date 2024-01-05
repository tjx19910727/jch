<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/11/7
 * Time: 11:32
 */

namespace app\AppFactory\Kernel\Traits\Activity;


use app\AppFactory\Kernel\Model\Activity\ActivityGoodsModel;

trait ActivityGoodsTrait
{
    /**
     * 获取活动商品列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return ActivityGoodsModel|ActivityGoodsModel[]|array|\think\Collection|\think\Paginator
     * @throws \Exception
     */
    public function getActivityGoodsList($where,$pageNum = 0, $field = "*", $order = "")
    {
        return ActivityGoodsModel::getList($where,$pageNum,$field,$order);
    }

    /**
     * 添加活动商品
     * @param $insert
     * @return mixed
     */
    public function addActivityGoods($insert)
    {
        $ag = ActivityGoodsModel::create($insert);
        return $ag->ag_id;
    }

    /**
     * 修改活动商品
     * @param $update
     * @param array $where
     * @param array $field
     * @return ActivityGoodsModel
     */
    public function updateActivityGoods($update,$where = [],$field = [])
    {
        return ActivityGoodsModel::update($update,$where,$field);
    }

    /**
     * 删除活动商品
     * @param $where
     * @return bool
     */
    public function delActivityGoods($where)
    {
        return ActivityGoodsModel::destroy($where);
    }
}