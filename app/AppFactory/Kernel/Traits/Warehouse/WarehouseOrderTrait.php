<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/21
 * Time: 8:59
 */

namespace app\AppFactory\Kernel\Traits\Warehouse;


use app\AppFactory\Kernel\Model\Warehouse\WarehouseGoodsModel;
use app\AppFactory\Kernel\Model\Warehouse\WarehouseOrderGoodsModel;
use app\AppFactory\Kernel\Model\Warehouse\WarehouseOrderModel;
use app\Management\validate\VWarehouseOrder;
use think\exception\ValidateException;

trait WarehouseOrderTrait
{
    /**
     * 获取一条仓库订单
     * @param $where
     * @param string $field
     * @param string $order
     * @return WarehouseOrderModel|array|mixed|null|\think\Model
     */
    public function getWarehouseOrderFind($where,$field = "*",$order = "")
    {
        return WarehouseOrderModel::getFind($where,$field,$order);
    }

    public function getWarehouseOrderDetails($where,$field = "*",$order = "")
    {
        return $this->getWarehouseOrderGoodsList($where,0,$field,$order);
    }

    /**
     * 获取仓库订单列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return WarehouseOrderModel|WarehouseOrderModel[]|array|\think\Collection|\think\Paginator
     * @throws \Exception
     */
    public function getWarehouseOrderList($where,$pageNum = 0,$field = "*",$order = "")
    {
        return WarehouseOrderModel::getList($where,$pageNum,$field,$order,function ($item) {
            $item['province'] = $this->getCityValue(['city_id' => $item['province_id']],'city_title');
            $item['city'] = $this->getCityValue(['city_id' => $item['city_id']],'city_title');
            $item['area'] = $this->getCityValue(['city_id' => $item['area_id']],'city_title');
            return $item;
        });
    }

    /**
     * 修改仓库订单
     * @param $update
     * @param array $where
     * @param array $allowField
     * @return WarehouseOrderModel
     */
    public function updateWarehouseOrder($update,$where = [],$allowField = [])
    {
        return WarehouseOrderModel::update($update,$where,$allowField);
    }

    /**
     * 创建仓库出入库单
     * @param $insert
     * @return mixed
     */
    // insert  receiver,total_quantity contacts  mobile  province_id city_id  area_id address logistics_no  remark  wgList
    public function addWarehouseOrder($insert)
    {
        $en = 0;
        $wgList = $insert['wgList'];
        unset($insert['wgList']);
        $sendWh = $this->getWarehouseFind(['manager_id' => $this->manager['manager_id']]);
        $insert['send_wh_id'] = $sendWh['wh_id'];
        $insert['sender'] = $this->manager['manager_id'];
        $insert['send_stock'] = $this->getWarehouseGoodsSum(['wh_id' => $sendWh['wh_id']],'stock');
        $receiveWh = $sendWh;
        // 接收人跟创建人为同一人
        if ($this->manager['manager_id'] == $insert['receiver']) {
            $insert['receive_stock'] = $insert['send_stock'];
            $insert['status'] = 2;
            $insert['receive_time'] = time();
            $en = 1;
        } else {
            $receiveWh = $this->getWarehouseFind(['manager_id' => $insert['receiver']]);
            $insert['receive_stock'] = $this->getWarehouseGoodsSum(['wh_id' => $receiveWh['wh_id']],'stock');
        }
        if (!$receiveWh) return $this->rFail("查无收货人仓库，请先创建仓库");
        $insert['receive_wh_id'] = $receiveWh['wh_id'];
        $insert['creator'] = $this->manager['manager_id'];
        $flag = [];
        $this->startTrans();
        $wo = WarehouseOrderModel::create($insert);
        $wo_id = $wo->wo_id;
        if ($wo_id) {
            $flag[] = 1;
            // wg_id goods_id wg_name  wg_pic  bar_code batch_number  manufacture_time  cost_price  retail_price  sell_by_date before_stock quantity   desc
            foreach ($wgList as $key => $value) {
                try {
                    validate(VWarehouseOrder::class)->scene("woDetails")->check($value);
                } catch (ValidateException $e) {
                    $this->rollbackTrans();
                    return $this->rValidate($e->getError());
                }
//                $value['manufacture_time'] = strtotime($value['manufacture_time']);
                $insertWog = $value;
                $whereWg = [];
                $whereWg['goods_id'] = $value['goods_id'];
                $whereWg['wg_id'] = $value['wg_id'];
                if (isset($value['batch_number']) && $value['batch_number']) $whereWg['batch_number'] = $value['batch_number'];
                $wg = $this->getWarehouseGoodsFind($whereWg);
                // 入库单
                if ($en) {
                    if ($wg) {
                        $flag[] = $this->incWarehouseGoods(['wg_id' => $wg['wg_id']],'stock', $value['quantity']);
                    }
                    if (!$wg) {
                        $wg = [
                            "wh_id" => $receiveWh['wh_id'],
                            "wh_name" => $receiveWh['wh_name'],
                            "goods_id" => $value['goods_id'],
                            "wg_name" => $value['wg_name'],
                            "wg_pic" => $value['wg_pic'],
                            "bar_code" => $value['bar_code'],
                            "batch_number" => $value['batch_number'],
                            "manufacture_time" => $value['manufacture_time'],
                            "cost_price" => $value['cost_price'],
                            "retail_price" => $value['retail_price'],
                            "sell_by_date" => $value['sell_by_date'],
                            "stock" => $value['quantity'],
                        ];
                        $wg['wg_id'] = $this->addWarehouseGoods($wg);
                        $flag[] = $wg['wg_id'] ? 1 : 0;
                    }
                }
                // 出库单
                if (!$en) {
                    if (!$wg) {
                        $this->rollbackTrans();
                        return $this->rFail("查无出库商品数据");
                    }
                    $flag[] = $this->decWarehouseGoods(['wg_id' => $value['wg_id']],'stock',$value['quantity']);
                }
                $insertWog['wo_id'] = $wo_id;
                $wog = WarehouseOrderGoodsModel::create($insertWog);
                $wog_id = $wog->wog_id;
                $flag[] = $wog_id ? 1 : 0;
            }
            if (!$en) {
                $config = [
                    "folder" => "enQr/" . date("Ymd"),
                    "name" => "en_" . $wo_id,
                    "logoPath" => true,
                ];
                $qrCode = $this->makeQr("",$config);
                if ($qrCode['state'] != 200) {
                    $this->rollbackTrans();
                    return json($qrCode);
                }
                $flag[] = $this->updateWarehouseOrder(['wo_id' => $wo_id,"qr_code" => $qrCode['data']]);
            }
        }
        return $this->checkTrans($flag);
    }

    /**
     * 确认收货或拒绝收货
     * @param $id
     * @param $status
     * @return array|string
     */
    public function receiptOrRejection($id,$status)
    {
        $check = $this->checkFrequency();
        if ($check !== true) return $check;
        if (!$status) return returnState(100,'操作类型不能为空');
        $wo = $this->getWarehouseOrderFind(['wo_id' => $id]);
        $wo = $wo->toArray();
        if (!$wo) return returnState(100,'查无入库单');
        if ($wo['status'] == 2) return returnState(100, "该入库单已收货，请勿重复点击");
        if ($wo['status'] == 3) return returnState(100, "该入库单已拒绝收货，请勿重复点击");
        if ($wo['status'] == 4) return returnState(100, "该入库单已撤销收货，请勿重复点击");
        $this->startTrans();
        $wog = $this->getWarehouseOrderGoodsList(['wo_id' => $id]);
        foreach ($wog as $key => $value) {
            $flag[] = WarehouseGoodsModel::setInc(['wg_id' => $value['wg_id']],'stock',$value['quantity']);
        }
        $flag[] = $this->updateWarehouseOrder(['wo_id' => $id,'status' => $status]);
        return $this->checkTrans($flag);
    }

    /**
     * 确认收货
     * 没有仓库，新增仓库
     * 没有仓库商品，新增仓库商品
     * 有仓库商品，库存数量增加
     * @return array
     */
    public function receipt_wo($id)
    {
        $check = $this->checkFrequency();
        if ($check !== true) return $check;
        $wo = $this->getWarehouseOrderFind(['wo_id' => $id]);
        if ($wo['status'] == 2) return returnState(100, "该入库单已收货，请勿重复点击");
        if ($wo['status'] == 3) return returnState(100, "该入库单已拒绝收货，请勿重复点击");
        if ($wo['status'] == 4) return returnState(100, "该入库单已撤销收货，请勿重复点击");
        $wog = $this->getWarehouseOrderGoodsList(['wo_id' => $id]);
        $wh = $this->getWarehouseFind(['wh_id' => $wo['receive_wh_id']]);
        $this->startTrans();
        // 没有收货仓库，优先创建一个
        if (!$wh) {
            $receiver = $this->getAuthManagerFind(['manager_id' => $wo['receiver']]);
            $wh = [
                "wh_name" => $receiver['nickname'] . "仓库",
                "province_id" => $wo['province_id'],
                "province" => $this->getCityValue(['city_id' =>$wo['province_id']],'city_fullname'),
                "city_id" => $wo['city_id'],
                "city" => $this->getCityValue(['city_id' =>$wo['city_id']],'city_fullname'),
                "area_id" => $wo['area_id'],
                "area" => $this->getCityValue(['city_id' =>$wo['area_id']],'city_fullname'),
                "address" => $wo['address'],
                "manager_id" => $wo['receiver'],
            ];
            $wh['wh_id'] = $this->addWarehouse($wh);
        }
        foreach ($wog as $key => $value) { // 创建新批次仓库商品信息
            $wg = $this->getWarehouseGoodsFind(['goods_id' => $value['goods_id'],'batch_number' => $value['batch_number']]);
            if (!$wg) {
                $wg = [
                    "wh_id" => $wh['wh_id'],
                    "wh_name" => $wh['wh_name'],
                    "goods_id" => $value['goods_id'],
                    "wg_name" => $value['wg_name'],
                    "wg_pic" => $value['wg_pic'],
                    "bar_code" => $value['bar_code'],
                    "batch_number" => $value['batch_number'],
                    "manufacture_time" => $value['manufacture_time'],
                    "cost_price" => $value['cost_price'],
                    "retail_price" => $value['retail_price'],
                    "sell_by_date" => $value['sell_by_date'],
                    "stock" => $value['quantity'],
                    "warning_stock" => 0,
                    "status" => 1,
                ];
                $wg['wg_id'] = $this->addWarehouseGoods($wg);
                if (!$wg['wg_id']) $flag[] = 0;
            } else {
                $flag[] = $this->incWarehouseGoods(['wg_id' => $wg['wg_id']],'stock',$value['quantity']);
            }
        }
        $update['wo_id'] = $id;
        $update['status'] = 2;
        $flag[] = $this->updateWarehouseOrder($update);
        return $this->checkTrans($flag);
    }

}