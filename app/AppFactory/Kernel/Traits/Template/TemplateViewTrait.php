<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/20
 * Time: 14:43
 */

namespace app\AppFactory\Kernel\Traits\Template;


use app\AppFactory\Kernel\Model\Template\TemplateViewModel;

trait TemplateViewTrait
{

    public function getTemplateViewFind($where,$field = "*",$order = "")
    {
        return TemplateViewModel::getFind($where,$field,$order);
    }

    public function getTemplateViewList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "")
    {
        return TemplateViewModel::getList($where,$pageNum,$field,$order,$eachFun);
    }

    public function addTemplateView($insert)
    {
        !isset($this->manager['manager_id']) ? :$insert['creator'] = $this->manager['manager_id'];
        if (!isset($insert['ao_id']) || !$insert['ao_id']) $insert['ao_id'] = $this->manager['ao_id'];
        $data = TemplateViewModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    public function updateTemplateView($update,$where = [],$field = [])
    {
        !isset($this->manager['manager_id']) ? : $update['update_id'] = $this->manager['manager_id'];
        return TemplateViewModel::update($update,$where,$field);
    }

    public function delTemplateView($where)
    {
        $result = TemplateViewModel::whereDel($where);
        return $result;
    }
}