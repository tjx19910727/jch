<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/6/18
 * Time: 16:56
 */

namespace app\AppFactory\Api\V2;


use app\AppFactory\Api\ApiBaseClient;
use app\AppFactory\Kernel\Traits\Machine\MachineChannelTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;

class V2Client extends ApiBaseClient
{
    use MachineTrait,MachineChannelTrait;

    /**
     * 根据机器ID获取库存信息列表
     * @return array|\think\response\Json
     */
    public function get_inventory_list()
    {
        $field = "sum(stock) quantity,retail_price sale_price,sku, 
        SUM(CASE status WHEN 3 THEN stock ELSE 0 END) mismatch_quantity,g_id product_id,market_price,sum(frozen_stock) reserver_quantity, sum(capacity) slot_max_count";
        $where['machine_id'] = $this->config['params']['machine_id'];
        if (isset($this->config['params']['product_id'])) $where['g_id'] = $this->config['product_id'];
        $data = $this->getMachineChannelList($where,0,$field,'','','g_id');
        if ($data) {
            return $this->returnData(0,$this->msg[0],$data);
        }
        return $this->returnData(10, $this->msg[10]);
    }

    public function getMachines()
    {
        $field = "";
        $where["machine_id"] = $this->config['params']['machine_id'];
    }
}