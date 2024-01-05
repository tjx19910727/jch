<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/20
 * Time: 10:58
 */

namespace app\AppFactory\Kernel\Traits\Goods;


use app\AppFactory\Kernel\Model\Goods\GoodsModel;

trait GoodsTrait
{
    public function getGoodsFind($where,$field = "*",$order = "")
    {
        return GoodsModel::getFind($where,$field,$order);
    }

    public function getGoodsList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "")
    {
        if (!$eachFun) {
            $eachFun = function ($item) {
                if (isset($item['creator'])) $item['creator_name'] = $this->getAuthManagerValue(['manager_id' => $item['creator']], 'nickname');
                return $item;
            };
        }
        return GoodsModel::getList($where,$pageNum,$field,$order,$eachFun);
    }

    public function addMoreGoods($data)
    {
        $goods = new GoodsModel();
        return $goods->saveAll($data);
    }

    public function addGoods($insert)
    {
        $insert['creator'] = $this->manager['manager_id'];
        $data = GoodsModel::create($insert);
        return $data->goods_id;
    }

    public function updateGoods($update,$where = [],$field = [])
    {
        $update['update_id'] = $this->manager['manager_id'];
        return GoodsModel::update($update,$where,$field);
    }

    public function delGoods($where)
    {
        return GoodsModel::destroy($where);
    }
}