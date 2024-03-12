<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:42
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Kernel\Traits\Earth\EarthCitiesTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthCountriesTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthRegionsTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthStatesTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersMachineCountTrait;
use app\AppFactory\Management\ManagementClient;

class MachineClient extends ManagementClient
{
    use EarthCountriesTrait,EarthStatesTrait,EarthCitiesTrait,EarthRegionsTrait;
    use MachineTrait;
    use SaleOrdersMachineCountTrait;

    public function getMList($where,$pageNum = 0,$field = "",$order = "")
    {
        return $this->rQ($this->getMachineList($where,$pageNum,$field,$order,function ($item) {
            if (isset($item['country_id']) && $item['country_id']) $item['country'] = $this->getEarthCountriesFind(['id' => $item['country_id']],'code,name,cname');
            if (isset($item['state_id']) && $item['state_id']) $item['state'] = $this->getEarthStatesFind(['id' => $item['state_id']],'code,name,cname');
            if (isset($item['city_id']) && $item['city_id']) $item['city'] = $this->getEarthCitiesFind(['id' => $item['city_id']],'code,name,cname');
            if (isset($item['regions_id']) && $item['regions_id']) $item['regions'] = $this->getEarthRegionsFind(['id' => $item['regions_id']],'code,name,cname');
            return $item;
        }));
    }

    public function getMFind($where,$field = "")
    {
        $item = $this->getMachineFind($where,$field);
        if ($item) {
            $item = $item->toArray();
            if (isset($item['country_id']) && $item['country_id']) $item['country'] = $this->getEarthCountriesFind(['id' => $item['country_id']],'code,name,cname');
            if (isset($item['state_id']) && $item['state_id']) $item['state'] = $this->getEarthStatesFind(['id' => $item['state_id']],'code,name,cname');
            if (isset($item['city_id']) && $item['city_id']) $item['city'] = $this->getEarthCitiesFind(['id' => $item['city_id']],'code,name,cname');
            if (isset($item['regions_id']) && $item['regions_id']) $item['regions'] = $this->getEarthRegionsFind(['id' => $item['regions_id']],'code,name,cname');
        }
        return $this->rQ($item);
    }

    /**
     * 获取设备总数、正常数量、禁用数量、维护数量、在线数量、离线数量
     * @param $where
     * @return array
     */
    public function getData($where)
    {
        $total = $this->getMachineCount($where);
        $where['status'] = 2;
        $disable = $this->getMachineCount($where);
        $where['status'] = 3;
        $maintain = $this->getMachineCount($where);
        $where['status'] = 1;
        $normal = $this->getMachineCount($where);
        $where['online'] = 1;
        $online = $this->getMachineCount($where);
        $where['online'] = 2;
        $offline = $this->getMachineCount($where);
        $data = [
            "total" => $total,
            "normal" => $normal,
            "disable" => $disable,
            "maintain" => $maintain,
            "online" => $online,
            "offline" => $offline,
        ];
        return $data;
    }

    /**
     * 概览——前10排行
     * @param $where
     * @return array|string
     */
    public function get10List($where)
    {
        $list = $this->getSaleOrdersMachineCountList($where,0,
            'm_id,machine_id,machine_name,totalPrice,totalQuantity,totalDiscountPrice,order_num,coupon_used',
            'totalPrice desc,totalQuantity desc, m_id desc','','m_id',2);
        if ($list) {
            $list = $list->toArray();
            foreach ($list as $key => $item) {
                $m = $this->getMachineFind(['m_id' => $item['m_id']],"country_id,state_id,city_id,regions_id");
                if ($m['country_id']) $item['country'] = $this->getEarthCountriesFind(['id' => $m['country_id']],'code,name,cname');
                if ($m['state_id']) $item['state'] = $this->getEarthStatesFind(['id' => $m['state_id']],'code,name,cname');
                if ($m['city_id']) $item['city'] = $this->getEarthCitiesFind(['id' => $m['city_id']],'code,name,cname');
                if ($m['regions_id']) $item['regions'] = $this->getEarthRegionsFind(['id' => $m['regions_id']],'code,name,cname');
                $list[$key] = $item;
            }
        }
        return $this->rQ($list);
    }
}