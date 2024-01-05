<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/20
 * Time: 20:05
 */

namespace app\AppFactory\Kernel\Traits\Goods;


use app\AppFactory\Kernel\Model\Goods\GoodsCategoryModel;

trait GoodsCategoryTrait
{
    public function getGoodsCategoryFind($where,$field = "*",$order = "")
    {
        return GoodsCategoryModel::getFind($where,$field,$order);
    }

    public function getGoodsCategoryList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "")
    {
        return GoodsCategoryModel::getList($where,$pageNum,$field,$order,$eachFun);
    }

    public function addGoodsCategory($insert)
    {
        $insert['creator'] = $this->manager['manager_id'];
        $data = GoodsCategoryModel::create($insert);
        return $data->gc_id;
    }

    public function updateGoodsCategory($update,$where = [],$field = [])
    {
        $update['update_id'] = $this->manager['manager_id'];
        return GoodsCategoryModel::update($update,$where,$field);
    }

    public function delGoodsCategory($where)
    {
        $result = GoodsCategoryModel::destroy($where);
        return $result;
    }
}