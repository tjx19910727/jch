<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:42
 */

namespace app\AppFactory\Management\Machine;

use app\AppFactory\Kernel\Model\Machine\MachineMainRelationModel;
use app\AppFactory\Kernel\Traits\Auth\AuthManagerMachineTrait;
use app\AppFactory\Kernel\Traits\Auth\AuthOrganizationTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthCitiesTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthCountriesTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthRegionsTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthStatesTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineAuxiliaryTrait;
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
use app\AppFactory\Kernel\Support\Excel;
use think\facade\Db;

class MachineClient extends ManagementClient
{
    use EarthCountriesTrait,EarthStatesTrait,EarthCitiesTrait,EarthRegionsTrait;
    use MachineTrait,MachineChannelTrait,MachineChannelReplenishmentTrait,MachineChannelStockTrait,MachineCheckStockTrait,MachineConfigTrait,MachineErrorCodeTrait,
        MachineGoodsTrait,
        MachineInfoTrait,MachineGroupTrait,MachineGroupMgTrait,MachineHelpTrait,MachineMqRecordTrait,MachineOnOffTrait,
        MachineOnlineTrait,MachineOnlineDetailsTrait,MachineVersionTrait,MachineVersionPlanTrait,MachineViewTrait,MachineLevelDescTrait;
    use SaleOrdersMachineCountTrait;
    use AuthManagerMachineTrait,AuthOrganizationTrait;
    use MachineAuxiliaryTrait;
    use MachineInfoTrait;

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

    // public function getSubMList($where,$pageNum = 0,$field = "",$order = "",$vending_machine_type = "")
    // {
    //     if ($this->manager['pid'] > 0) {
    //         $mIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], "m_id");
    //         $createMIds = $this->getMachineColumn(['creator' => $this->manager['manager_id']],'m_id');
    //         if ($createMIds && $mIds) $mIds = array_unique(array_merge($mIds,$createMIds));
    //         $where[] = ['m_id', 'in', $mIds];
    //     }
    //     if(empty($vending_machine_type)){
    //         $expr = "(mc.channel_position = 3 OR (mc.channel_position = 2 AND EXISTS(SELECT 1 FROM machine_info mi WHERE mi.m_id = a.m_id AND mi.sub_cabinet = 1)))";
    //     }else{
    //         if($vending_machine_type == 2){
    //             //弧柜
    //             $expr = "(mc.channel_position = 2 AND EXISTS(SELECT 1 FROM machine_info mi WHERE mi.m_id = a.m_id AND mi.sub_cabinet = 1))";
    //         }else{
    //             //边柜
    //             $expr = "mc.channel_position = 3";
    //         }
    //     }
        
    //     if (!empty($where['raw'])) {
    //         $where['raw'] .= " AND " . $expr;
    //     } else {
    //         $where['raw'] = $expr;
    //     }
    //     return $this->rQ($this->getMachineJoinChannelList($where,$pageNum,$field,$order,function ($item) {
    //         if (isset($item['country_id']) && $item['country_id']) $item['country'] = $this->getEarthCountriesFind(['id' => $item['country_id']],'code,name,cname');
    //         if (isset($item['state_id']) && $item['state_id']) $item['state'] = $this->getEarthStatesFind(['id' => $item['state_id']],'code,name,cname');
    //         if (isset($item['city_id']) && $item['city_id']) $item['city'] = $this->getEarthCitiesFind(['id' => $item['city_id']],'code,name,cname');
    //         if (isset($item['regions_id']) && $item['regions_id']) $item['regions'] = $this->getEarthRegionsFind(['id' => $item['regions_id']],'code,name,cname');
    //         if (isset($item['ao_id']) && $item['ao_id']) $item['ao_id_desc'] = $this->getAuthOrganizationColumn(['ao_id' => $item['ao_id']],'organization_name')[0] ?? '';
    //         $item['main_m_id'] = $item['m_id'];
    //         if($item['channel_position'] == 2){
    //             $item['machine_name'] = $item['machine_name'].'的弧柜';
    //             $item['vending_machine_type'] = 2;
    //         }elseif($item['channel_position'] == 3){
    //             $item['machine_name'] = $item['channel_name'] ?: ($item['machine_name'].'的边柜');
    //             $item['vending_machine_type'] = 3;
    //         }else{
    //             $item['machine_name'] = $item['machine_name'].'的副柜';
    //         }
    //         return $item;
    //     },'a.m_id','',[],['join'=>'machine_channel mc','on'=>'mc.m_id = a.m_id','type'=>'INNER']));
    // }
    public function getSubMList($where,$pageNum = 0,$field = "*",$order = "")
    {
        $this->syncSubMachineAuxiliary();

        if ($this->manager['pid'] > 0) {
            $where[] = ['manager_id', '=', $this->manager['manager_id']];
        }

        return $this->rQ($this->getMachineAuxiliaryList($where,$pageNum,$field,$order,function ($item) {

            $item['main_machine_id'] = '';
            $item['main_machine_name'] = '';
            if ($item['main_m_id'] > 0) {
                $mainM = $this->getMachineFind(['m_id' => $item['main_m_id']], 'machine_id,machine_name');
                $item['main_machine_id'] = $mainM['machine_id'] ?? '';
                $item['main_machine_name'] = $mainM['machine_name'] ?? '';
            }
            if (isset($item['ao_id']) && $item['ao_id']) $item['ao_id_desc'] = $this->getAuthOrganizationColumn(['ao_id' => $item['ao_id']],'organization_name')[0] ?? '';
            return $item;
        }));
    }

    /**
     * 在副柜列表查询链路中补齐自动上报但未建档的副柜关系
     */
    private function syncSubMachineAuxiliary()
    {
        try {
            $infoWhere[] = ['mi.sub_cabinet', '=', 1];
            if ($this->manager['pid'] > 0) {
                $authMIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], 'm_id');
                if (!$authMIds) {
                    return;
                }
                $infoWhere[] = ['mi.m_id', 'in', $authMIds];
            }

            $missingList = Db::name('machine_info')->alias('mi')
                ->join('machine m', 'm.m_id = mi.m_id')
                ->join('machine_channel mc', 'mc.m_id = mi.m_id AND mc.channel_position = 2')
                ->join('machine_auxiliary ma_arc', 'ma_arc.main_m_id = mi.m_id AND ma_arc.machine_type = 1', 'LEFT')
                ->where($infoWhere)
                ->whereNull('ma_arc.m_id')
                ->field([
                    'mi.m_id' => 'main_m_id',
                    'm.machine_name',
                    'm.machine_id',
                    'm.street',
                    'm.ao_id',
                    'm.manager_id',
                ])
                ->group('mi.m_id')
                ->select()
                ->toArray();

            if (!$missingList) {
                return;
            }

            foreach ($missingList as $row) {
                $mainMId = intval($row['main_m_id'] ?? 0);
                if ($mainMId <= 0) {
                    continue;
                }

                $insertAux = [
                    'main_m_id' => $mainMId,
                    'machine_type' => 1,//弧柜，兼容之前没有区分的情况
                    'machine_name' => $row['machine_name'] ?? '',
                    'machine_id' => $row['machine_id'] ? $row['machine_id'] .'-'.mt_rand(1000, 9999): mt_rand(1000000, 9999999),
                    'street' => $row['street'] ?: '',
                    'ao_id' => $row['ao_id'] ?: $this->manager['ao_id'],
                    'manager_id' => $row['manager_id'] ?: $this->manager['manager_id'],
                    'status' => 1,
                ];
                $this->addMachineAuxiliary($insertAux);
            }
        } catch (\Exception $e) {
            actionException($e, 1);
        }
    }

    public function getSubMFind($where,$field = "")
    {
        $item = $this->getMachineAuxiliaryFind($where,$field);
        if ($item) {
            $item = $item->toArray();
            $item['main_machine_id'] = '';
            $item['main_machine_name'] = '';
            $item['machine_channel'] = [];
            $item['bind_time'] = $item['bind_time'] ? date("Y-m-d H:i:s", $item['bind_time']) : '';
            if ($item['main_m_id'] > 0) {
                $mainM = $this->getMachineFind(['m_id' => $item['main_m_id']], 'machine_id,machine_name,street');
                $item['main_machine_id'] = $mainM['machine_id'] ?? '';
                $item['main_machine_name'] = $mainM['machine_name'] ?? '';
                $item['machine_channel'] = $this->getMachineChannelList(['m_id' => $item['main_m_id'],'channel_position' => $item['machine_type'] == 1 ? 2 :3]);
                $item['main_machine_address'] = $mainM['street'] ?? '';
            }
            if (isset($item['ao_id']) && $item['ao_id']) $item['ao_id_desc'] = $this->getAuthOrganizationColumn(['ao_id' => $item['ao_id']],'organization_name')[0] ?? '';
        }
        return $this->rQ($item);
    }



    //边柜添加
    // public function addSubM($postData)
    // {
    //     try {
    //         $main_m_id = 0;
    //         if(!empty($postData['main_m_id'])){
    //             $main_m_id = $postData['main_m_id'];
    //             unset($postData['main_m_id']);
    //         }else{
    //             return $this->r(100, $this->lang("VSubMachine.main_machine_id_require"));
    //         }
    //         //查询此主柜是否已经关联过边柜，如果已经关联过边柜则不允许再添加边柜了
    //         $channelCount = $this->getMachineChannelCount([['m_id','=',$main_m_id],['channel_position','=',3]]);
    //         if($channelCount > 0){
    //             return $this->r(100, $this->lang("VSubMachine.main_machine_only_one_sub"));
    //         }
    //         $postData['vending_machine_type'] = 3;//边柜
    //         $mainM = $this->getMachineFind(['m_id' => $main_m_id]);
    //         if(!$mainM){
    //             return $this->r(100, $this->lang("VMachine.machine_no_data"));
    //         }
    //         $mainM = $mainM->toArray();
    //         $postData['ao_id'] = $mainM['ao_id'];
    //             //创建默认货道
    //         for ($i = 1; $i <= 3 ; $i++) {
    //             $channelData = [
    //                 'm_id' => $main_m_id,
    //                 'machine_id' => $mainM['machine_id'],
    //                 'ao_id' => $postData['ao_id'] ?? 0,
    //                 'channel_code' => '020'.$i,
    //                 'channel_position' => 3,
    //                 'channel_name' => $postData['machine_name'] ?? '',
    //                 'width2' => 300,
    //             ];
    //             $channelAll[] = $channelData;
    //         }
    //         $this->addMachineMoreChannel($channelAll);
    //         return $this->rA($mainM);
    //     } catch (\Exception $e) {
    //         actionException($e,1);
    //         return $this->rTryCatch($e->getMessage());
    //     }
    // }

        //边柜添加
    public function addSubM($postData)
    {
        $main_m_id = 0;
        if(isset($postData['main_m_id'])){
            $main_m_id = $postData['main_m_id'] ;
        }
        $machine_type = $postData['machine_type'] ?? 2; // 默认边柜
        if (!in_array(intval($machine_type), [1, 2])) $machine_type = 2;
        $postData['status'] = 2;//未挂接未启用
        $postData['bind_time'] = 0;
        $is_add = true;
        $postData['ao_id'] = $this->manager['ao_id'] ?? 0;
        if($main_m_id){
            $mainM = $this->getMachineFind(['m_id' => $main_m_id]);
            if(!$mainM){
                return $this->r(100, $this->lang("VMachine.machine_no_data"));
            }
            $mainM = $mainM->toArray();
            //查询此主柜是否已经关联过同类型的副柜
            $existsWhere = [
                ['main_m_id', '=', $main_m_id],
                ['machine_type', '=', $machine_type],
            ];
            $AuxiliaryCount = $this->getMachineAuxiliaryCount($existsWhere);
            if ($AuxiliaryCount > 0) {
                $msg = $machine_type == 1 ? $this->lang("VSubMachine.main_machine_only_one_arc") : $this->lang("VSubMachine.main_machine_only_one_sub");
                return $this->r(100, $msg);
            }
            $channelPosition = $this->getSubMachineChannelPosition($machine_type);
            $hasAutoReported = $this->getMachineChannelCount([
                ['m_id', '=', $main_m_id],
                ['channel_position', '=', $channelPosition],
            ]);
            if ($hasAutoReported > 0) {
                $is_add = false;
            }
            $postData['ao_id'] = $mainM['ao_id'];
            $postData['status'] = 3;//已挂接未启用
            $postData['bind_time'] = time();
        }

        $postData['manager_id'] = $this->manager['manager_id'] ?? 0;
        $this->startTrans();
        try {
            $m = $this->addMachineAuxiliary($postData);
            if ($m && $main_m_id && $is_add) {
                // 创建默认货道
                $this->addDefaultSubMachineChannels(
                    $main_m_id,
                    $mainM,
                    $machine_type,
                    $postData['machine_name'] ?? ''
                );
            }
            $this->commitTrans();
            return $this->rA($m);
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e,1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    // public function updateSubM($postData)
    // {
    //     unset($postData['machine_id']);
    //     $main_m_id = 0;
    //     if(!empty($postData['main_m_id'])){
    //         $main_m_id = $postData['main_m_id'];
    //         unset($postData['main_m_id']);
    //     }else{
    //         return $this->r(100, $this->lang("VSubMachine.main_machine_id_require"));
    //     }
    //     $m = $this->getMachineFind(['m_id' => $postData['m_id']], "m_id,machine_id,machine_name,vending_machine_type");
    //     if(!$m) {
    //         return $this->r(100, $this->lang("VMachine.machine_no_data"));
    //     }
    //     $m = $m->toArray();
    //     $updateData = [
    //         'channel_name' => $postData['machine_name'] ?? '',
    //     ];
    //     if($m['m_id'] != $main_m_id){
    //         //查询此主柜是否已经关联过边柜，如果已经关联过边柜则不允许再添加边柜了
    //         $channelCount = $this->getMachineChannelCount([['m_id','=',$main_m_id],['channel_position','=',3]]);
    //         if($channelCount > 0){
    //             return $this->r(100, $this->lang("VSubMachine.main_machine_only_one_sub"));
    //         }
    //         $mainM = $this->getMachineFind(['m_id' => $main_m_id]);
    //         if(!$mainM){
    //             return $this->r(100, $this->lang("VMachine.machine_no_data"));
    //         }
    //         $mainM = $mainM->toArray();
    //         $postData['ao_id'] = $mainM['ao_id'];
    //         $updateData['m_id'] = $main_m_id;
    //         $updateData['ao_id'] = $mainM['ao_id'];
    //         $this->updateMachineChannel($updateData, [['m_id', '=', $m['m_id']]]);
    //     }
    //     $this->updateMachineChannel($updateData, [['m_id', '=', $m['m_id']]]);
    //     return $this->r(200, $this->lang("update_success"));
    // }
    public function updateSubM($postData)
    {
        $m = $this->getMachineAuxiliaryFind(['m_id' => $postData['m_id']], "m_id,machine_id,machine_name,machine_type,main_m_id,status");
        if (!$m) {
            return $this->r(100, $this->lang("VSubMachine.no_data"));
        }
        $m = $m->toArray();
        $old_main_m_id = $m['main_m_id'] ?? 0;
        $old_machine_type = $m['machine_type'] ?? 2;
        // 允许前端传 main_m_id = 0，表示解绑主柜；不传则沿用原值
        $main_m_id = $postData['main_m_id'];
        $machine_type = $postData['machine_type'];
        if (!in_array($machine_type, [1, 2])) $machine_type = 2;
        $mainChanged = $old_main_m_id != $main_m_id;
        $typeChanged = $old_machine_type != $machine_type;
        //查询machine_info下的sub_cabinet字段，如果是1则说明已经上报过副柜了，此时不允许修改类型和主柜了
        $info_value = $this->getMachineInfoValue([['m_id', '=', $m['main_m_id']]],'sub_cabinet');
        if(($m['status'] == 1 || $info_value == 1) && ($mainChanged || $typeChanged)){
            return $this->r(100,$this->lang("VSubMachine.is_online_no_change"));
        }
        $oldChannelPosition = $this->getSubMachineChannelPosition($old_machine_type);
        $newChannelPosition = $this->getSubMachineChannelPosition($machine_type);
        if($typeChanged && $old_main_m_id){
            //更新先不允许修改类型，如果要修改类型了，必须先解绑主柜，等修改完类型后再挂接主柜
            return $this->r(100, $this->lang("VSubMachine.type_change_require_unbind"));
        }
        $postData['ao_id'] = $this->manager['ao_id'] ?? 0;
        $mainM = null;
        $postData['status'] = 2;//未挂接未启用
        if ($main_m_id > 0) {
            // 校验同主柜同类型副柜是否已存在（排除当前副柜）
            $existsWhere = [
                ['main_m_id', '=', $main_m_id],
                ['machine_type', '=', $machine_type],
                ['m_id', '<>', $m['m_id']],
            ];
            $AuxiliaryCount = $this->getMachineAuxiliaryCount($existsWhere);
            if ($AuxiliaryCount > 0) {
                $msg = $machine_type == 1 ? $this->lang("VSubMachine.main_machine_only_one_arc") : $this->lang("VSubMachine.main_machine_only_one_sub");
                return $this->r(100, $msg);
            }

            $mainM = $this->getMachineFind(['m_id' => $main_m_id]);
            if (!$mainM) {
                return $this->r(100, $this->lang("VMachine.machine_no_data"));
            }
            $mainM = $mainM->toArray();
            $postData['ao_id'] = $mainM['ao_id'] ?? 0;
            $postData['status'] = 3;//已挂接未启用
        }

        if ($main_m_id == 0) {
            $postData['bind_time'] = 0;
        } elseif ($mainChanged) {
            $postData['bind_time'] = time();
        }

        $postData['machine_type'] = $machine_type;

        $this->startTrans();
        try {
            $result = $this->updateMachineAuxiliary($postData);
            if (!$result) {
                $this->rollbackTrans();
                return $this->r(100, $this->lang("update_fail"));
            }

            // 只在 main_m_id 或 machine_type 变化时处理货道
            if ($mainChanged || $typeChanged) {
                if ($main_m_id > 0) {
                    $from_main_m_id = $old_main_m_id > 0 ? $old_main_m_id : $main_m_id;
                    $updateChannel = [];
                    if ($mainChanged) {
                        $updateChannel['m_id'] = $main_m_id;
                        $updateChannel['machine_id'] = $mainM['machine_id'] ?? '';
                    }
                    if ($typeChanged) {
                        $updateChannel['channel_position'] = $newChannelPosition;
                    }

                    $updated = false;
                    if ($updateChannel) {
                        if ($typeChanged) {
                            // 类型变化时，同步修正货道编码前缀：type=1 -> 01，type=2 -> 02
                            $this->syncSubMachineChannelCodePrefix($from_main_m_id, $oldChannelPosition, $machine_type);
                        }
                        $updated = $this->updateSubMachineAdminChannels($from_main_m_id, $oldChannelPosition, $updateChannel);
                    }

                    if (!$updated && $old_main_m_id == 0) {
                        // 原来未挂接，当前挂接时补建默认货道
                        $this->addDefaultSubMachineChannels(
                            $main_m_id,
                            $mainM,
                            $machine_type,
                            $postData['machine_name'] ?? $m['machine_name']
                        );
                    }
                } elseif ($main_m_id == 0 && $old_main_m_id > 0) {
                    // 显式传 0 解绑主柜时，删除旧主柜下后台手动创建货道
                    $this->deleteSubMachineAdminChannels($old_main_m_id, $oldChannelPosition);
                }
            }

            $this->commitTrans();
            return $this->r(200, $this->lang("update_success"));
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e,1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    private function getSubMachineChannelPosition($machine_type)
    {
        return intval($machine_type) == 1 ? 2 : 3;
    }

    private function addDefaultSubMachineChannels($main_m_id, $mainM, $machine_type, $channel_name)
    {
        $channelPosition = $this->getSubMachineChannelPosition($machine_type);
        $channelAll = [];
        for ($i = 1; $i <= 3; $i++) {
            $channelAll[] = [
                'm_id' => $main_m_id,
                'machine_id' => $mainM['machine_id'] ?? '',
                'channel_code' => ($machine_type == 1 ? '010' : '020') . $i,
                'channel_position' => $channelPosition,
                'channel_name' => $channel_name,
                'is_admin' => 1,
                'width2' => 300,
            ];
        }
        return $this->addMachineMoreChannel($channelAll);
    }

    private function updateSubMachineAdminChannels($main_m_id, $channelPosition, $updateData)
    {
        $whereChannel = [
            ['m_id', '=', $main_m_id],
            ['channel_position', '=', $channelPosition],
        ];
        if (!$this->getMachineChannelCount($whereChannel)) {
            return false;
        }
        $this->updateMachineChannel($updateData, $whereChannel);
        return true;
    }

    private function syncSubMachineChannelCodePrefix($main_m_id, $channelPosition, $machine_type)
    {
        $whereChannel = [
            ['m_id', '=', $main_m_id],
            ['channel_position', '=', $channelPosition],
        ];
        $channelList = $this->getMachineChannelList($whereChannel, 0, 'mc_id,channel_code', 'mc_id asc');
        if (!$channelList) {
            return false;
        }

        $prefix = intval($machine_type) === 1 ? '01' : '02';
        foreach ($channelList as $index => $channel) {
            $oldCode = strval($channel['channel_code'] ?? '');
            $suffix = strlen($oldCode) > 2 ? substr($oldCode, 2) : strval($index + 1);
            $newCode = $prefix . $suffix;
            $this->updateMachineChannel([
                'mc_id' => $channel['mc_id'],
                'channel_code' => $newCode,
            ]);
        }
        return true;
    }

    private function deleteSubMachineAdminChannels($main_m_id, $channelPosition)
    {
        $whereChannel = [
            ['m_id', '=', $main_m_id],
            ['channel_position', '=', $channelPosition],
        ];
        if (!$this->getMachineChannelCount($whereChannel)) {
            return false;
        }
        $this->delMachineChannel($whereChannel);
        return true;
    }

    /**
     * 副柜新增货道（仅边柜可操作，且必须绑定主柜）
     * @param array $postData
     * @return array|string
     */
    public function addSubMChannel($postData)
    {
        $m_id = intval($postData['m_id'] ?? 0);
        $subMachine = $this->getMachineAuxiliaryFind(['m_id' => $m_id], 'm_id,machine_name,machine_type,main_m_id');
        if (!$subMachine) {
            return $this->r(100, $this->lang("VSubMachine.no_data"));
        }
        $subMachine = $subMachine->toArray();

        if (intval($subMachine['machine_type'] ?? 0) !== 2) {
            return $this->r(100, $this->lang("VSubMachine.only_edge_add_channel"));
        }

        $main_m_id = intval($subMachine['main_m_id'] ?? 0);
        if ($main_m_id <= 0) {
            return $this->r(100, $this->lang("VSubMachine.bind_main_before_add_channel"));
        }

        $mainM = $this->getMachineFind(['m_id' => $main_m_id], 'm_id,machine_id,ao_id');
        if (!$mainM) {
            return $this->r(100, $this->lang("VMachine.machine_no_data"));
        }
        $mainM = $mainM->toArray();

        $channelList = $this->getMachineChannelList([
            ['m_id', '=', $main_m_id],
            ['channel_position', '=', 3],
        ], 0, 'channel_code', 'mc_id asc');

        $existCodes = [];
        $maxNum = 0;
        if ($channelList) {
            foreach ($channelList as $row) {
                $code = strval($row['channel_code'] ?? '');
                if (preg_match('/^02(\d+)$/', $code, $matches)) {
                    $existCodes[$code] = 1;
                    $num = intval($matches[1]);
                    if ($num > $maxNum) {
                        $maxNum = $num;
                    }
                }
            }
        }

        $channelCode = trim(strval($postData['channel_code'] ?? ''));
        if (!$channelCode) {
            $channelCode = '02' . str_pad((string)($maxNum + 1), 2, '0', STR_PAD_LEFT);
        }

        if (isset($existCodes[$channelCode])) {
            return $this->r(100, '货道编号已存在');
        }

        $insert = $postData;
        unset($insert['m_id']);
        unset($insert['mc_id']);

        $insert = array_merge($insert, [
            'm_id' => $main_m_id,
            'machine_id' => $mainM['machine_id'] ?? '',
            'channel_code' => $channelCode,
            'channel_position' => 3,
            'channel_name' => $postData['channel_name'] ?? ($subMachine['machine_name'] ?? ''),
            'is_admin' => 1,
            'width2' => $postData['width2'] ?? 300,
        ]);

        $mc_id = $this->addMachineChannel($insert);
        if (!$mc_id) {
            return $this->r(100, $this->lang('add_fail'));
        }

        return $this->r(200, $this->lang('add_success'), [
            'mc_id' => $mc_id,
            'channel_code' => $channelCode,
        ]);
    }

    /**
     * 副柜删除货道（仅边柜可操作）
     * @param array $postData
     * @return array|string
     */
    public function delSubMChannel($postData)
    {
        $m_id = intval($postData['m_id'] ?? 0);
        $mc_id = intval($postData['mc_id'] ?? 0);

        $subMachine = $this->getMachineAuxiliaryFind(['m_id' => $m_id], 'm_id,machine_type,main_m_id');
        if (!$subMachine) {
            return $this->r(100, $this->lang("VSubMachine.no_data"));
        }
        $subMachine = $subMachine->toArray();

        if (intval($subMachine['machine_type'] ?? 0) !== 2) {
            return $this->r(100, $this->lang("VSubMachine.only_edge_del_channel"));
        }

        $main_m_id = intval($subMachine['main_m_id'] ?? 0);
        if ($main_m_id <= 0) {
            return $this->r(100, $this->lang('query_fail'));
        }

        $channel = $this->getMachineChannelFind([
            ['mc_id', '=', $mc_id],
            ['m_id', '=', $main_m_id],
            ['channel_position', '=', 3],
        ], 'mc_id');
        if (!$channel) {
            return $this->r(100, $this->lang('query_fail'));
        }

        $result = $this->delMachineChannel([['mc_id', '=', $mc_id]]);
        if (!$result) {
            return $this->r(100, $this->lang('del_fail'));
        }

        return $this->r(200, $this->lang('del_success'));
    }

    /**
     * 删除设备信息
     * @param $m_id
     * @return array|\think\response\Json
     */
    public function delSubM($m_id)
    {
        $m = $this->getMachineAuxiliaryFind(['m_id' => $m_id], "m_id,status");
        if (!$m) {
            return $this->r(200,$this->lang("action_success"));
        }
        //如果副柜是已挂接已使用状态则不允许删除
        $info_value = $this->getMachineInfoValue([['m_id', '=', $m['main_m_id']]],'sub_cabinet');
        if($m['status'] == 1 || $info_value == 1){
            return $this->r(100,$this->lang("VSubMachine.is_online_no_del"));
        }
        $main_m_id = $m['main_m_id'] ?? 0;
        $where[] = ['m_id',"=",$m_id];
        $this->delMachineAuxiliary($where);
        if($main_m_id){
            //如果生成了货道则删除货道信息,此处只能删除后台手动创建的货道
            $this->deleteSubMachineAdminChannels($main_m_id, $m['machine_type'] == 1 ? 2 : 3);
        }
        return $this->r(200,$this->lang("action_success"));
    }

    /**
     * 判断主设备是否已关联指定类型的副柜
     * @param int $main_m_id 主柜ID
     * @param int $machine_type 副柜类型：1弧柜 2边柜
     * @param int $exclude_sub_m_id 排除的副柜ID（用于更新时）
     * @return bool true: 已关联, false: 未关联
     */
    public function checkMainMachineCabinetRelation($main_m_id, $machine_type,$m_id = 0)
    {
        // 现在所有副柜都在 b_mc_id，通过 join machine_auxiliary 表来区分类型
        $where = [
            ['r.main_mc_id', '=', $main_m_id],
            ['a.machine_type', '=', $machine_type]
        ];
        if ($m_id) {
            $where[] = ['r.b_mc_id', '<>', $m_id];
        }
        $count = MachineMainRelationModel::alias('r')
            ->join('machine_auxiliary a', 'r.b_mc_id = a.m_id')
            ->where($where)
            ->count();

        return $count;
    }

    
    /**
     * 导出副柜列表
     * @param $where
     * @return array|\think\response\Json
     */
    public function exportSubM($where)
    {
        $list = $this->getMachineAuxiliaryList($where, 0, '*', 'created_at desc');

        if (count($list)) {
            $list = $list->toArray();
            foreach ($list as $key => $item) {
                if ($item['main_m_id'] > 0) {
                    $mainM = $this->getMachineFind(['m_id' => $item['main_m_id']], 'machine_id,machine_name');
                    $item['main_machine_id'] = $mainM['machine_id'] ?? '';
                    $item['main_machine_name'] = $mainM['machine_name'] ?? '';
                } else {
                    $item['main_machine_id'] = '';
                    $item['main_machine_name'] = '';
                }
                $list[$key] = $item;
                unset($list[$key]['m_id']);
            }
            if($item['machine_type'] == 1) {
                $item['machine_type'] = '弧柜';
            } else{
                $item['machine_type'] = '边柜';
            } 
            $title = [
                "machine_id" => "副柜编号",
                "machine_name" => "副柜名称",
                "main_machine_id" => "主柜编号",
                "main_machine_name" => "主柜名称",
                "address" => "详细地址",
                "machine_type" => "设备类型",
                "remark" => "备注",
                "created_at" => "创建时间",
                "updated_at" => "修改时间"
            ];

            $filename = "副柜信息-" . date("YmdHis");
            return $this->sendToExport("副柜列表", $filename, $title, $list);
        }
        return $this->rFail($this->lang("query_fail"));
    }

    /**
     * 导入副柜Excel
     * @param $data
     * @return array|string
     */
    public function importSubM($data)
    {
        try {
            $path = root_path() . "public" . $data['file_path'];
            $title = ["machine_id", "machine_name","address", "machine_type_desc"];
            $other = [
                'manager_id' => $this->manager['manager_id'] ?? 0, 
                'ao_id' => $this->manager['ao_id'] ?? 0
            ];
            $importData = Excel::importExcel($path, $title, $other);
            if ($importData) {
                $this->startTrans();
                $insertAuxiliary = [];
                $mainMIdsMap = []; // 存储导入数据中副柜索引与对应主柜ID的映射
                
                foreach ($importData as $key => $value) {
                    if (empty($value['machine_id'])) continue;
                    // 处理设备类型文字转数字
                    if (isset($value['machine_type_desc'])) {
                        if ($value['machine_type_desc'] == "弧柜") $value['machine_type'] = 1;
                        elseif ($value['machine_type_desc'] == "边柜") $value['machine_type'] = 2;
                        else $value['machine_type'] = 2; // 默认边柜
                        unset($value['machine_type_desc']);
                    }
                    //校验副柜编号是否唯一
                    $exists = $this->getMachineAuxiliaryCount(['machine_id' => $value['machine_id']]);
                    if ($exists) {
                        continue; // 跳过已存在的副柜编号
                    }
                    $value['status'] = 2; // 未挂接未启用
                    $value['manager_id'] = $this->manager['manager_id'] ?? 0;
                    $insertAuxiliary[$key] = $value;
                }

                if ($insertAuxiliary) {

                    $this->addMachineAuxiliaryMore($insertAuxiliary);
                }

                $this->commitTrans();
                return $this->rSuccess($this->lang("action_success"));
            }
            return $this->r(100, '获取不到Excel文档中的数据');
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 副柜挂接主柜
     * @param $postData
     * @return array|\think\response\Json
     */
    public function bindMainMachine($postData)
    {
        $m_id = intval($postData['m_id']);
        $main_m_id = intval($postData['main_m_id']); // 允许为0，0表示解绑

        $m = $this->getMachineAuxiliaryFind(['m_id' => $m_id], "m_id,machine_id,machine_name,machine_type,main_m_id");
        if (!$m) {
            return $this->r(100, $this->lang("VSubMachine.no_data"));
        }
        $m = $m->toArray();

        $machine_type = intval($m['machine_type'] ?? 2);
        $channelPosition = $this->getSubMachineChannelPosition($machine_type);
        $old_main_m_id = intval($m['main_m_id'] ?? 0);

        if ($old_main_m_id === $main_m_id) {
            return $this->r(200, $this->lang("action_success"));
        }

        $mainM = null;
        if ($main_m_id > 0) {
            // 查询此主柜是否已经关联过同类型副柜（排除当前副柜）
            $existsWhere = [
                ['main_m_id', '=', $main_m_id],
                ['machine_type', '=', $machine_type],
                ['m_id', '<>', $m['m_id']],
            ];
            $AuxiliaryCount = $this->getMachineAuxiliaryCount($existsWhere);
            if ($AuxiliaryCount > 0) {
                $msg = $machine_type == 1 ? $this->lang("VSubMachine.main_machine_only_one_arc") : $this->lang("VSubMachine.main_machine_only_one_sub");
                return $this->r(100, $msg);
            }

            $mainM = $this->getMachineFind(['m_id' => $main_m_id]);
            if (!$mainM) {
                return $this->r(100, $this->lang("VMachine.machine_no_data"));
            }
            $mainM = $mainM->toArray();
        }

        $this->startTrans();
        try {
            // 仅更新副柜与主柜绑定关系，不处理machine_type变化
            $updateSub = [
                'm_id' => $m_id,
                'main_m_id' => $main_m_id,
                'status' => $main_m_id > 0 ? 3 : 2,
                'ao_id' => $mainM['ao_id'] ?? 0,
                'bind_time' => $main_m_id > 0 ? time() : 0,
            ];
            $this->updateMachineAuxiliary($updateSub);

            if ($main_m_id > 0) {
                $from_main_m_id = $old_main_m_id > 0 ? $old_main_m_id : $main_m_id;
                $updateChannel = [
                    'm_id' => $main_m_id,
                    'machine_id' => $mainM['machine_id'] ?? '',
                    'ao_id' => $mainM['ao_id'] ?? 0,
                ];
                $updated = $this->updateSubMachineAdminChannels($from_main_m_id, $channelPosition, $updateChannel);

                if (!$updated && $old_main_m_id == 0) {
                    // 首次挂接：创建默认货道
                    $this->addDefaultSubMachineChannels($main_m_id, $mainM, $machine_type, $m['machine_name']);
                }
            } elseif ($old_main_m_id > 0) {
                // main_m_id=0 解绑：删除旧主柜下后台手动创建货道
                $this->deleteSubMachineAdminChannels($old_main_m_id, $channelPosition);
            }

            $this->commitTrans();
            return $this->r(200, $this->lang("action_success"));
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }
}