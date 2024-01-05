<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/21
 * Time: 8:58
 */

namespace app\AppFactory\Kernel\Traits\Warehouse;


use app\AppFactory\Kernel\Model\Warehouse\WarehouseCheckModel;
use app\management\validate\VWarehouseCheck;
use think\exception\ValidateException;

trait WarehouseCheckTrait
{
    /**
     * 获取盘点单列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return WarehouseCheckModel|WarehouseCheckModel[]|array|\think\Collection|\think\Paginator
     * @throws \Exception
     */
    public function getWarehouseCheckList($where,$pageNum = 0, $field = "*",$order = "wc_id desc")
    {
        return WarehouseCheckModel::getList($where,$pageNum,$field,$order);
    }

    /**
     * 查询一条盘点单数据
     * @param $where
     * @param string $field
     * @param string $order
     * @return WarehouseCheckModel|array|mixed|null|\think\Model
     */
    public function getWarehouseCheckFind($where,$field= "*",$order = "")
    {
        return WarehouseCheckModel::getFind($where,$field,$order);
    }

    /**
     * 获取盘点单字段求和数据
     * @param $where
     * @param $sum
     * @return float
     */
    public function getWarehouseCheckSum($where,$sum)
    {
        return WarehouseCheckModel::getSum($where,$sum);
    }

    /**
     * 添加盘点单
     * @param $insert
     * @return mixed
     */
    public function addWarehouseCheck($insert)
    {
        $scene = $insert['type'] == 1 ? "addStoreDetails" : "addWhDetails";
        $insert['manager_id'] = $this->manager['manager_id'];
        $insert['manager_nickname'] = $this->manager['nickname'];
        $details = $insert['details'];
        unset($insert['details']);
        $flag = [];
        $this->startTrans();
        $wc = WarehouseCheckModel::create($insert);
        $wc_id = $wc->wc_id;
        if ($wc_id) {
            $flag[] = 1;
            $cargo_damage_quantity = 0;
            $cargo_damage_amount = 0;
            $where['store_id'] = $insert['store_id'];
            foreach ($details as $key => $value) {
                $value['wc_id'] = $wc_id;
                // 缺货计算货损
                if ($value['stock'] > $value['actual_stock']) {
                    $where['shelves_number'] = $value['shelves_number'];
                    $price = $this->getStoreShelvesValue($where,'cost_price');
                    $quantity = bcsub($value['stock'],$value['actual_stock']);
                    $value['cost_price'] = $price;
                    $value['cargo_damage_quantity'] = $quantity;
                    $value['cargo_damage_amount'] = bcmul($price,$quantity,3);
                    $cargo_damage_quantity = bcadd($cargo_damage_quantity,$value['cargo_damage_quantity']);
                    $cargo_damage_amount = bcadd($cargo_damage_amount,$value['cargo_damage_amount']);
                }
                try {
                    validate(VWarehouseCheck::class)->scene($scene)->check($value);
                    $flag[] = $this->addWarehouseCheckDetails($value);
                } catch (ValidateException $e) {
                    $this->rollbackTrans();
                    return $this->rValidate("【" . $value['goods_name'] . "】" . $e->getMessage());
                }
            }
            WarehouseCheckModel::update(['wc_id' => $wc_id,'cargo_damage_quantity' => $cargo_damage_quantity,'cargo_damage_amount' => $cargo_damage_amount]);
        }
        $result = flag_check($flag);
        return $this->checkTrans($result);
    }
}