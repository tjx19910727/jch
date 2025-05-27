<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/16
 * Time: 9:00
 */

namespace app\AppFactory\Kernel\Traits\Goods;


use app\AppFactory\Kernel\Model\Goods\GoodsLangModel;

trait GoodsLangTrait
{
    public function getGoodsLangFind($where,$field = "*",$order = "gl_id desc")
    {
        return GoodsLangModel::getFind($where,$field,$order);
    }

    public function getGoodsLangList($where,$pageNum = 0, $field = "*",$order = "gl_id desc")
    {
        return GoodsLangModel::getList($where,$pageNum,$field,$order);
    }

    public function addGoodsLang($insert)
    {
        if (isset($this->manager['manager_id']))  $insert['creator'] = $this->manager['manager_id'] ?? 0;
        $gl = GoodsLangModel::create($insert);
        return $gl->gl_id;
    }

    public function updateGoodsLang($update,$where = [],$field = [])
    {
        if (isset($update['lang'],$update['g_id'],$update['g_name'])) unset($update['lang'],$update['g_id'],$update['g_name']);
        if (isset($this->manager['manager_id'])) $update['update_id'] = $this->manager['manager_id'] ?? 0;
        return GoodsLangModel::update($update,$where,$field);
    }

    public function delGoodsLang($where)
    {
        return GoodsLangModel::whereDel($where);
    }
}