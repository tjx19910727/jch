<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/11/7
 * Time: 11:01
 */

namespace app\management\controller\activity;


use app\management\controller\Common;

class ActivityDiscount extends Common
{
    protected $validatePath = 'app\management\validate\VActivity.';

    /**
     * 获取限时折扣列表
     * @return mixed
     */
    public function getList()
    {
        $postData = input();
        $where = $this->getWhere($postData,false,
            ["store_name" => "like","terminal_no" => "like","activity_name" => "like","start_date" => "between","end_date" => "between"]
        );
        return $this->app->activityDiscount->getList($where,$postData['pageNum'] ?? 0);
    }

    /**
     * 获取活动详情
     * @return array|string
     */
    public function getDetails()
    {
        $ad_id = input("ad_id");
        return $this->app->activityDiscount->getDetails(['ad_id' => $ad_id]);
    }

    /**
     * 添加限时折扣活动
     * @return array|string
     */
    public function add()
    {
        $postData = input();
        actionLog($postData,"添加限时折扣活动");
        try {
            $this->validate($postData, $this->validatePath . "addDiscount");
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->activityDiscount->addInfo($postData);
    }

    /**
     * 修改限时折扣活动主体信息
     * @return array|mixed|string
     */
    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . "updateDiscount");
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->activityDiscount->update($postData);
    }

    /**
     * 删除限时折扣活动信息
     * @return bool|string
     */
    public function del()
    {
        $a_id = input('ad_id');
        return $this->app->activityDiscount->delInfo($a_id);
    }
}