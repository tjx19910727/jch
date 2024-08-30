<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/6/18
 * Time: 16:56
 */

namespace app\AppFactory\Api\V2;


use app\AppFactory\Kernel\Traits\Activity\ActivityPickCodeTrait;
use app\AppFactory\Kernel\Traits\Api\ApiAdvanceTrait;
use app\AppFactory\Kernel\Traits\Api\ApiCallbackTrait;
use app\AppFactory\Kernel\Traits\Api\ApiLockStockTrait;
use app\AppFactory\Kernel\Traits\Config\ConfigSceneTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthAreaTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthCitiesTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthCountriesTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthRegionsTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthStatesTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineChannelTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineMqRecordTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Traits\Payment\AfterOrderPaymentTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleHotelTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersDailyCountTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersRevenueTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersTrait;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

class V2Client extends V2BaseClient
{
    use MachineTrait, MachineChannelTrait,MachineMqRecordTrait;
    use ConfigSceneTrait;
    use EarthCountriesTrait, EarthCitiesTrait, EarthRegionsTrait, EarthAreaTrait, EarthStatesTrait;
    use SaleOrdersDailyCountTrait, SaleOrdersTrait, SaleHotelTrait, SaleOrdersRevenueTrait;
    use ActivityPickCodeTrait;
    use ApiAdvanceTrait, ApiCallbackTrait, ApiLockStockTrait;
    use AfterOrderPaymentTrait;
    use GoodsTrait;

    protected $machine;
    protected $order;
    protected $returnData;

    /**
     * 01 根据机器ID获取库存信息列表
     * @return array|\think\response\Json
     */
    public function get_inventory_list()
    {
        try {
            $field = "mc_id,channel_code,
            (CASE `status` WHEN 3 THEN 0 ELSE stock END) quantity,retail_price sale_price,sku, 
            (CASE `status` WHEN 3 THEN stock ELSE 0 END) mismatch_quantity,g_id product_id,g_name,
            market_price,frozen_stock reserver_quantity, capacity slot_max_count";
            $where['machine_id'] = $this->params['machine_id'];
            if (isset($this->params['product_id']) && $this->params['product_id']) $where['g_id'] = $this->config['product_id'];
            $where[] = ['status', '<>', 2];
            $data = $this->getMachineChannelList($where,$this->params['pageNum'], $field, 'stock desc');
            $data = $data->each(function ($item) {
                $item['g_desc'] = $this->getGoodsValue(['g_id' => $item['product_id']],'desc');
                return $item;
            });
            if ($data) {
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
            logo logo_url, pic icon_url,status ai_status,last_online_time ai_time,online oo_status";
            $where = [];
            if (isset($this->params['machine_id']) && $this->params['machine_id'])
                $where[] = ["machine_id", 'in', $this->params['machine_id']];
            $whereSdc[] = ['create_date', ">=", strtotime("-7 days")];
            $machineList = $this->getMachineList($where, $this->params['pageNum'], $field)->each(function ($machine) use ($whereSdc) {
                if (isset($machine['country_id']) && $machine['country_id']) $machine['country'] = $this->getEarthCountriesValue(['id' => $machine['country_id']], 'name');
                if (isset($machine['state_id']) && $machine['state_id']) $machine['state'] = $this->getEarthStatesValue(['id' => $machine['state_id']], 'name');
                if (isset($machine['city_id']) && $machine['city_id']) $machine['city'] = $this->getEarthCitiesValue(['id' => $machine['city_id']], 'name');
                if (isset($machine['regions_id']) && $machine['regions_id']) $machine['regions'] = $this->getEarthRegionsValue(['id' => $machine['regions_id']], 'name');
                $machine['inventory'] = $this->getMachineChannelSum(['machine_id' => $machine['machine_id']], 'stock');
                $machine['location_type'] = $machine['scene_id'] ? $this->getConfigSceneValue(['id' => $machine['scene_id']], 'name') : "";
                $machine['district'] = "";
                $machine['oo_status'] = $machine['oo_status'] == 1 ? "online" : "offline";
                $machine['ai_status'] = $machine['ai_status'] == 1 ? "active" : "maintain";
                $machine['ai_time'] = date("Y-m-d H:i:s", $machine['ai_time']);
                $domain = request()->domain();
                if ($machine['logo_url']) $machine['logo_url'] = $domain . $machine['logo_url'];
                if ($machine['icon_url']) $machine['icon_url'] = $domain . $machine['icon_url'];
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
                return $machine;
            });
            if ($machineList) {
                $machineList = $machineList->toArray();
                return $this->returnData(0, $this->lang("msg." . 0), $machineList);
            }
            return $this->returnData(10, $this->lang("msg." . 10));
        } catch (DataNotFoundException $e) {
            actionException($e, 1);
            return $this->returnData(99, $this->lang("msg." . 99));
        } catch (ModelNotFoundException $e) {
            actionException($e, 1);
            return $this->returnData(99, $this->lang("msg." . 99));
        } catch (DbException $e) {
            actionException($e, 1);
            return $this->returnData(99, $this->lang("msg." . 99));
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
            $checkOrder = $this->getSaleOrdersFind(['trade_no' => $this->params['order_no']], 'order_id');
            if ($checkOrder) return $this->returnData(0, $this->lang("msg." . 0), ['pick_code' => $this->params['pick_code'] ?? "", 'success' => true, "order_no" => $this->params['order_no']]);

            $this->machine = $this->getMachineFind(['machine_id' => $this->params['kiosk_id']], 'm_id,machine_id,machine_name,online,ao_id');
            if (!$this->machine) return $this->returnData(15, $this->lang("msg." . 15) . "：" . $this->lang("reserve_order.machine_no_data"));
            if ($this->machine['online'] != 1) return $this->returnData(99, $this->lang("msg." . 99) . "：" . $this->lang("reserve_order.machine_offline"));

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
                // 生成订单详情记录
                $sodResult = $this->createSod();
                if ($sodResult !== 1) {
                    $this->rollbackTrans();
                    return $sodResult;
                }
                // 生成预订商品记录
                $advanceResult = $this->createAdvance();
                if ($advanceResult !== 1) {
                    $this->rollbackTrans();
                    return $advanceResult;
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
        $this->machine = $this->getMachineFind(['machine_id' => $this->params['kiosk_id']], 'm_id,machine_id,machine_name,online,ao_id');
        if (!$this->machine) return $this->returnData(15, $this->lang("msg." . 15) . "：" . $this->lang("reserve_order.machine_no_data"));
        if ($this->machine['online'] != 1) return $this->returnData(99, $this->lang("msg." . 99) . "：" . $this->lang("reserve_order.machine_offline"),
            ['success' => false, "order_no" => $this->params['order_no']]);

        // 查询有生成过订单
        $this->order = $this->getSaleOrdersFind(['trade_no' => $this->params['order_no']], 'order_id');
        if (!$this->order) return $this->returnData(10, $this->lang("msg." . 10), ['success' => true, "order_no" => $this->params['order_no']]);
        if ($this->order['pay_status'] == 5) return $this->returnData(0, $this->lang("msg." . 0));

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
                // 外部支付
                $this->order['pay_type'] = 5;
                if (isset($this->params['mch_no']) && $this->params['mch_no']) $this->order['mch_no'] = $this->params['mch_no'];

                $this->startTrans();
                try {// 结算分润收益
                    $flag[] = $this->settlementRevenue();
                    $flag[] = $this->paymentSuccessful();
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
}