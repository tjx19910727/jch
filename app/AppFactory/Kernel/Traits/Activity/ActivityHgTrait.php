<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/12/6
 * Time: 13:57
 */

namespace app\AppFactory\Kernel\Traits\Activity;


use app\AppFactory\Kernel\Model\Activity\ActivityHgModel;

trait ActivityHgTrait
{
    public function getActivityHgList($where,$pageNum = 0, $field = "*", $order = "ah_id desc")
    {
        return ActivityHgModel::getList($where,$pageNum,$field,$order);
    }

    public function getActivityHgFind($where,$field = "*", $order = "ah_id desc")
    {
        return ActivityHgModel::getFind($where,$field,$order);
    }

    public function addActivityHg($insert)
    {
        if (isset($this->manager['manager_id'])) $insert['creator'] = $this->manager['manager_id'];
        $hg = ActivityHgModel::create($insert);
        return $hg->ah_id;
    }

    public function updateActivityHg($update,$where = [],$field = [])
    {
        if (isset($this->manager['manager_id'])) $update['update_id'] = $this->manager['manager_id'];
        return ActivityHgModel::update($update,$where,$field);
    }

    public function delActivityHg($where)
    {
        return ActivityHgModel::whereDel($where);
    }
}