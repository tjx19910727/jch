<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/30
 * Time: 13:52
 */

namespace app\AppFactory\Kernel\Traits\GatewayWorker;


use app\AppFactory\Kernel\Support\Validate\GatewayWorker\VTerminal;

trait OrderTrait
{
    public function submitExtractOrder()
    {
        $extractCode = $this->message['data']['extractCode'];
    }

    /**
     * 提交购物车生成订单
     * @return mixed
     */
    protected function submitShoppingCart()
    {
        $flag = [];
        $subData = $this->message['data'];
        $insertOrder = [
            "trade_no" => $this->getSaleOrdersTradeNo(),
            "store_id" => $this->store['store_id'],
            "store_name" => $this->store['store_name'],
            "store_manager" => $this->store['store_manager'],
            "terminal_no" => $this->message['terminal_no'],
            "order_type" => $subData['order_type'],
        ];
        $this->startTrans();
        $order_id = $this->addSaleOrders($insertOrder);
        if (!$order_id) {
            $this->rollbackTrans();
            return $this->rFail("创建订单失败");
        }
        if ($order_id) {
            $flag[] = 1;

            // 购物车数据
            $cart = $subData['cart'];
            $totalCostPrice = 0;
            $totalRetailPrice = 0;
            $totalQuantity = 0;
            foreach ($cart as $key => $value) {
                try {
                    validate(VTerminal::class)->scene("submitShoppingCartData")->check($value);
                } catch (\Exception $e) {
                    $this->rollbackTrans();
                    return $this->rValidate($e->getMessage());
                }
                $shelves = $this->getStoreShelvesFind(['ss_id' => $value['ss_id'],'store_id' => $subData['store_id']]);
                $check = $this->checkShelves($shelves);
                if ($check !== true) {
                    $this->rollbackTrans();
                    return $check;
                }
                if (is_object($shelves)) $shelves = $shelves->toArray();
                $insertDetails = [
                    "order_id" => $order_id,
                    "ss_id" => $value['ss_id'],
                    "shelves_number" => $shelves['shelves_number'],
                    "wg_id" => $shelves['wg_id'],
                    "goods_id" => $shelves['goods_id'],
                    "goods_name" => $shelves['goods_name'],
                    "goods_pic" => $shelves['goods_pic'],
                    "gc_id" => $shelves['goods_c_id'],
                    "gc_name" => $shelves['goods_c_name'],
                    "cost_price" => $shelves['cost_price'],
                    "retail_price" => $shelves['retail_price'],
                    "total_sod_price" => bcmul($shelves['retail_price'],$value['quantity'],3),
                    "quantity" => $value['quantity'],
                    "bar_code" => $shelves['bar_code'],
                    "batch_number" => $shelves['batch_number'],
                    "manufacture_time" => $shelves['manufacture_time'],
                    "sell_by_date" => $shelves['sell_by_date'],
                ];
                $totalCostPrice = bcadd($totalCostPrice,bcmul($insertDetails['cost_price'],$insertDetails['quantity'],3),3);
                $totalRetailPrice = bcadd($totalRetailPrice,$insertDetails['total_sod_price'],3);
                $totalQuantity = bcadd($totalQuantity,$insertDetails['quantity']);
                $sod_id = $this->addSaleOrdersDetails($insertDetails);
                $flag[] = $sod_id ? 1:0;
            }
            $flag[] = $this->updateSaleOrders(['order_id' => $order_id,'cost_price' => $totalCostPrice,'total_price' => $totalRetailPrice,'total_quantity' => $totalQuantity]);
        }
        $result = flag_check($flag);
        $result ? $this->commitTrans():$this->rollbackTrans();
        if ($result) {
            $order = $this->getSaleOrdersFind(["order_id" => $order_id]);
            $order->details = $this->getSaleOrdersDetailsList(['order_id' => $order_id]);
            $this->order = $order->toArray();
            $this->order['paymentUrlLink'] = $this->getUrl("/mobile/mini.entrance/index?order_id=" . $order_id . "&store_id=" . $order['store_id']);
            // 检查收费
            $check = $this->checkCharge();
            if ($check !== true) return $check;
            $this->countIncome();
            return $this->r(200,"操作成功",$this->order);
        }
        return $this->r(100,'操作失败');
    }

    /**
     * 上报指定购物车商品数据变化
     * @return mixed
     */
    protected function reportBuyCarChange()
    {
        $changeData = $this->message['data'];
        if ($changeData) {
            $changeData = json2arr($changeData);
            try {
                validate(VTerminal::class)->scene("reportBuyCarChange")->check($changeData);
            } catch (\Exception $e) {
                return $this->rValidate($e->getMessage());
            }
            $goods = $this->getStoreShelvesFind(['ss_id' => $changeData['ss_id']],'store_id,shelves_number,goods_id,goods_name,wg_id,goods_c_name,goods_c_id,goods_pic,retail_price,stock,bar_code,batch_number,manufacture_time');;
            if ($goods) {
                $goods = $goods->toArray();
                $changeData = array_merge($changeData,$goods);
            }
            $this->sendGatewayGroup("store" . $this->store['store_id'],$this->r(200,'购物车变动',$changeData),'buyCarChange');
        }
        return $this->r(200,'操作成功');
    }

    /**
     * 上报取消购物车
     * @return mixed
     */
    protected function cancelBuyCar()
    {
        $this->sendGatewayGroup('store' . $this->store['store_id'],$this->r(200,'购物车取消支付',['store_id' => $this->store['store_id']]),'cancelBuy');
        return $this->r(200,'操作成功');
    }

}