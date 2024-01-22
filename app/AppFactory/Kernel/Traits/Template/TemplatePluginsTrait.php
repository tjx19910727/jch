<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/20
 * Time: 14:43
 */

namespace app\AppFactory\Kernel\Traits\Template;


use app\AppFactory\Kernel\Model\Template\TemplatePluginsModel;

trait TemplatePluginsTrait
{

    public function getTemplatePluginsFind($where,$field = "*",$order = "")
    {
        return TemplatePluginsModel::getFind($where,$field,$order);
    }

    public function getTemplatePluginsList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "")
    {
        return TemplatePluginsModel::getList($where,$pageNum,$field,$order,$eachFun);
    }

    public function addTemplatePlugins($insert)
    {
        !isset($this->manager['manager_id']) ? :$insert['creator'] = $this->manager['manager_id'];
        $data = TemplatePluginsModel::create($insert);
        return $data->id;
    }

    public function updateTemplatePlugins($update,$where = [],$field = [])
    {
        !isset($this->manager['manager_id']) ? : $update['update_id'] = $this->manager['manager_id'];
        return TemplatePluginsModel::update($update,$where,$field);
    }

    public function delTemplatePlugins($where)
    {
        $result = TemplatePluginsModel::destroy($where);
        return $result;
    }
}