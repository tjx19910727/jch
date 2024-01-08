<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/12/6
 * Time: 14:30
 */

namespace app\management\controller\activity;


use app\management\controller\Common;

class ActivityHg extends Common
{
    protected $validatePath = 'app\management\validate\VActivity.';

    public function getList()
    {
        $postData = input();
        $where = $this->getWhere($postData,false,
            ["store_name" => "like","terminal_no" => "like","activity_name" => "like","start_date" => "between","end_date" => "between"]);
        return $this->app->activityHg->getList($where,$postData['pageNum'] ?? 0);
    }

    /**
     * 获取活动详情
     * @return array|string
     */
    public function getDetails()
    {
        $ah_id = input("ah_id");
        return $this->app->activityHg->getDetails(['ah_id' => $ah_id]);
    }

    public function add()
    {
        $postData = input();
        actionLog($postData,"添加加价换购活动");
        try {
            $this->validate($postData, $this->validatePath . "addHg");
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->activityHg->addInfo($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . "updateHg");
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->activityHg->update($postData);
    }

    public function del()
    {
        $ah_id = input('ah_id');
        if (!$ah_id) return returnState(100,'活动ID不能为空');
        return $this->app->activityHg->del(['ah_id' => $ah_id]);
    }
}