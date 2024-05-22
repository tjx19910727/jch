<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/17
 * Time: 15:25
 */

namespace app\AppFactory\Kernel\Traits\Goods;


use app\AppFactory\Kernel\Model\Goods\GoodsChangeModel;

trait GoodsChangeTrait
{

    public function getGoodsChangeCount($where)
    {
        return GoodsChangeModel::getCount($where);
    }

    public function getGoodsChangeValue($where,$value)
    {
        return GoodsChangeModel::getFieldValue($where,$value);
    }

    public function getGoodsChangeColumn($where,$column)
    {
        return GoodsChangeModel::getColumn($where,$column);
    }

    public function getGoodsChangeFind($where,$field = "*",$order = "")
    {
        return GoodsChangeModel::getFind($where,$field,$order);
    }

    public function getGoodsChangeList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "",$group = '')
    {
        return GoodsChangeModel::getList($where,$pageNum,$field,$order,$eachFun,$group);
    }

    private $desc = [
        "1" => [
            "1" => "未知",
            "2" => "货架上货新商品",
            "3" => "货架下货旧商品",
        ],
        "2" => [
            "1" => "未知",
            "2" => "设备商品库上货备用库存",
            "3" => "设备商品库下货备用库存",
        ],
    ];

    public function addGoodsChange($insert)
    {
        if (!isset($insert['desc'])) $insert['desc'] = $this->desc[$insert['position']][$insert['type']];
        $data = GoodsChangeModel::create($insert);
        return $data->change_id;
    }

    public function updateGoodsChange($update,$where = [],$field = [])
    {
        return GoodsChangeModel::update($update,$where,$field);
    }

    public function delGoodsChange($where)
    {
        $result = GoodsChangeModel::whereDel($where);
        return $result;
    }

}