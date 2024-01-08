<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/11/9
 * Time: 13:54
 */

namespace app\management\controller\activity;


use app\management\controller\Common;

class ActivityTime extends Common
{
    protected $validatePath = 'app\management\validate\VActivity.';

    /**
     * 获取活动时间列表
     * @return mixed
     */
    public function getList()
    {
        $postData = input();
        $where = $this->getWhere($postData,false,[]);
        return $this->app->activityTime->getList($where,$postData['pageNum'] ?? 0, "at_id,a_id,a_type,start_time,end_time");
    }

    /**
     * 添加活动时间列表
     * @return array|mixed|string
     */
    public function add()
    {
        $postData = input();
        return $this->app->activityTime->add($postData);
    }

    /**
     * 修改活动时间
     * @return array|mixed|string
     */
    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData,$this->validatePath . "updateTime");
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->activityTime->update($postData);
    }

    /**
     * 删除活动时间
     * @return mixed
     */
    public function del()
    {
        $id = input("at_id");
        $where['at_id'] = $id;
        return $this->app->activityTime->del($where);
    }
}