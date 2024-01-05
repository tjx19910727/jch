<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/26
 * Time: 14:13
 */

namespace app\AppFactory\Kernel\Traits\Store;


use app\AppFactory\Kernel\Model\Store\StoreShelvesModel;

trait StoreShelvesTrait
{
    public function getStoreShelvesValue($where,$value)
    {
        return StoreShelvesModel::getFieldValue($where,$value);
    }

    /**
     * @param $where
     * @param string $field
     * @param string $order
     * @return StoreShelvesModel|array|mixed|null|\think\Model
     */
    public function getStoreShelvesFind($where,$field = "*",$order = "")
    {
        $data = StoreShelvesModel::getFind($where,$field,$order);
        return $data;
    }

    /**
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return StoreShelvesModel|StoreShelvesModel[]|array|\think\Collection|\think\Paginator
     * @throws \Exception
     */
    public function getStoreShelvesList($where,$pageNum = 0,$field = "*",$order = "")
    {
        return StoreShelvesModel::getList($where,$pageNum,$field,$order);
    }

    public function addStoreShelves($insert)
    {
        $shelves = $this->getStoreShelvesFind(['store_id' => $insert['store_id'],'shelves_number' => $insert['shelves_number']]);
        if ($shelves) return $this->rFail("货架编号已存在");
        $insert['creator'] = $this->manager['manager_id'];
        $sp = StoreShelvesModel::create($insert);
        return $sp->ss_id;
    }

    public function updateStoreShelves($update,$where = [],$field = [])
    {
        if (isset($this->manager['manager_id'])) $update['update_id'] = $this->manager['manager_id'];
        return StoreShelvesModel::update($update,$where,$field);
    }

    public function delStoreShelves($where)
    {
        return StoreShelvesModel::destroy($where);
    }

    public function incStoreShelves($where,$field,$inc = 1)
    {
        return StoreShelvesModel::setInc($where,$field,$inc);
    }

    public function decStoreShelves($where,$field,$dec = 1)
    {
        $result = StoreShelvesModel::setDec($where,$field,$dec);
        return $result;
    }

    public function checkShelves($shelves)
    {
        if (!$shelves) return $this->rFail("查无货架商品信息");
        if ($shelves['shelves_status'] == 2) return $this->rFail("货架商品已被禁用" . $shelves['shelves_number']);
//        if ($shelves['stock'] <= 0) return $this->rFail("货架商品库存不足" . $shelves['shelves_number']);
        return true;
    }

    /**
     * 更换货架商品
     * @param $postData
     * @return mixed
     */
    public function changeGoods($postData)
    {
        $ss = $this->getStoreShelvesFind(['ss_id' => $postData['ss_id']], '*');
        $ss = obj2arr($ss);
        if (!$ss) {
            $this->rollbackTrans();
            return $this->rFail('查无货架信息');
        }
        $flag[] = true;
        if ($ss['goods_id'] != $postData['goods_id']) {
            $postData['stock'] = 0;
            $wg = $this->getWarehouseGoodsFind(['wg_id' => $postData['wg_id']]);
            if ($wg) {
                $wg = $wg->toArray();
                $wg['stock'] = bcadd($wg['stock'], $ss['stock']);
                $flag[] = $this->updateWarehouseGoods($wg);
                $insertRep = [
                    "store_id" => $ss['store_id'],
                    "store_name" => $ss['store_name'],
                    "ss_id" => $ss['ss_id'],
                    "shelves_number" => $ss['shelves_number'],
                    "goods_id" => $ss['goods_id'],
                    "goods_name" => $ss['goods_name'],
                    "wg_id" => $ss['wg_id'],
                    "goods_pic" => $ss['goods_pic'],
                    "bar_code" => $ss['bar_code'],
                    "batch_number" => $ss['batch_number'],
                    "before_stock" => $ss['stock'],
                    "rep_type" => 2,
                    "quantity" => 0 - $ss['stock'],
                ];
                $flag[] = $this->addStoreShelvesRep($insertRep);
            }
            $flag[] = $this->updateStoreShelves($postData, [], ["goods_id", "wg_id", 'goods_name', 'goods_c_id', 'goods_c_name', 'goods_pic', 'cost_price', 'retail_price', 'stock', 'bar_code', 'batch_number', 'manufacture_time', 'sell_by_date']);
        }
        $result = flag_check($flag);
        return $result ? true : $this->rFail('更换货架商品失败');
    }
}