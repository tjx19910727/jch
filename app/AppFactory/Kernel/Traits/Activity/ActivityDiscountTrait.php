<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/11/7
 * Time: 11:05
 */

namespace app\AppFactory\Kernel\Traits\Activity;


use app\AppFactory\Kernel\Model\Activity\ActivityDiscountModel;

trait ActivityDiscountTrait
{
    /**
     * 获取限时活动列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return ActivityDiscountModel|ActivityDiscountModel[]|array|\think\Collection|\think\Paginator
     * @throws \Exception
     */
    public function getActivityDiscountList($where,$pageNum = 0, $field = "*", $order = "ad_id desc")
    {
        return ActivityDiscountModel::getList($where,$pageNum,$field,$order);
    }

    /**
     * 获取一条限时活动
     * @param $where
     * @param string $field
     * @param string $order
     * @return ActivityDiscountModel|array|mixed|null|\think\Model
     */
    public function getActivityDiscountFind($where,$field = "*",$order = "ad_id desc")
    {
        $data = ActivityDiscountModel::getFind($where,$field,$order);
        return $data;
    }

    /**
     * 添加限时活动
     * @param $insert
     * @return mixed
     */
    public function addActivityDiscount($insert)
    {
        $insert['creator'] = $this->manager['manager_id'] ?? 0;
        $ad = ActivityDiscountModel::create($insert);
        return $ad->ad_id;
    }

    /**
     * 修改限时活动
     * @param $update
     * @param array $where
     * @param array $field
     * @return ActivityDiscountModel
     */
    public function updateActivityDiscount($update, $where = [], $field = [])
    {
        if (isset($this->manager['manager_id'])) $update['update_id'] = $this->manager['manager_id'];
        return ActivityDiscountModel::update($update,$where,$field);
    }

    /**
     * 删除限时活动
     * @param $where
     * @return bool
     */
    public function delActivityDiscount($where)
    {
        return ActivityDiscountModel::destroy($where);
    }

    public function calculateADOrder()
    {

    }
}