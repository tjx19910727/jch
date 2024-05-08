<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/3
 * Time: 9:31
 */

namespace app\AppFactory\Kernel\Traits\Wx;


use app\AppFactory\Kernel\Model\Wx\WxTemplateModel;

trait WxTemplateTrait
{
    public function getWxTemplateList($where,$pageNum = 0, $field = "",$order = "")
    {
        return WxTemplateModel::getList($where,$pageNum,$field,$order);
    }

    public function getWxTemplateFind($where,$field = "*", $order = "")
    {
        return WxTemplateModel::getFind($where,$field,$order);
    }

    public function addWxTemplate($insert)
    {
        $wt = WxTemplateModel::create($insert);
        return $wt->wt_id;
    }

    public function updateWxTemplate($update,$where = [],$field = [])
    {
        return WxTemplateModel::update($update,$where,$field);
    }

    public function delWxTemplate($where)
    {
        return WxTemplateModel::whereDel($where);
    }
}