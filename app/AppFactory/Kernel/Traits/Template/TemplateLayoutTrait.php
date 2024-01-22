<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/20
 * Time: 14:43
 */

namespace app\AppFactory\Kernel\Traits\Template;


use app\AppFactory\Kernel\Model\Template\TemplateLayoutModel;

trait TemplateLayoutTrait
{

    public function getTemplateLayoutFind($where,$field = "*",$order = "")
    {
        return TemplateLayoutModel::getFind($where,$field,$order);
    }

    public function getTemplateLayoutList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "")
    {
        return TemplateLayoutModel::getList($where,$pageNum,$field,$order,$eachFun);
    }

    public function addTemplateLayout($insert)
    {
        !isset($this->manager['manager_id']) ? :$insert['creator'] = $this->manager['manager_id'];
        $data = TemplateLayoutModel::create($insert);
        return $data->id;
    }

    public function updateTemplateLayout($update,$where = [],$field = [])
    {
        !isset($this->manager['manager_id']) ? : $update['update_id'] = $this->manager['manager_id'];
        return TemplateLayoutModel::update($update,$where,$field);
    }

    public function delTemplateLayout($where)
    {
        $result = TemplateLayoutModel::destroy($where);
        return $result;
    }
}