<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/14
 * Time: 14:58
 */

namespace app\AppFactory\Kernel\Traits\OpenPlatform;


use app\AppFactory\Kernel\Model\OpenPlatform\OpenPlatformWxKeywordsModel;

trait OpenPlatformWxKeywordsTrait
{
    public function getOpenPlatformWxKeywordsList($where,$pageNum = 0,$field = "*", $order = "id desc")
    {
        return OpenPlatformWxKeywordsModel::getList($where,$pageNum,$field,$order);
    }

    public function getOpenPlatformWxKeywordsFind($where,$field = "*", $order = "id desc")
    {
        return OpenPlatformWxKeywordsModel::getFind($where,$field,$order);
    }

    public function addOpenPlatformWxKeywords($insert)
    {
        $insert['creator'] = $this->manager["manager_id"] ?? 0 ;
        $wk = OpenPlatformWxKeywordsModel::create($insert);
        return $wk->id;
    }

    public function updateOpenPlatformWxKeywords($update,$where = [], $field = [])
    {
        return OpenPlatformWxKeywordsModel::update($update,$where,$field);
    }
}