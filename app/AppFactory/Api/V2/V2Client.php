<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/6/18
 * Time: 16:56
 */

namespace app\AppFactory\Api\V2;


use app\AppFactory\Api\ApiBaseClient;
use app\AppFactory\Kernel\Traits\Config\ConfigSceneTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthAreaTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthCitiesTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthCountriesTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthRegionsTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthStatesTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineChannelTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersDailyCountTrait;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

class V2Client extends ApiBaseClient
{
    use MachineTrait,MachineChannelTrait;
    use ConfigSceneTrait;
    use EarthCountriesTrait,EarthCitiesTrait,EarthRegionsTrait,EarthAreaTrait,EarthStatesTrait;
    use SaleOrdersDailyCountTrait;

    protected $machine;

    /**
     * 01 根据机器ID获取库存信息列表
     * @return array|\think\response\Json
     */
    public function get_inventory_list()
    {
        try {
            $field = "sum(stock) quantity,retail_price sale_price,sku, 
            SUM(CASE status WHEN 3 THEN stock ELSE 0 END) mismatch_quantity,g_id product_id,market_price,sum(frozen_stock) reserver_quantity, sum(capacity) slot_max_count";
            $where['machine_id'] = $this->config['params']['machine_id'];
            if (isset($this->config['params']['product_id'])) $where['g_id'] = $this->config['product_id'];
            $data = $this->getMachineChannelList($where, 0, $field, '', '', 'g_id');
            if ($data) {
                return $this->returnData(0, $this->msg[0], $data);
            }
            return $this->returnData(10, $this->msg[10]);
        } catch (\Exception $e) {
            actionException($e,1);
            return $this->returnData(99,$this->msg[99]);
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
            if (isset($this->config['params']['machine_id']) && $this->config['params']['machine_id'])
                $where[] = ["machine_id",'in',$this->config['params']['machine_id']];
            $machineList = $this->getMachineList($where,0, $field);
            if ($machineList) {
                $machineList = $machineList->toArray();
                $whereSdc[] = ['create_date',">=",strtotime("-7 days")];
                foreach ($machineList as $k => $machine) {
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
                    $machine['sale_count'] = ($sdc['totalQuantity']??0) - ($sdc['totalRefundQuantity']??0);

                    unset($machine['country_id'], $machine['state_id'], $machine['city_id'], $machine['regions_id'], $machine['scene_id']);
                    $machineList[$k] = $machine;
                }
                return $this->returnData(0, $this->msg[0], $machineList);
            }
            return $this->returnData(10, $this->msg[10]);
        } catch (DataNotFoundException $e) {
            actionException($e,1);
            return $this->returnData(99,$this->msg[99]);
        } catch (ModelNotFoundException $e) {
            actionException($e,1);
            return $this->returnData(99,$this->msg[99]);
        } catch (DbException $e) {
            actionException($e,1);
            return $this->returnData(99,$this->msg[99]);
        }
    }

    // 14 预订商品
    public function reserve_order()
    {
        $machine = $this->getMachineFind(['machine_id' => $this->config['params']['kiosk_id']],'m_id,machine_id,machine_name,online');
        if (!$machine) {
            return $this->returnData(15,$this->msg[15] . "：" . $this->lang("VV2.reserve_order.machine_no_data"));
        }
        if ($machine['online'] != 1)  return $this->returnData(99, $this->msg[99] . "：" . $this->lang("VV2.reserve_order.machine_offline"));
        
    }
}