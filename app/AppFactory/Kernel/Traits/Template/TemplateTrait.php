<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/20
 * Time: 14:42
 */

namespace app\AppFactory\Kernel\Traits\Template;


use app\AppFactory\Kernel\Model\Template\TemplateModel;

trait TemplateTrait
{

    public function getTemplateFind($where,$field = "*",$order = "")
    {
        return TemplateModel::getFind($where,$field,$order);
    }

    public function getTemplateList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "")
    {
        return TemplateModel::getList($where,$pageNum,$field,$order,$eachFun);
    }

    public function addTemplate($insert)
    {
        !isset($this->manager['manager_id']) ? :$insert['creator'] = $this->manager['manager_id'];
        $data = TemplateModel::create($insert);
        return $data->id;
    }

    public function updateTemplate($update,$where = [],$field = [])
    {
        !isset($this->manager['manager_id']) ? : $update['update_id'] = $this->manager['manager_id'];
        return TemplateModel::update($update,$where,$field);
    }

    public function delTemplate($where)
    {
        $result = TemplateModel::destroy($where);
        return $result;
    }
}