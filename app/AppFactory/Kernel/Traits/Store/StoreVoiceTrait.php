<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/9/21
 * Time: 16:41
 */

namespace app\AppFactory\Kernel\Traits\Store;


use app\AppFactory\Kernel\Model\Store\StoreVoiceModel;

trait StoreVoiceTrait
{
    public function getStoreVoiceFind($where,$field = "*",$order = "")
    {
        return StoreVoiceModel::getFind($where,$field,$order);
    }

    public function getStoreVoiceList($where,$pageNum = 0,$field = "*", $order = "")
    {
        return StoreVoiceModel::getList($where,$pageNum,$field,$order);
    }

    public function addStoreVoice($insert)
    {
        $insert['creator'] = $this->manager['manager_id'] ?? 0;
        $sv = StoreVoiceModel::create($insert);
        return $sv->sv_id;
    }

    public function updateStoreVoice($update, $where = [], $field = ["title","file_path","type","status"])
    {
        return StoreVoiceModel::update($update,$where,$field);
    }

    public function delStoreVoice($where)
    {
        return StoreVoiceModel::destroy($where);
    }
}