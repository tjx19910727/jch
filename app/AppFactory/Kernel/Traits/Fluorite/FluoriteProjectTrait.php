<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/6
 * Time: 17:30
 */

namespace app\AppFactory\Kernel\Traits\Fluorite;


use app\AppFactory\Kernel\Model\Fluorite\FluoriteProjectModel;

trait FluoriteProjectTrait
{
    public function getProjectFind($where,$field = "*", $order = "fp_id desc")
    {
        return FluoriteProjectModel::getFind($where,$field,$order);
    }

    public function getProjectList($where,$pageNum = 0,$field = "*", $order = "fp_id desc")
    {
        return FluoriteProjectModel::getList($where,$pageNum,$field,$order);
    }

    public function addFluoriteProject($insert)
    {
        $insert['creator'] = $this->manager['manager_id'] ?? 1;
        $fp = FluoriteProjectModel::create($insert);
        return $fp->fp_id;
    }

    public function updateFluoriteProject($update,$where = [],$field = [])
    {
        $update['update_id'] = $this->manager['manager_id'] ?? 1;
        $result = FluoriteProjectModel::update($update,$where,$field);
        return $result;
    }


}