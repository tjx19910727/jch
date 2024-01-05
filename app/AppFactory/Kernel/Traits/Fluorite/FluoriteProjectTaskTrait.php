<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/25
 * Time: 9:41
 */

namespace app\AppFactory\Kernel\Traits\Fluorite;


use app\AppFactory\Kernel\Model\Fluorite\FluoriteProjectTaskModel;

trait FluoriteProjectTaskTrait
{
    public function getFluoriteProjectTaskModel()
    {
        return new FluoriteProjectTaskModel();
    }

    public function getFluoriteProjectTaskList($where,$pageNum = 0,$field = "*",$order = "fpt_id desc")
    {
        return FluoriteProjectTaskModel::getList($where,$pageNum,$field,$order);
    }

    public function getFluoriteProjectTaskFind($where,$field = "*", $order = "fpt_id desc")
    {
        return FluoriteProjectTaskModel::getFind($where,$field,$order);
    }

    public function addFluoriteProjectTask($insert)
    {
        $fpt = FluoriteProjectTaskModel::create($insert);
        return $fpt->fpt_id;
    }

    public function updateFluoriteProjectTask($update, $where = [], $field = [])
    {
        return FluoriteProjectTaskModel::update($update,$where,$field);
    }
}