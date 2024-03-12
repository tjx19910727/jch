<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/4
 * Time: 14:20
 */

namespace app\management\controller\activity;


use app\management\controller\Common;

class ActivityCouponUsed extends Common
{

    protected $field = "*";
    protected $validatePath = 'app\management\validate\Activity\VActivityCouponUsed.';

    /**
     * 获取优惠券使用记录列表
     * @return mixed
     */
    public function getList()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'getList');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, []);
        return $this->app->activityCouponUsed->getList($where,$pageNum,$this->field,'cu_id desc');
    }

    /**
     * 获取一条优惠券使用记录
     * @return array|string
     */
    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->activityCouponUsed->getFind($where,$this->field);
    }

    /**
     * 生成随机码列表
     * @return array|string
     * @throws \Exception
     */
    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->activityCouponUsed->addMore($postData);
    }

    /**
     * 修改优惠券使用记录
     * @return array|mixed|string
     */
    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->activityCouponUsed->update($postData);
    }

    /**
     * 删除优惠券使用记录
     * @return array|mixed|string
     */
    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->activityCouponUsed->del($postData);
    }

    /**
     * 导出优惠券码
     * @return array|string
     */
    public function exportCode()
    {
        $postData = input();
        return $this->app->activityCouponUsed->exportCode($postData);
    }

    /**
     * 导出优惠码使用报表
     * @return array|string
     */
    public function exportUsedList()
    {
        $postData = input();
        return $this->app->activityCouponUsed->exportUsedList($postData);
    }
}