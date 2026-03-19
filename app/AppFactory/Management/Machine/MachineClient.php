<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:42
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Kernel\Traits\Auth\AuthManagerMachineTrait;
use app\AppFactory\Kernel\Traits\Auth\AuthOrganizationTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthCitiesTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthCountriesTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthRegionsTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthStatesTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineChannelReplenishmentTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineChannelStockTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineChannelTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineCheckStockTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineConfigTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineErrorCodeTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineGoodsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineGroupMgTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineGroupTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineHelpTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineInfoTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineMqRecordTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineOnlineDetailsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineOnlineTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineOnOffTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineVersionPlanTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineVersionTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineViewTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersMachineCountTrait;
use app\AppFactory\Management\ManagementClient;
use app\management\validate\Machine\VMachine;
use app\AppFactory\Kernel\Traits\Machine\MachineLevelDescTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineMainRelationTrait;

class MachineClient extends ManagementClient
{
    use EarthCountriesTrait,EarthStatesTrait,EarthCitiesTrait,EarthRegionsTrait;
    use MachineTrait,MachineChannelTrait,MachineChannelReplenishmentTrait,MachineChannelStockTrait,MachineCheckStockTrait,MachineConfigTrait,MachineErrorCodeTrait,
        MachineGoodsTrait,
        MachineInfoTrait,MachineGroupTrait,MachineGroupMgTrait,MachineHelpTrait,MachineMqRecordTrait,MachineOnOffTrait,
        MachineOnlineTrait,MachineOnlineDetailsTrait,MachineVersionTrait,MachineVersionPlanTrait,MachineViewTrait,MachineLevelDescTrait,MachineMainRelationTrait;
    use SaleOrdersMachineCountTrait;
    use AuthManagerMachineTrait,AuthOrganizationTrait;

    public function addM($postData)
    {
        try {
            $machine_group_id = 0;
            if (isset($postData['machine_group_id']) && $postData['machine_group_id']) {
                $machine_group_id = explode(",", $postData['machine_group_id']);
                unset($postData['machine_group_id']);
            }
            $m = $this->addMachine($postData);
            if ($m) {
                $machine = $this->getMachineFind(['m_id' => $m]);
                $updateMc = [];
                $whereMc = [
                    'm_id' => $machine['m_id'],
                ];
                if (isset($postData['recycle_bin_capacity']) && $postData['recycle_bin_capacity']) {
                    $updateMc['recycle_bin_capacity'] = $postData['recycle_bin_capacity'];
                }
                if ($updateMc) $this->updateMachineConfig($updateMc,$whereMc);
                if ($machine_group_id) {
                    foreach ($machine_group_id as $mk => $mv) {
                        $mg = $this->getMachineGroupFind(['mg_id' => $mv], 'mg_id,mg_name');
                        if (!$mg) {
                            return $this->r(100, $this->lang("VMachineGoods.mg_no_data"));
                        }
                        $mg = $mg->toArray();
                        $mg['m_id'] = $machine['m_id'];
                        $mg['machine_id'] = $machine['machine_id'];
                        $mg['machine_name'] = $machine['machine_name'];
                        $insertAll[] = $mg;
                    }
                    $this->addMachineGroupMgMore($insertAll);
                }
            }
            return $this->rA($m);
        } catch (\Exception $e) {
            actionException($e,1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    public function updateM($postData)
    {
        $machine_group_id = [];
        if (isset($postData['machine_group_id']) && $postData['machine_group_id']) {
            $machine_group_id = explode(",",$postData['machine_group_id']);
            unset($postData['machine_group_id']);
        }
        $this->startTrans();
        try {
            $result = $this->updateMachine($postData);
            if ($result) {
                $m = $this->getMachineFind(['m_id' => $postData['m_id']], "m_id,machine_id,machine_name");
                if (!$m) {
                    return $this->r(100, $this->lang("VMachine.machine_no_data"));
                }
                $m = $m->toArray();
                $this->sendToMachine($m,'updateMachine');
                if ($machine_group_id && is_int($machine_group_id)) $machine_group_id = [$machine_group_id];
                $oldMgId = $this->getMachineGroupMgColumn(['m_id' => $m['m_id']], "mg_id");
                $addList = array_diff($machine_group_id, $oldMgId);
                $delList = array_diff($oldMgId, $machine_group_id);
                if ($delList) $flag[] = $this->delMachineGroupMg(['m_id' => $m['m_id'], ['mg_id', 'in', $delList]]);
                if ($addList) {
                    foreach ($addList as $mk => $mv) {
                        $mg = $this->getMachineGroupFind(['mg_id' => $mv], 'mg_id,mg_name');
                        if (!$mg) {
                            $this->rollbackTrans();
                            return $this->r(100, $this->lang("VMachineGoods.mg_no_data"));
                        }
                        $mg = $mg->toArray();
                        $insertAll[] = array_merge($mg, $m);
                    }
                    $flag[] = $this->addMachineGroupMgMore($insertAll);
                }

                $this->commitTrans();
                return $this->r(200, $this->lang("update_success"));
            }
            $this->rollbackTrans();
            return $this->r(100, $this->lang("update_fail"));
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e,1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    public function updateMore($postData)
    {
        foreach ($postData['mList'] as $key => $value) {
            try {
                validate(VMachine::class)->scene("updateMore")->check($value);
            } catch (\Exception $e) {
                return $this->rValidate($e->getMessage());
            }
            $m = $this->getMachineFind(['m_id' => $value['m_id']],"m_id,machine_id,machine_name");
            if (!$m) {
                return $this->r(100,$this->lang("VMachine.machine_no_data"));
            }
            $this->updateMachine($value);
            $m = $m->toArray();
            $this->sendToMachine($m,'updateMachine');
        }
        return $this->rAction(1);
    }

    public function getMList($where,$pageNum = 0,$field = "",$order = "")
    {
        if ($this->manager['pid'] > 0) {
            $mIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], "m_id");
            $createMIds = $this->getMachineColumn(['creator' => $this->manager['manager_id']],'m_id');
            if ($createMIds && $mIds) $mIds = array_unique(array_merge($mIds,$createMIds));
            $where[] = ['m_id', 'in', $mIds];
        }
        return $this->rQ($this->getMachineList($where,$pageNum,$field,$order,function ($item) {
            if (isset($item['country_id']) && $item['country_id']) $item['country'] = $this->getEarthCountriesFind(['id' => $item['country_id']],'code,name,cname');
            if (isset($item['state_id']) && $item['state_id']) $item['state'] = $this->getEarthStatesFind(['id' => $item['state_id']],'code,name,cname');
            if (isset($item['city_id']) && $item['city_id']) $item['city'] = $this->getEarthCitiesFind(['id' => $item['city_id']],'code,name,cname');
            if (isset($item['regions_id']) && $item['regions_id']) $item['regions'] = $this->getEarthRegionsFind(['id' => $item['regions_id']],'code,name,cname');
            if (isset($item['ao_id']) && $item['ao_id']) $item['ao_id_desc'] = $this->getAuthOrganizationColumn(['ao_id' => $item['ao_id']],'organization_name')[0] ?? '';
            return $item;
        }));
    }

    /**
     * 导出设备列表
     * @param $where
     * @param string $field
     * @param string $order
     * @return array|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function exportM($where,$field = "*",$order = "")
    {
        $list = $this->getMachineList($where,0,$field,$order);
        if ($list) {
            $lang = input("lang");
            $fieldName = "name";
            if (!$lang || $lang == "zh-cn") $fieldName = "cname";
            $countries = $this->getEarthCountriesColumn([], 'cname,name', "id");
            $states = $this->getEarthStatesColumn([], 'cname,name', "id");
            $cities = $this->getEarthCitiesColumn([], 'cname,name', 'id');
            $regions = $this->getEarthRegionsColumn([], 'cname,name', 'id');
            foreach ($list as $key => $item) {
                $address = [];
                if (isset($item['country_id']) && $item['country_id']) $address[] = $countries[$item['country_id']][$fieldName];
                if (isset($item['state_id']) && $item['state_id'])  $address[] = $states[$item['state_id']][$fieldName];
                if (isset($item['city_id']) && $item['city_id'])  $address[] = $cities[$item['city_id']][$fieldName];
                if (isset($item['regions_id']) && $item['regions_id'])  $address[] = $regions[$item['regions_id']][$fieldName];
                $item['address'] = implode("",$address) . $item['street'] . $item['floor'];
                unset($item['country_id'],$item['state_id'],$item['city_id'],$item['regions_id'],$item['street'],$item['floor']);
                $list[$key] = $item;
            }
            $filename = "导出设备信息-" . date("Ymd");
            $title = [
                "machine_id" => "设备编号",
                "machine_name" => "设备名称",
                "address" => "详细地址",
                "device_type" => "应用类型",
                "machine_level" => "设备等级",
                "status" => "设备状态",
                "online" => "设备在离线",
                "last_online_time" => "最后上线时间",
                "version" => "软件版本",
                'factory' => "所属工厂",
                'inventory_location' => "库存地点"
            ];
            return $this->sendToExport("设备管理-设备列表", $filename, $title, $list);
        }
        return $this->rFail($this->lang("query_fail"));
    }

    /**
     * 删除设备信息
     * @param $m_id
     * @return array|\think\response\Json
     */
    public function delM($m_id)
    {
        $where[] = ['m_id',"in",$m_id];
        $this->delMachine($where);
        $this->delMachineChannel($where);
        $this->delMachineChannelReplenishment($where);
        $this->delMachineChannelStock($where);
        $this->delMachineCheckStock($where);
        $this->delMachineConfig($where);
        $this->delMachineErrorCode($where);
        $this->delMachineGoods($where);
        $this->delMachineGroupMg($where);
        $this->delMachineHelp($where);
        $this->delMachineInfo($where);
        $this->delMachineMqRecord($where);
        $this->delMachineOnOff($where);
        $this->delMachineOnline($where);
        $this->delMachineOnlineDetails($where);
        $this->delMachineVersionPlan($where);
        $this->delMachineView($where);
        return $this->r(200,$this->lang("action_success"));
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
            if (isset($item['machine_level']) && $item['machine_level']) $item['machine_level_info'] = $this->getMachineLevelFind(['machine_level' => $item['machine_level']],'name,pic');
        }
        return $this->rQ($item);
    }

    /**
     * 获取设备总数、正常数量、禁用数量、维护数量、在线数量、离线数量
     * @param $where
     * @return array
     */
    public function getData($where = [])
    {
        if ($this->manager['pid'] > 0) {
            $mIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], "m_id");
            if ($mIds) {
                $where[] = ['m_id', 'in', $mIds];
            }
        }
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
    public function get10List($where = [])
    {
        if ($this->manager['pid'] > 0) {
            $mIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], "m_id");
            if ($mIds) {
                $where[] = ['m_id', 'in', $mIds];
            }
        }
        $list = $this->getSaleOrdersMachineCountList($where, 0,
            'm_id,machine_id,machine_name,totalPrice,totalQuantity,totalDiscountPrice,order_num,coupon_used',
            'totalPrice desc,totalQuantity desc, m_id desc', '', 'm_id', 10);
        if ($list) {
            $list = $list->toArray();
            foreach ($list as $key => $item) {
                $m = $this->getMachineFind(['m_id' => $item['m_id']], "country_id,state_id,city_id,regions_id");
                if ($m) {
                    if ($m['country_id']) $item['country'] = $this->getEarthCountriesFind(['id' => $m['country_id']], 'code,name,cname');
                    if ($m['state_id']) $item['state'] = $this->getEarthStatesFind(['id' => $m['state_id']], 'code,name,cname');
                    if ($m['city_id']) $item['city'] = $this->getEarthCitiesFind(['id' => $m['city_id']], 'code,name,cname');
                    if ($m['regions_id']) $item['regions'] = $this->getEarthRegionsFind(['id' => $m['regions_id']], 'code,name,cname');
                }
                $list[$key] = $item;
            }
        }
        return $this->rQ($list);
    }

    /**
     * 导出设备排行榜
     * @param $where
     * @return array|\think\response\Json
     */
    public function exportRankingList($where = [])
    {
        $mIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']],"m_id");
        if ($mIds) {
            $where[] = ['m_id', 'in', $mIds];
        }
        $list = $this->getSaleOrdersMachineCountList($where, 0,
            'm_id,machine_id,machine_name,totalPrice,totalQuantity,totalDiscountPrice,order_num,coupon_used',
            'totalPrice desc,totalQuantity desc, m_id desc', '', 'm_id');
        if ($list) {
            $list = $list->toArray();
            foreach ($list as $key => $item) {
                $item['address'] = "";
                $m = $this->getMachineFind(['m_id' => $item['m_id']], "country_id,state_id,city_id,regions_id");
                if ($m['country_id']) $item['address'] = $this->getEarthCountriesValue(['id' => $m['country_id']], 'name');
                if ($m['state_id']) $item['address'] .= "-" . $this->getEarthStatesValue(['id' => $m['state_id']], 'name');
                if ($m['city_id']) $item['address'] .= "-" . $this->getEarthCitiesValue(['id' => $m['city_id']], 'name');
                if ($m['regions_id']) $item['address'] .= "-" . $this->getEarthRegionsValue(['id' => $m['regions_id']], 'name');
                $list[$key] = $item;
            }
            $title = [
                "machine_id" => "机器ID",
                "machine_name" => "机器名称",
                "address" => "机器位置",
                "totalPrice" => "销售额",
                "coupon_used" => "优惠券",
            ];
            $filename = "设备排行榜（最近7天）-" . date("YmdHis");
            $result = $this->sendToExport("首页-设备排行榜（最近7天）", $filename, $title, $list);
            return $result;
        }
        return $this->r(100,$this->lang("query_fail"));
    }

    public function getLevelList($where,$pageNum = 0,$field = "",$order = "")
    {

        return $this->rQ($this->getMachineLevelList($where,$pageNum,$field,$order));
    }

    public function getLevelFind($where,$field = "")
    {
        $item = $this->getMachineLevelFind($where,$field);
        if ($item) {
            $item = $item->toArray();
        }
        return $this->rQ($item);
    }

    public function addMLevel($postData)
    {
        try {
            $m = $this->addMachineLevel($postData);
            return $this->rA($m);
        } catch (\Exception $e) {
            actionException($e,1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    public function updateMLevel($postData)
    {
        try {
            $result = $this->updateMachineLevel($postData);
            if ($result) {
                return $this->r(200, $this->lang("update_success"));
            }
            return $this->r(100, $this->lang("update_fail"));
        } catch (\Exception $e) {
            actionException($e,1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    public function getSubMList($where,$pageNum = 0,$field = "",$order = "",$vending_machine_type = "")
    {
        if ($this->manager['pid'] > 0) {
            $mIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], "m_id");
            $createMIds = $this->getMachineColumn(['creator' => $this->manager['manager_id']],'m_id');
            if ($createMIds && $mIds) $mIds = array_unique(array_merge($mIds,$createMIds));
            $where[] = ['m_id', 'in', $mIds];
        }
        if(empty($vending_machine_type)){
            $expr = "(mc.channel_position = 3 OR (mc.channel_position = 2 AND EXISTS(SELECT 1 FROM machine_info mi WHERE mi.m_id = a.m_id AND mi.sub_cabinet = 1)))";
        }else{
            if($vending_machine_type == 2){
                //弧柜
                $expr = "(mc.channel_position = 2 AND EXISTS(SELECT 1 FROM machine_info mi WHERE mi.m_id = a.m_id AND mi.sub_cabinet = 1))";
            }else{
                //边柜
                $expr = "mc.channel_position = 3";
            }
        }
        
        if (!empty($where['raw'])) {
            $where['raw'] .= " AND " . $expr;
        } else {
            $where['raw'] = $expr;
        }
        return $this->rQ($this->getMachineJoinChannelList($where,$pageNum,$field,$order,function ($item) {
            if (isset($item['country_id']) && $item['country_id']) $item['country'] = $this->getEarthCountriesFind(['id' => $item['country_id']],'code,name,cname');
            if (isset($item['state_id']) && $item['state_id']) $item['state'] = $this->getEarthStatesFind(['id' => $item['state_id']],'code,name,cname');
            if (isset($item['city_id']) && $item['city_id']) $item['city'] = $this->getEarthCitiesFind(['id' => $item['city_id']],'code,name,cname');
            if (isset($item['regions_id']) && $item['regions_id']) $item['regions'] = $this->getEarthRegionsFind(['id' => $item['regions_id']],'code,name,cname');
            if (isset($item['ao_id']) && $item['ao_id']) $item['ao_id_desc'] = $this->getAuthOrganizationColumn(['ao_id' => $item['ao_id']],'organization_name')[0] ?? '';
            $item['main_m_id'] = $item['m_id'];
            if($item['channel_position'] == 2){
                $item['machine_name'] = $item['machine_name'].'的弧柜';
            }elseif($item['channel_position'] == 3){
                $item['machine_name'] = $item['channel_name'] ?: ($item['machine_name'].'的弧柜');
            }else{
                $item['machine_name'] = $item['machine_name'].'的副柜';
            }
            return $item;
        },'a.m_id','',[],['join'=>'machine_channel mc','on'=>'mc.m_id = a.m_id','type'=>'INNER']));
    }

    //边柜添加
    public function addSubM($postData)
    {
        try {
            $main_m_id = 0;
            if(!empty($postData['main_m_id'])){
                $main_m_id = $postData['main_m_id'];
                unset($postData['main_m_id']);
            }else{
                return $this->r(100, $this->lang("VSubMachine.main_machine_id_require"));
            }
            //查询此主柜是否已经关联过边柜，如果已经关联过边柜则不允许再添加边柜了
            $channelCount = $this->getMachineChannelCount([['m_id','=',$main_m_id],['channel_position','=',3]]);
            if($channelCount > 0){
                return $this->r(100, $this->lang("VSubMachine.main_machine_only_one_sub"));
            }
            $postData['vending_machine_type'] = 3;//边柜
            $mainM = $this->getMachineFind(['m_id' => $main_m_id]);
            if(!$mainM){
                return $this->r(100, $this->lang("VMachine.machine_no_data"));
            }
            $mainM = $mainM->toArray();
            $postData['ao_id'] = $mainM['ao_id'];
                //创建默认货道
            for ($i = 1; $i <= 3 ; $i++) {
                $channelData = [
                    'm_id' => $main_m_id,
                    'machine_id' => $mainM['machine_id'],
                    'ao_id' => $postData['ao_id'] ?? 0,
                    'channel_code' => '020'.$i,
                    'channel_position' => 3,
                    'channel_name' => $postData['machine_name'] ?? '',
                    'width2' => 300,
                ];
                $channelAll[] = $channelData;
            }
            $this->addMachineMoreChannel($channelAll);
            return $this->rA($mainM);
        } catch (\Exception $e) {
            actionException($e,1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    public function updateSubM($postData)
    {
        unset($postData['machine_id']);
        $main_m_id = 0;
        if(!empty($postData['main_m_id'])){
            $main_m_id = $postData['main_m_id'];
            unset($postData['main_m_id']);
        }else{
            return $this->r(100, $this->lang("VSubMachine.main_machine_id_require"));
        }
        $m = $this->getMachineFind(['m_id' => $postData['m_id']], "m_id,machine_id,machine_name,vending_machine_type");
        if(!$m) {
            return $this->r(100, $this->lang("VMachine.machine_no_data"));
        }
        $m = $m->toArray();
        $updateData = [
            'channel_name' => $postData['machine_name'] ?? '',
        ];
        if($m['m_id'] != $main_m_id){
            //查询此主柜是否已经关联过边柜，如果已经关联过边柜则不允许再添加边柜了
            $channelCount = $this->getMachineChannelCount([['m_id','=',$main_m_id],['channel_position','=',3]]);
            if($channelCount > 0){
                return $this->r(100, $this->lang("VSubMachine.main_machine_only_one_sub"));
            }
            $mainM = $this->getMachineFind(['m_id' => $main_m_id]);
            if(!$mainM){
                return $this->r(100, $this->lang("VMachine.machine_no_data"));
            }
            $mainM = $mainM->toArray();
            $postData['ao_id'] = $mainM['ao_id'];
            $updateData['m_id'] = $main_m_id;
            $updateData['ao_id'] = $mainM['ao_id'];
            $this->updateMachineChannel($updateData, [['m_id', '=', $m['m_id']]]);
        }
        $this->updateMachineChannel($updateData, [['m_id', '=', $m['m_id']]]);
        return $this->r(200, $this->lang("update_success"));
    }

    /**
     * 删除设备信息
     * @param $m_id
     * @return array|\think\response\Json
     */
    public function delSubM($m_id)
    {
        //只能删除边柜信息
        $channelCount = $this->getMachineChannelCount([['m_id','=',$m_id],['channel_position','=',3]]);
        if($channelCount == 0){
            return $this->r(100, $this->lang("VSubMachine.machine_no_delete"));
        }
        $m = $this->getMachineFind(['m_id' => $m_id], "m_id,vending_machine_type");
        if (!$m) {
            return $this->r(200,$this->lang("action_success"));
        }
        $m = $m->toArray();
        $where[] = ['m_id',"=",$m_id];
        $where[] = ['channel_position',"=",3];
        $this->delMachineChannel($where);
        return $this->r(200,$this->lang("action_success"));
    }
}