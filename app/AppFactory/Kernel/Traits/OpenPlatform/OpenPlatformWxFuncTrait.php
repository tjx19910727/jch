<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/14
 * Time: 10:50
 */

namespace app\AppFactory\Kernel\Traits\OpenPlatform;


use app\AppFactory\Kernel\Model\OpenPlatform\OpenPlatformWxFuncModel;

trait OpenPlatformWxFuncTrait
{
    public function getOpenPlatformWxFuncList($where,$pageNum = 0, $field = "*", $order = "")
    {
        return OpenPlatformWxFuncModel::getList($where,$pageNum,$field,$order);
    }

    public function getOpenPlatformWxFuncFind($where,$field = "*", $order = "")
    {
        return OpenPlatformWxFuncModel::getFind($where,$field,$order);
    }

    public function addOpenPlatformWxFunc($insert)
    {
        $wf = OpenPlatformWxFuncModel::create($insert);
        return $wf->wf_id;
    }

    public function updateOpenPlatformWxFunc($update,$where = [],$field = [])
    {
        return OpenPlatformWxFuncModel::update($update,$where,$field);
    }
}