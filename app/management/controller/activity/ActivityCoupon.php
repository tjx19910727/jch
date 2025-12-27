<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/4
 * Time: 14:07
 */

namespace app\management\controller\activity;


use app\management\controller\Common;

class ActivityCoupon extends Common
{

    protected $field = "*";
    protected $validatePath = 'app\management\validate\Activity\VActivityCoupon.';

    /**
     * 查询优惠券活动列表
     * @return mixed
     */
    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ["c_name" => "like"]);
        $where['ao_id'] = $this->manager['ao_id'];
        $this->field .= ",
        (CASE WHEN code > 0 THEN 0 ELSE (SELECT count(cu_id) FROM activity_coupon_used cu WHERE  cu.c_id = a.`c_id` ) END) codeNum";
        return $this->app->activityCoupon->getAcAgAmList($where,$pageNum,$this->field,'c_id desc');
    }

    /**
     * 查询一条优惠券活动列表
     * @return array|string
     */
    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->activityCoupon->getAcAgAmFind($where,$this->field);
    }

    /**
     * 生成一条优惠券活动
     * @return array|mixed|string
     */
    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->activityCoupon->addAc($postData);
    }

    /**
     * 修改优惠券活动内容
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
        return $this->app->activityCoupon->updateAc($postData);
    }

    /**
     * 删除优惠券活动
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
        return $this->app->activityCoupon->del($postData);
    }

    /**
     * 优惠券主动下架
     * @return array|bool|string
     */
    public function takeDown()
    {
        $c_id = input("c_id");
        strpos($c_id,',') !== false ? $where[] = ['c_id',"in",$c_id] : $where['c_id'] = $c_id;
        return $this->app->activityCoupon->activeTakeDown($where);
    }
}