<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/15
 * Time: 14:33
 */

namespace app\AppFactory\Kernel\Traits\Activity;


use app\AppFactory\Kernel\Model\Activity\Pick\ActivityPickCodeModel;

trait ActivityPickCodeTrait
{

    public function getActivityPickCodeValue($where,$value)
    {
        return ActivityPickCodeModel::getFieldValue($where,$value);
    }

    public function getActivityPickCodeColumn($where,$column)
    {
        return ActivityPickCodeModel::getColumn($where,$column);
    }

    public function getActivityPickCodeFind($where,$field = "*",$order = "apc_id desc")
    {
        return ActivityPickCodeModel::getFind($where,$field,$order);
    }

    public function getActivityPickCodeList($where,$pageNum = 0,$field = "*", $order = "apc_id desc")
    {
        return ActivityPickCodeModel::getList($where,$pageNum,$field,$order);
    }

    public function addActivityPickCodeMore($insertAll)
    {
        $apc = new ActivityPickCodeModel();
        return $apc->saveAll($insertAll);
    }

    public function addActivityPickCode($insert)
    {
        $apc = ActivityPickCodeModel::create($insert);
        return $apc->id;
    }

    public function updateActivityPickCode($update,$where = [],$field = [])
    {
        return ActivityPickCodeModel::update($update,$where,$field);
    }

    public function delActivityPickCode($where)
    {
        return ActivityPickCodeModel::whereDel($where);
    }
}