<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/6/18
 * Time: 16:56
 */

namespace app\AppFactory\Api\V2;


use app\AppFactory\Kernel\Traits\Activity\ActivityCouponTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityCouponUsedTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityPickCodeTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityPickTrait;
use app\AppFactory\Kernel\Traits\Api\ApiAdvanceTrait;
use app\AppFactory\Kernel\Traits\Api\ApiCallbackTrait;
use app\AppFactory\Kernel\Traits\Config\ConfigSceneTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthAreaTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthCitiesTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthCountriesTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthRegionsTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthStatesTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsCategoryTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsMultipleTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineChannelTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineMqRecordTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Traits\Payment\AfterOrderPaymentTrait;
use app\AppFactory\Kernel\Traits\Payment\BeforeOrderPaymentTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleHotelTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersDailyCountTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersRevenueTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersTrait;
use app\AppFactory\Kernel\Traits\Card\CardTrait;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

class V2Client extends V2BaseClient
{
    use MachineTrait, MachineChannelTrait,MachineMqRecordTrait;
    use ConfigSceneTrait;
    use EarthCountriesTrait, EarthCitiesTrait, EarthRegionsTrait, EarthAreaTrait, EarthStatesTrait;
    use SaleOrdersDailyCountTrait, SaleOrdersTrait, SaleHotelTrait, SaleOrdersRevenueTrait;
    use ActivityPickTrait,ActivityPickCodeTrait,ActivityCouponTrait,ActivityCouponUsedTrait;
    use ApiAdvanceTrait, ApiCallbackTrait;
    use AfterOrderPaymentTrait;
    use GoodsTrait,GoodsCategoryTrait;
    use GoodsMultipleTrait;
    use BeforeOrderPaymentTrait;
    use CardTrait;

    protected $machine;
    protected $order;
    protected $returnData;
    public $data;


    /**
     *  获取主体库商品信息列表                                                                                           
     *  @return array|\think\response\Json
     */
    public function get_goods_lists(){
        // try {
            $field = "g_id product_id,g_name,quantity,gc_id,gc_anme,desc g_desc,cost_price,sku,sku2,bar_code,banner,pic,details_pic,retail_price,sale_price,market_price,status";
            if (isset($this->params['product_id']) && $this->params['product_id']) $where['g_id'] = $this->params['product_id'];
            $where['status'] = 1;
            $data = $this->getGoodsList($where, $this->params['pageNum'] ?? 1,  $field, 'stock desc');
            dd($data);

            actionLog($this->getLS(),'【SQL】查询主体商品');
            if ($data) {
                return $this->returnData(0, $this->lang("msg." . 0), $data);
            }
            return $this->returnData(10, $this->lang("msg." . 10));
        // } catch (\Exception $e) {
        //     actionException($e, 1);
        //     return $this->returnData(99, $this->lang("msg." . 99));
        // }
    }



    /**
     * 01 根据机器ID获取库存信息列表
     * @return array|\think\response\Json
     */
    public function get_inventory_list()
    {
        try {
            $field = "mc_id,channel_code,
            (CASE `status` WHEN 3 THEN 0 ELSE stock END) quantity,retail_price sale_price,sku, 
            (CASE `status` WHEN 3 THEN stock ELSE 0 END) mismatch_quantity,g_id product_id,g_name,bar_code,cost_price,
            market_price,frozen_stock reserver_quantity, capacity slot_max_count,status";
            $where['machine_id'] = $this->params['machine_id'];
            if (isset($this->params['product_id']) && $this->params['product_id']) $where['g_id'] = $this->config['product_id'];
            $where[] = ['status', '<>', 2];
            $data = $this->getMachineChannelList($where, ['list_rows' => $this->params['pageNum'],'page' => $this->params['page']], $field, 'stock desc');
//            actionLog($this->getLS(),'【SQL】查询货道');
            $data = $data->each(function ($item) {
                $goods = $this->getGoodsFind(['g_id' => $item['product_id'], ['sell_channel', 'in', ['1','3']]],'pic,banner,sku2,sku,bar_code,cost_price,`desc`,retail_price,details_pic,gc_id,gc_name');
                $item['g_retail_price'] = $goods['retail_price'] ?? 0;
                $item['pic'] = $goods['pic'] ?? '';
                $item['details_pic'] = $goods['details_pic'] ?? '';
                $item['banner'] = $goods['banner'] ?? '';
                $item['sku'] = $goods['sku'] ?? '';
                $item['sku2'] = $goods['sku2'] ?? '';
                $item['bar_code'] = $goods['bar_code'] ?? '';
                $item['cost_price'] = $goods['cost_price'] ?? '';
                $item['g_desc'] = $goods['desc'] ?? '';
                $item['gc_id'] = $goods['gc_id'] ?? "";
                $item['gc_name'] = $goods['gc_name'] ?? "";
                return $item;
            });
            if ($data) {
//                actionLog($data,'返回货道');
                return $this->returnData(0, $this->lang("msg." . 0), $data);
            }
            return $this->returnData(10, $this->lang("msg." . 10));
        } catch (\Exception $e) {
            actionException($e, 1);
            return $this->returnData(99, $this->lang("msg." . 99));
        }
    }

    /**
     * 05 获取机器信息
     * @return array|\think\response\Json
     */
    public function get_machines()
    {
        try {
            $field = "machine_id,machine_name,machine_type,machine_serial_number extend1,version software_version,
            country_id,state_id,city_id,regions_id,zip_code zip,street,floor building,mac_address mac,lat,lng,scene_id,
            logo logo_url, pic icon_url,status ai_status,last_online_time ai_time,online oo_status,current_status,device_type,factory,inventory_location";
            $where = [];
            if (isset($this->params['machine_id']) && $this->params['machine_id'])
                $where[] = ["machine_id", 'in', $this->params['machine_id']];
            $whereSdc[] = ['create_date', ">=", strtotime("-7 days")];
            $machineList = $this->getMachineList($where, ['list_rows' => $this->params['pageNum'],'page' => $this->params['page'] ?? 1], $field);
//            actionLog($this->getLS(),'【SQL】查询设备');
            $machineList = $machineList->each(function ($machine) use ($whereSdc) {
                if (isset($machine['country_id']) && $machine['country_id']) $machine['country'] = $this->getEarthCountriesValue(['id' => $machine['country_id']], 'cname');
                if (isset($machine['state_id']) && $machine['state_id']) $machine['state'] = $this->getEarthStatesValue(['id' => $machine['state_id']], 'cname');
                if (isset($machine['city_id']) && $machine['city_id']) $machine['city'] = $this->getEarthCitiesValue(['id' => $machine['city_id']], 'cname');
                if (isset($machine['regions_id']) && $machine['regions_id']) $machine['regions'] = $this->getEarthRegionsValue(['id' => $machine['regions_id']], 'cname');
                $machine['inventory'] = $this->getMachineChannelSum(['machine_id' => $machine['machine_id']], 'stock');
                $machine['location_type'] = $machine['scene_id'] ? $this->getConfigSceneValue(['id' => $machine['scene_id']], 'name') : "";
                $machine['district'] = "";
                $machine['oo_status'] = $machine['oo_status'] == 1 ? "online" : "offline";
                $machine['ai_status'] = $machine['ai_status'] == 1 ? "active" : "maintain";
                $machine['ai_time'] = date("Y-m-d H:i:s", $machine['ai_time']);
                if ($machine['logo_url']) $machine['logo_url'] = checkStrDomain($machine['logo_url']);
                if ($machine['icon_url']) $machine['icon_url'] = checkStrDomain($machine['icon_url']);
                $whereDailyCount = $whereSdc;
                $whereDailyCount['machine_id'] = $machine['machine_id'];
                $sdc = $this->getSaleOrdersDailyCountFind($whereDailyCount,
                    "sum(totalPrice) totalPrice,sum(totalRefundAmount) totalRefundMoney,sum(totalDiscountPrice) totalDiscountPrice,
                        sum(totalQuantity) totalQuantity,sum(totalRefundQuantity) totalRefundQuantity",
                    '',
                    'machine_id');
                $machine['sale_income'] = ($sdc['totalPrice'] ?? 0) - ($sdc['totalRefundMoney'] ?? 0) - ($sdc['totalDiscountPrice'] ?? 0);
                $machine['sale_count'] = ($sdc['totalQuantity'] ?? 0) - ($sdc['totalRefundQuantity'] ?? 0);
                unset($machine['country_id'], $machine['state_id'], $machine['city_id'], $machine['regions_id'], $machine['scene_id']);

                if ($machine['device_type'] == 2) $machine['oo_status'] = "online";
                return $machine;
            });
            if ($machineList) {
                $machineList = $machineList->toArray();
//                actionLog($machineList,'返回设备列表');
                return $this->returnData(0, $this->lang("msg." . 0), $machineList);
            }
            return $this->returnData(10, $this->lang("msg." . 10));
        } catch (DataNotFoundException $e) {
            actionException($e, 1);
            return $this->returnData(99, $this->lang("msg." . 99) . $e->getMessage());
        } catch (ModelNotFoundException $e) {
            actionException($e, 1);
            return $this->returnData(99, $this->lang("msg." . 99 . $e->getMessage()));
        } catch (DbException $e) {
            actionException($e, 1);
            return $this->returnData(99, $this->lang("msg." . 99 . $e->getMessage()));
        }
    }

    /**
     * 14 预订商品
     * @return array|\think\response\Json
     */
    public function reserve_order()
    {
        $this->startTrans();
        try {
            $checkOrder = $this->getSaleOrdersFind(['trade_no' => $this->params['order_no']], 'order_id,pay_code');
            if ($checkOrder) return $this->returnData(0, $this->lang("msg." . 0), ['pick_code' => $checkOrder['pay_code'] ?? ($this->params['pick_code'] ?? ""), 'success' => true, "order_no" => $this->params['order_no']]);

            $this->machine = $this->getMachineFind(['machine_id' => $this->params['kiosk_id']], 'm_id,machine_id,machine_name,device_type,online,ao_id,factory,inventory_location');
            if (!$this->machine) return $this->returnData(15, $this->lang("msg." . 15) . "：" . $this->lang("reserve_order.machine_no_data"));
            if ($this->machine['device_type'] == 1 && $this->machine['online'] != 1)
                return $this->returnData(99, $this->lang("msg." . 99) . "：" . $this->lang("reserve_order.machine_offline"));

            // 不存在则重新生成一个8位纯数字取货码
            if (!isset($this->params['pick_code']) || !$this->params['pick_code']) {
                while (1) {
                    $this->params['pick_code'] = $this->leftHandZero(random_int(00000000, 99999999), 8);
                    $check = $this->getActivityPickCodeCount(['code' => $this->params['pick_code'], ['status', 'in', [1, 5]]]);
                    if (!$check) break;
                }
            }
            $this->createSo();
            actionLog($this->getLS(), '生成订单');
            if ($this->order['order_id']) {
                // 生成取货码记录
                $apcResult = $this->createApc();
                if ($apcResult !== 1) {
                    $this->rollbackTrans();
                    return $apcResult;
                }
                // 生成预订商品记录
                $advanceResult = $this->createAdvance();
                if ($advanceResult !== 1) {
                    $this->rollbackTrans();
                    return $advanceResult;
                }
                // 生成订单详情记录
                $sodResult = $this->createSod();
                if ($sodResult !== 1) {
                    $this->rollbackTrans();
                    return $sodResult;
                }
                $result = $this->updateSaleOrders($this->order);
                actionLog($this->getLS(), '修改订单');
                if ($result) {
                    $this->commitTrans();
                    return $this->returnData(0, $this->lang("msg." . 0), ['pick_code' => $this->params['pick_code'], 'success' => true, "order_no" => $this->params['order_no']]);
                }
                $this->rollbackTrans();
                return $this->returnData(99, $this->lang("msg." . 99));
            }
            $this->rollbackTrans();
            return $this->returnData(99, $this->lang("msg." . 99));
        } catch (\Exception $e) {
            actionException($e, 1);
            $this->rollbackTrans();
            return $this->returnData(99, $this->lang("msg." . 99) . "：" . $e->getMessage());
        }
    }

    /**
     * 15 取消预订商品
     * @return array|\think\response\Json
     */
    public function cancel_order()
    {
        // 查询设备在线
        $this->machine = $this->getMachineFind(['machine_id' => $this->params['kiosk_id']], 'm_id,machine_id,machine_name,device_type,online,ao_id');
        if (!$this->machine) return $this->returnData(15, $this->lang("msg." . 15) . "：" . $this->lang("reserve_order.machine_no_data"));
        if ($this->machine['device_type'] == 1 && $this->machine['online'] != 1) return $this->returnData(99, $this->lang("msg." . 99) . "：" . $this->lang("reserve_order.machine_offline"),
            ['success' => false, "order_no" => $this->params['order_no']]);

        // 查询有生成过订单
        $this->order = $this->getSaleOrdersFind(['trade_no' => $this->params['order_no']], 'order_id');
        if (!$this->order) return $this->returnData(10, $this->lang("msg." . 10), ['success' => true, "order_no" => $this->params['order_no']]);
        if ($this->order['pay_status'] == 5) return $this->returnData(0, $this->lang("msg." . 0));
        if ($this->order['out_status'] > 2 ) return $this->returnData(99,$this->lang("msg.99"));

        // 查询预订商品记录
        $advance = $this->getApiAdvanceFind(['trade_no' => $this->params['order_no']]);
        if (!$advance) return $this->returnData(10, $this->lang("msg." . 10));
        if ($advance['status'] == "CANCELED") return $this->returnData(0, $this->lang("msg." . 0));
        if ($advance['status'] == "PROCESSING") return $this->returnData(20, $this->lang("msg." . 20));

        $this->startTrans();
        try {
            $flag[] = $this->updateActivityPickCode(['status' => 4], ['order_id' => $this->order['order_id']]);
            actionLog($this->getLS(), '修改取货码记录');
            $flag[] = $this->updateSaleOrders(['pay_status' => 5, 'order_id' => $this->order['order_id']]);
            actionLog($this->getLS(), '修改订单信息');
            $flag[] = $this->updateApiAdvance(['status' => "CANCELED"], ['order_id' => $this->order['order_id']]);
            actionLog($this->getLS(), '修改预订商品信息');
            $sod = $this->getSaleOrdersDetailsList(['order_id' => $this->order['order_id']]);
            if ($sod) {
                $sod = $sod->toArray();
                foreach ($sod as $dk => $dv) {
                    $mc = $this->getMachineChannelFind(['mc_id' => $dv['mc_id']], 'mc_id,frozen_stock,stock');
                    // 减冻结库存，增加货架库存
                    $updateMc['mc_id'] = $mc['mc_id'];
                    if ($mc['frozen_stock'] > $dv['quantity']) {
                        $updateMc['frozen_stock'] = bcsub($mc['frozen_stock'], $dv['quantity']);
                    } else {
                        $updateMc['frozen_stock'] = 0;
                    }
                    $updateMc['stock'] = bcadd($mc['stock'], $dv['quantity']);
                    $flag[] = $this->updateMachineChannel($updateMc);
                    actionLog($this->getLS(), '修改货架库存');
                }
            }
            actionLog($flag, '修改结果');
            $result = flag_check($flag);
            if ($result) {
                $this->commitTrans();
                return $this->returnData(0, $this->lang("msg." . 0));
            }
            $this->rollbackTrans();
            return $this->returnData(19, $this->lang("msg." . 19));
        } catch (\Exception $e) {
            $this->rollbackTrans();
            return $this->returnData(99, $this->lang("msg." . 99));
        }
    }

    /**
     * 16 获取预定提货状态
     * @return array|\think\response\Json
     */
    public function get_order_info()
    {
        $field = "status,machine_id,machine_name,trade_no machine_transaction_no,charge_amount,total_amount item_total_amount,quantity,pick_code,payment_method,total_amount,discount_amount,pick_time";
        $advance = $this->getApiAdvanceFind(['trade_no' => $this->params['order_no']], $field);
        if (!$advance) {
            return $this->returnData(10, $this->lang("msg." . 10));
        }
        return $this->returnData(0, $this->lang("msg." . 0), $advance);
    }

    /**
     * 第三方支付回调
     * @return array|\think\response\Json
     */
    public function payNotify()
    {
        try {
            $this->order = $this->getSaleOrdersFind(['trade_no' => $this->params['order_no']]);
            if (!$this->order) return $this->returnData(23, $this->lang("msg." . 23));
            if ($this->order['pay_status'] > 2) return $this->returnData(24, $this->lang("msg." . 24));// 用户是否支付成功
            if ($this->order['pay_type'] != 5) return $this->returnData(25, $this->lang("msg" . 25));
            $this->order = $this->order->toArray();
            if ($this->params['pay_status'] == 1) {

                $this->order['mch_no'] = $this->params['mch_no'] ?? "";
                $this->order['total_price'] = 0;

                $this->startTrans();
                try {// 结算分润收益
                    $flag[] = $this->settlementRevenue();
                    $flag[] = $this->paymentSuccessful();
                    $flag[] = $this->updateSaleOrdersDetails(["total_sod_price" => 0],['order_id' => $this->order['order_id']]);
                    $result = flag_check($flag);
                    actionLog($result, '处理支付成功事务');
                    if (!$result) {
                        $this->rollbackTrans();
                        return $this->returnData(19, $this->lang("msg." . 19));
                    }
                } catch (\Exception $e) {
                    $this->rollbackTrans();
                    actionException($e, 1);
                    return $this->returnData(99, $this->lang("msg." . 99) . "：" . $e->getMessage());
                }
                $this->commitTrans();
            } elseif ($this->params['pay_status'] === 2) {
                $result = $this->paymentFailed();
                if (!$result) {
                    return $this->returnData(19, $this->lang("msg." . 19));
                }
            }
            return $this->returnData(0, $this->lang("msg." . 0));
        } catch (\Exception $e) {
            actionException($e, 1);
            return $this->returnData(99, $this->lang("msg." . 99) . "：" . $e->getMessage());
        }
    }

    /**
     * 酒店预订通知回调
     * @return array|\think\response\Json
     */
    public function hotelNotify()
    {
        try {
            $this->order = $this->getSaleOrdersFind(['out_trade_no' => $this->params['order_no']]);
            if (!$this->order) return $this->returnData(23, $this->lang("msg.23"));
            $sh = $this->getSaleHotelFind(['order_id' => $this->order['order_id']]);
            if (!$sh) return $this->returnData(26, $this->lang("msg.26"));
            $sh = $sh->toArray();
            $updateSh['sh_id'] = $sh['sh_id'];
            $updateSh['reservation_status'] = $this->params['reservation_status'];
            $result = $this->updateSaleHotel($updateSh);
            if ($result) {
                return $this->returnData(0, $this->lang("msg.0"));
            }
            return $this->returnData(19, $this->lang('msg.19'));
        } catch (\Exception $e) {
            actionException($e,1);
            return $this->returnData(99,$this->lang("msg.99"));
        }
    }

    /**
     * 获取组合商品数据
     * @return array|\think\response\Json
     * @throws DbException
     */
    public function get_goods_multiple()
    {
        $where['gmm.machine_id'] = $this->params['kiosk_id'];
        $where['gm.status'] = 1;
        $data = $this->getGoodsMultipleListByMachine($where, ['list_rows' => $this->params['pageNum'] ?? 0,'page' => $this->params['page'] ?? 1],
            "gm.gm_id,gm.gm_name,gm.gm_pic,gm.gm_desc,gm.start_time,gm.end_time",'gm.create_time desc',$this->params['page'] ?? 1);
        return $this->returnData(0,$this->lang("msg.0"),$data);
    }

    /**
     * 获取营销活动码，包含优惠券和提货码
     * @return array|\think\response\Json
     */
    public function get_activity_code()
    {
        try {
            $code = "";// 优惠券
            if (!in_array($this->params['aType'],[2,3])) {
                return $this->returnData(27,$this->lang("msg.27"));
            }
            if ($this->params['aType'] == 2) {
                $where['c_id'] = $this->params['aId'];
                $coupon = $this->getActivityCouponFind($where);
                if (!$coupon) return $this->returnData(27, $this->lang("msg.27"));
                if ($coupon['status'] == 3) return $this->returnData(28, $this->lang("msg.28"));
                if ($coupon['status'] == 4) return $this->returnData(29, $this->lang("msg.29"));
                if ($coupon['start_date'] > time()) return $this->returnData(30, $this->lang("msg.30"));
                if ($coupon['end_date'] < time()) {
                    $this->updateActivityCoupon(['c_id' => $coupon['c_id'], 'status' => 3]);
                    $this->updateActivityCouponUsed(['status' => 3], ['c_id' => $coupon['c_id']]);
                    return $this->returnData(31, $this->lang("msg.31"));
                }
                // 固定码，不是随机码的，有使用次数上限的
                if ($coupon['code']) {
                    if ($coupon['used_limit'] > 0) {
                        $whereCount['c_id'] = $coupon['c_id'];
                        $whereCount['status'] = 2;
                        $usedNum = $this->getActivityCouponUsedCount($whereCount);
                        // 已使用次数等于或超过上限设置的
                        if ($coupon['used_limit'] <= $usedNum) {
                            return $this->returnData(32, $this->lang("msg.32"));
                        }
                    }
                    $insertCu = [
                        "c_id" => $coupon['c_id'],
                        "receive_type" => 2,
                        "code" => $coupon['code'],
                        "code_type" => 2,
                        "c_type" => $coupon['c_type'],
                        "reduction" => $coupon['reduction'],
                        "pay_limit" => $coupon['pay_limit'],
                    ];
                    $code = $coupon['code'];
                    $result = $this->addActivityCouponUsed($insertCu);
                    if (!$result) return $this->returnData(34, $this->lang("msg.34"));
                } else {
                    $whereCu['c_id'] = $coupon['c_id'];
                    $whereCu['status'] = 1;
                    $whereCu['receive_type'] = 1;
                    $cu = $this->getActivityCouponUsedFind($whereCu, 'cu_id,code', 'cu_id desc');
                    if (!$cu) return $this->returnData(33, $this->lang("msg.33"));
                    $code = $cu['code'];
                    $result = $this->updateActivityCouponUsed(['cu_id' => $cu['cu_id'], 'receive_type' => 2]);
                    if (!$result) return $this->returnData(35, $this->lang("msg.35"));
                }
            }// 取货码
            if ($this->params['aType'] == 3) {
                $whereP['id'] = $this->params['aId'];
                $pick = $this->getActivityPickFind($whereP);
                if (!$pick) return $this->returnData(27, $this->lang("msg.27"));
                if ($pick['status'] == 3) return $this->returnData(28, $this->lang("msg.28"));
                if ($pick['status'] == 4) return $this->returnData(29, $this->lang("msg.29"));
                if ($pick['start_time'] > time()) return $this->returnData(30, $this->lang("msg.30"));
                if ($pick['end_time'] < time()) {
                    $this->updateActivityPick(['status' => 3, 'id' => $pick['id']]);
                    $this->updateActivityPickCode(['status' => 3], ['ap_id' => $pick['id']]);
                    return $this->returnData(29, $this->lang("msg.29"));
                }
                if ($pick['status'] == 1 && $pick['start_time'] < time()) {
                    $this->updateActivityPick(['status' => 2, 'id' => $pick['id']]);
                }
                $wherePc['ap_id'] = $pick['id'];
                $wherePc['receive_type'] = 1;
                $wherePc['status'] = 1;
                $pc = $this->getActivityPickCodeFind($wherePc, 'apc_id,code', 'apc_id desc');
                if (!$pc) return $this->returnData(33, $this->lang("msg.33"));
                $result = $this->updateActivityPickCode(['apc_id' => $pc['apc_id'], 'receive_type' => 2]);
                if (!$result) return $this->returnData(35, $this->lang("msg.35"));
                $code = $pc['code'];
            }
            return $this->returnData(0, $this->lang("msg.0"), ['code' => $code]);
        } catch (\Exception $e) {
            actionException($e,1);
            return $this->returnData(99,$this->lang("msg.99") . ":" . $e->getMessage());
        }
    }

    /**
     * 10. 使用预订订单取货码
     * string       pick_code      取货码
     * string       machine_id      设备编号
     * int          out_channel     出货口，1：第1出货口（正常出货），2：第2出货口（机器人取货），待定
     * @return array|\think\response\Json
     * @throws \Exception
     */
    public function use_pick_code()
    {
        // 查询取货码
        $where['code'] = $this->params['pick_code'];
        $where['pick_type'] = 3;
        $pickCode = $this->getActivityPickCodeFind($where);
        actionLog($this->getLS(),'查询取货码SQL');
        actionLog($pickCode,'查询结果');
        if (!$pickCode) return $this->returnData(15,$this->lang("msg.15") . ": " . $this->lang("use_pick_code.null_data"));
        if ($pickCode['status'] == 2) return $this->returnData(19,$this->lang("msg.19") . ": " . $this->lang("use_pick_code.status2"));
        if ($pickCode['status'] == 3) return $this->returnData(19,$this->lang("msg.19") . ": " . $this->lang("use_pick_code.status3"));
        if ($pickCode['status'] == 4) return $this->returnData(19,$this->lang("msg.19") . ": " . $this->lang("use_pick_code.status4"));
        if ($pickCode['status'] == 5) return $this->returnData(19,$this->lang("msg.19") . ": " . $this->lang("use_pick_code.status5"));
        $pickCode = $pickCode->toArray();

        // 查询取货码关联订单
        $this->order = $this->getSaleOrdersFind(['order_id' => $pickCode['order_id']]);
        actionLog($this->getLS(),'查询取货码订单SQL');
        if (!$this->order) return $this->returnData(15,$this->lang("msg.15") . ": " . $this->lang("use_pick_code.order_null_data"));
        $this->order = $this->order->toArray();

        // 查询订单详情
        $details = $this->getSaleOrdersDetailsList(['order_id' => $pickCode['order_id']]);
        actionLog($this->getLS(),'查询取货码订单详情列表SQL');
        if (!$details) return $this->returnData(15,$this->lang("msg.15") . ": " . $this->lang("use_pick_code.order_details_null_data"));
        $details = $details->toArray();
        $this->order['details'] = $details;
        actionLog($this->order,'订单数据');

        // 查询设备数据
        $this->machine = $this->getMachineFind(['machine_id' => $this->params['machine_id']],'m_id,machine_id,machine_name,device_type,ao_id');
        if (!$this->machine) return $this->returnData(40,$this->lang("msg.40") . " : " . $this->params['machine_id']);


        $updateOrder = [];
        $this->startTrans();
        if ($this->order['total_price'] > 0) {
            $flag[] = $this->countIncome();
        }
        $updatePc['status'] = $this->machine['device_type'] == 1 ? 5 : 2;
        $updatePc['m_id'] = $this->machine['m_id'];
        $updatePc['machine_id'] = $this->machine['machine_id'];
        $updatePc['machine_name'] = $this->machine['machine_name'];
        $updatePc['apc_id'] = $pickCode['apc_id'];
        // 设备为售卖机，下发出货数据，不允许另一台设备取货
        if ($this->machine['device_type'] == 1) {
            if ($this->machine['m_id'] != $this->order['m_id']) {
                $this->rollbackTrans();
                return $this->returnData(40,$this->lang("msg.40") . ":" . $this->params['machine_id']);
            }
            $result = $this->outGoods();
            actionLog($result);
            if (is_object($result)) {
                $result = obj2arr($result);
                if (isset($result['state']) && $result['state'] != 200) {
                    $this->rollbackTrans();
                    return $result;
                }
            }
            if ($result === false) {
                $this->rollbackTrans();
                return $this->returnData(19, $this->lang("msg.19"));
            }
            $updateOrder['out_status'] = $this->order['out_status'];
            $updateApiA['status'] = "PROCESSING";
        }
        // 设备应用类型为门店
        else if ($this->machine['device_type'] == 2) {
            $updateOrder['out_status'] = 4;
            $updateOrder['out_time'] = time();
            $updateApiA['status'] = "PICKED";
            $updateApiA['pick_time'] = date("Y-m-d H:i:s");
            $updatePc['used_time'] = time();
            // 核销的设备编号不是原订单设备编号
            if ($this->machine['m_id'] != $this->order['m_id']) {
                // 重置订单设备信息
                $updateOrder['m_id'] = $this->machine['m_id'];
                $updateOrder['machine_id'] = $this->machine['machine_id'];
                $updateOrder['machine_name'] = $this->machine['machine_name'];
                $updateOrder['ao_id'] = $this->machine['ao_id'];
                // 循环重置新设备货道信息
                $whereMc['m_id'] = $updateOrder['m_id'];
                $whereMc['status'] = 1;
                foreach ($details as $key => $value) {
                    // 原货道冻结库存释放到正常库存
                    $flag[] = $this->setMachineChannelInc(['mc_id' => $value['mc_id']], 'stock', $value['quantity']);
                    actionLog($this->getLS(), '【SQL】增加旧货道库存值');
                    $flag[] = $this->setMachineChannelDec(['mc_id' => $value['mc_id']], 'frozen_stock', $value['quantity']);
                    actionLog($this->getLS(), '【SQL】减少旧货道冻结库存');

                    // 查询新货道信息
                    $whereMc['g_id'] = $value['g_id'];
                    $mc = $this->getMachineChannelFind($whereMc, '
                    mc_id,channel_code,stock,shelf_way,channel_position,manufacture_time,sell_by_date,
                    mg_id,g_id,g_name,gc_id,gc_name,pic,sku,bar_code,batch_number,
                    cost_price,market_price');
                    if (!$mc) {
                        actionLog($this->getLS(), '【SQL】查无新货道信息');
                        $this->rollbackTrans();
                        return $this->returnData(38, $this->lang("msg.38") . " : " . $value['g_name']);
                    }
                    $mc = $mc->toArray();
                    if ($mc['stock'] < $value['quantity']) {
                        $this->rollbackTrans();
                        return $this->returnData(39, $this->lang("msg.39") . " : " . $value['g_name']);
                    }
                    // 减新货道库存
                    $flag[] = $this->setMachineChannelDec(['mc_id' => $mc['mc_id']], 'stock', $value['quantity']);
                    actionLog($this->getLS(), '【SQL】减新货道冻结库存');

                    // 修改订单详情数据
                    unset($mc['stock']);
                    $updateSod = $mc;
                    $updateSod['sod_id'] = $value['sod_id'];
                    $updateSod['success_quantity'] = $value['quantity'];
                    $flag[] = $this->updateSaleOrdersDetails($updateSod);
                    actionLog($this->getLS(), '【SQL】修改订单详情出货成功数量1');
                }
            }
            // 核销的设备编号是原订单设备编号
            else {
                // 减货道冻结库存，修改订单详情数据
                foreach ($details as $key => $value) {
                    $flag[] = $this->setMachineChannelDec(['mc_id' => $value['mc_id']],'frozen_stock',$value['quantity']);
                    actionLog($this->getLS(),'【SQL】减货道冻结库存');
                    $updateSod['success_quantity'] = $value['quantity'];
                    $updateSod['sod_id'] = $value['sod_id'];
                    $flag[] = $this->updateSaleOrdersDetails($updateSod);
                    actionLog($this->getLS(),'【SQL】修改订单详情出货成功数量2');
                }
            }
        } else {
            return $this->returnData(19,$this->lang("msg.19") . ":" .  $this->lang("use_pick_code.device_type_unDefine"));
        }
        // 修改取货码使用记录
        $updatePc['receive_type'] = 2;
        $flag[] = $this->updateActivityPickCode($updatePc);
        actionLog($this->getLS(),'修改提货码使用记录');
        // 修改订单数据
        if ($updateOrder) {
            $updateOrder['order_id'] = $this->order['order_id'];
            $flag[] = $this->updateSaleOrders($updateOrder);
            actionLog($this->getLS(),'修改订单记录');
        }
        // 售卖机为取货中，门店为已取货
        $this->machine['device_type'] == 1 ? $updateApiA['status'] = "PROCESSING" : $updateApiA['status'] = "PICKED";

        $flag[] = $this->updateApiAdvance($updateApiA, ['apc_id' => $pickCode['apc_id']]);
        actionLog($this->getLS(),'修改预订商品记录');
        actionLog($flag,'事务处理');
        $check = $this->checkFlag($flag);
        if ($check) {
            $this->commitTrans();
            return $this->returnData(0,$this->lang("msg.0"));
        }
        $this->rollbackTrans();
        return $this->returnData(19,$this->lang("msg.19") . ": " . $this->lang("use_pick_code.trans_fail"));
    }

    /**
     * 11. 获取商品分类
     * @return array|\think\response\Json
     */
    public function get_goods_category()
    {
        $where['status'] = 1;
        $where['ao_id'] = $this->authConfig['ao_id'];
        $list = $this->getGoodsCategoryList($where, ['list_rows' => $this->params['pageNum'] ?? 0,'page' => $this->params['page'] ?? 1],'gc_id,gc_pid,gc_name,`desc` gc_desc, ico, sort','sort asc');
        return $this->returnData(0,$this->lang("msg.0"),$list);
    }

    public function get_card_points(){
        $data = $this->getCardFind(['card_show_no' => $this->params['card_no']], 'card_show_no as card_no, points');
        if(!$data) return $this->returnData(10, $this->lang("card.can_not_find_card"));
        return $this->returnData(0,$this->lang("msg.0"), $data->toArray());
    }
}