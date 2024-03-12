<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/20
 * Time: 10:58
 */

namespace app\AppFactory\Kernel\Traits\Goods;


use app\AppFactory\Kernel\Model\Activity\ActivityGoodsModel;
use app\AppFactory\Kernel\Model\Goods\GoodsModel;
use app\AppFactory\Kernel\Model\Machine\MachineChannelModel;
use app\AppFactory\Kernel\Model\Machine\MachineGoodsModel;

trait GoodsTrait
{
    /**
     * 获取商品信息
     * @param $where
     * @param string $field
     * @param string $order
     * @return mixed
     */
    public function getGoodsFind($where,$field = "*",$order = "")
    {
        return GoodsModel::getFind($where,$field,$order);
    }

    public function getGoodsList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "",$group = "",$limit = "")
    {
//        if (!$eachFun) {
//            $eachFun = function ($item) {
//                if (isset($item['creator'])) $item['creator_name'] = $this->getAuthManagerValue(['manager_id' => $item['creator']], 'nickname');
//                return $item;
//            };
//        }
        return GoodsModel::getList($where,$pageNum,$field,$order,$eachFun,$group,$limit);
    }

    public function addMoreGoods($data)
    {
        $goods = new GoodsModel();
        return $goods->saveAll($data);
    }

    public function addGoods($insert)
    {
        !isset($this->manager['manager_id']) ? :$insert['creator'] = $this->manager['manager_id'];
        !isset($this->manager['ao_id']) ? :$insert['ao_id'] = $this->manager['ao_id'];
        $data = GoodsModel::create($insert);
        return $data->g_id;
    }

    public function updateGoods($update,$where = [],$field = [],$updateType = 1)
    {
        !isset($this->manager['manager_id']) ? : $update['update_id'] = $this->manager['manager_id'];
        $result = GoodsModel::update($update,$where,$field);
        if ($result && $updateType) {
            $new = GoodsModel::getFind(['g_id' => $result['g_id']],'g_id,g_name,gc_id,gc_name,pic,sku,bar_code')->toArray();
            MachineGoodsModel::update($new,['g_id' => $result['g_id']]);
            MachineChannelModel::update($new,['g_id' => $result['g_id']]);
            ActivityGoodsModel::update([
                'g_name' => $new['g_name'],
                'pic' => $new['pic'],
                'sku' => $new['sku'],
            ],['g_id' => $result['g_id']]);
        }
        return $result;
    }

    public function delGoods($where)
    {
        return GoodsModel::whereDel($where);
    }
}