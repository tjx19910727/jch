<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/26
 * Time: 9:58
 */

namespace app\AppFactory\Kernel\Traits\Activity;


use app\AppFactory\Kernel\Model\Activity\Fd\ActivityFdContentModel;

trait ActivityFdContentTrait
{
    
    public function getActivityFdContentFind($where,$field = "*",$order = "")
    {
        return ActivityFdContentModel::getFind($where,$field,$order);
    }

    public function getActivityFdContentList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "")
    {
        return ActivityFdContentModel::getList($where,$pageNum,$field,$order,$eachFun);
    }

    public function addActivityFdContentMore($insertAll)
    {
        $afd = new ActivityFdContentModel();
        return $afd->saveAll($insertAll);
    }

    public function addActivityFdContent($insert)
    {
        $data = ActivityFdContentModel::create($insert);
        return $data->fdc_id;
    }

    public function updateActivityFdContent($update,$where = [],$field = [])
    {
        return ActivityFdContentModel::update($update,$where,$field);
    }

    public function delActivityFdContent($where)
    {
        return ActivityFdContentModel::whereDel($where);
    }

}