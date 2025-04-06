<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/26
 * Time: 10:14
 */

namespace app\management\controller\activity;


use app\management\controller\Common;

class ActivityFd extends Common
{

    protected $field = "fd_id,fd_name,start_date,end_date,fd_type,condition_type,`desc`,exclusion,status,creator,create_time";
    protected $validatePath = 'app\management\validate\Activity\VActivityFd.';

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ["fd_name" => "like"]);
        return $this->app->activityFd->getList($where,$pageNum,$this->field,'fd_id desc');
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->activityFd->getFdAmFind($where,$this->field);
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->activityFd->addFd($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->activityFd->updateFd($postData);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->activityFd->delFd($postData);
    }


    /**
     * 满减满送活动主动下架
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
        if (strpos($postData['fd_id'],",") !== false) $where[] = ['fd_id',"in",$postData['fd_id']];
        else $where['fd_id'] = $postData['fd_id'];
        return $this->app->activityFd->fdTakeDown($where);
    }
}