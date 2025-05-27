<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/3
 * Time: 9:31
 */

namespace app\AppFactory\Kernel\Traits\Wx;


use app\AppFactory\Kernel\Model\Wx\WxTemplateLogModel;

trait WxTemplateLogTrait
{

    public function getWxTemplateLogList($where,$pageNum = 0, $field = "",$order = "")
    {
        return WxTemplateLogModel::getList($where,$pageNum,$field,$order);
    }

    public function getWxTemplateLogFind($where,$field = "*", $order = "")
    {
        return WxTemplateLogModel::getFind($where,$field,$order);
    }

    public function addWxTemplateLog($insert)
    {
        $wtl = WxTemplateLogModel::create($insert);
        return $wtl->wtl_id;
    }

    public function updateWxTemplateLog($update,$where = [],$field = [])
    {
        return WxTemplateLogModel::update($update,$where,$field);
    }

    public function delWxTemplateLog($where)
    {
        return WxTemplateLogModel::whereDel($where);
    }
}