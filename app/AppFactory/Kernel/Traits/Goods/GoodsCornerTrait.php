<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/16
 * Time: 9:00
 */

namespace app\AppFactory\Kernel\Traits\Goods;


use app\AppFactory\Kernel\Model\Goods\GoodsCornerModel;

trait GoodsCornerTrait
{
    public function getGoodsCornerColumn($where,$column,$key = "")
    {
        return GoodsCornerModel::getColumn($where,$column,$key);
    }
    public function getGoodsCornerFind($where,$field = "*",$order = "id desc")
    {
        return GoodsCornerModel::getFind($where,$field,$order);
    }

    public function getGoodsCornerList($where,$pageNum = 0, $field = "*",$order = "id desc",$eachFunc = "")
    {
        return GoodsCornerModel::getList($where,$pageNum,$field,$order,$eachFunc);
    }

    public function getGoodsCornerListByMachine($where,$pageNum = 0,$field = "*",$order = "",$eachFunc = "")
    {
        return GoodsCornerModel::getListByAmAg($where,$pageNum,$field,$order,$eachFunc);
    }

    public function getGoodsCornerFindByAmAg($where,$field = "*",$order = "")
    {
        return GoodsCornerModel::getFindByAmAg($where,$field,$order);
    }

    public function addGoodsCorner($insert)
    {
        if (isset($this->manager['manager_id']))  $insert['creator'] = $this->manager['manager_id'] ?? 0;
        $corner = GoodsCornerModel::create($insert);
        return $corner->id;
    }

    public function updateGoodsCorner($update,$where = [],$field = [])
    {
        if (isset($this->manager['manager_id'])) $update['update_id'] = $this->manager['manager_id'] ?? 0;
        return GoodsCornerModel::update($update,$where,$field);
    }

    public function delGoodsCorner($where)
    {
        return GoodsCornerModel::whereDel($where);
    }
}