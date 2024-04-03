<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/15
 * Time: 14:40
 */

namespace app\management\controller\activity;


use app\management\controller\Common;

class ActivityPick extends Common
{

    protected $field = "*";
    protected $validatePath = 'app\management\validate\Activity\VActivityPick.';

    /**
     * 获取提货码活动列表
     * @return mixed
     */
    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, []);
        $this->field .= ",
        (SELECT count(apc_id) FROM activity_pick_code apc WHERE  apc.ap_id = a.`id` ) codeNum";
        return $this->app->activityPick->getApAgAmList($where,$pageNum,$this->field,'id desc');
    }

    /**
     * 获取一条提货码活动
     * @return array|string
     */
    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->activityPick->getApAgAmFind($where,$this->field);
    }

    /**
     * 添加提货码活动信息
     * @return array|string
     */
    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->activityPick->addAp($postData);
    }

    /**
     * 修改提货码活动信息
     * @return array|bool|string
     */
    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->activityPick->updateAp($postData);
    }

    /**
     * 删除提货码活动信息
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
        return $this->app->activityPick->del($postData);
    }

    /**
     * 提货码主动下架
     * @return array|bool|string
     */
    public function takeDown()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'takeDown');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->activityPick->activeTakeDown($postData);
    }
}