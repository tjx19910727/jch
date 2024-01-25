<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/16
 * Time: 9:00
 */

namespace app\AppFactory\Kernel\Traits\Goods;


use app\AppFactory\Kernel\Model\Goods\GoodsCategoryLangModel;

trait GoodsCategoryLangTrait
{
    public function getGoodsCategoryLangFind($where,$field = "*",$order = "gcl_id desc")
    {
        return GoodsCategoryLangModel::getFind($where,$field,$order);
    }

    public function getGoodsCategoryLangList($where,$pageNum = 0, $field = "*",$order = "gcl_id desc")
    {
        return GoodsCategoryLangModel::getList($where,$pageNum,$field,$order);
    }

    public function addGoodsCategoryLang($insert)
    {
        if (isset($this->manager['manager_id']))  $insert['creator'] = $this->manager['manager_id'] ?? 0;
        $gcl = GoodsCategoryLangModel::create($insert);
        return $gcl->gcl_id;
    }

    public function updateGoodsCategoryLang($update,$where = [],$field = [])
    {
        if (isset($this->manager['manager_id'])) $update['update_id'] = $this->manager['manager_id'] ?? 0;
        return GoodsCategoryLangModel::update($update,$where,$field);
    }

    public function delGoodsCategoryLang($where)
    {
        return GoodsCategoryLangModel::whereDel($where);
    }
}