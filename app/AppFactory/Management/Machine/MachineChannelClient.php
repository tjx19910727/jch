<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:42
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\AppFactory;
use app\AppFactory\Kernel\Traits\Auth\AuthManagerMachineTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsChangeTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineChannelTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineGoodsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineInfoTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineMainRelationTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Management\ManagementClient;
use think\facade\Db;

class MachineChannelClient extends ManagementClient
{
    use MachineTrait,MachineChannelTrait,MachineGoodsTrait,MachineInfoTrait,MachineMainRelationTrait;
    use GoodsTrait,GoodsChangeTrait;
    use AuthManagerMachineTrait;

    /**
     * 获取空槽、BAD、空货数量
     * @param $where
     * @return array
     */
    public function getData()
    {
        $empty = 0;
        $bad = 0;
        $stockOut = 0;
        $machineIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']],"machine_id");
        if ($machineIds) {
            $where[] = ['machine_id', 'in', $machineIds];
            $whereEmpty = $where;
            $whereEmpty['g_id'] = 0;
            $empty = $this->getMachineChannelCount($whereEmpty);

            $whereBad = $where;
            $whereBad['status'] = 3;
            $bad = $this->getMachineChannelCount($whereBad);

            $whereStockOut = $where;
            $whereStockOut['stock'] = 0;
            $stockOut = $this->getMachineChannelCount($whereStockOut);
        }
        $data = [
            "empty" => $empty,
            "bad" => $bad,
            "stockOut" => $stockOut,
        ];
        return $data;
    }

        /**
     * 获取空槽、BAD、空货数量 V2
     * 如果machine_info表的sub_cabinet为2不取channel_position为2的数据
     * @return array
     */
    public function getDataV2()
    {
        $empty = 0;
        $bad = 0;
        $stockOut = 0;
        $machineIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']],"machine_id");
        if ($machineIds) {
            $where[] = ['machine_id', 'in', $machineIds];
            $whereEmpty = $where;
            $whereEmpty['g_id'] = 0;
            $empty = $this->getMachineChannelCountV2($whereEmpty);

            $whereBad = $where;
            $whereBad['status'] = 3;
            $bad = $this->getMachineChannelCountV2($whereBad);

            $whereStockOut = $where;
            $whereStockOut['stock'] = 0;
            $stockOut = $this->getMachineChannelCountV2($whereStockOut);
        }
        $data = [
            "empty" => $empty,
            "bad" => $bad,
            "stockOut" => $stockOut,
        ];
        return $data;
    }

    /**
     * 获取空槽货道列表
     * @param $where
     * @return array|string
     */
    public function getEmptyList($where)
    {
        if ($this->manager['pid'] > 0) {
            $mIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], "m_id");
            if ($mIds) {
                $where[] = ['m_id', 'in', $mIds];
            }
        }
        $where[] = ['status','<>',2];
        $where['g_id'] = 0;
        $list = $this->getMachineChannelList($where, 0, 'm_id,machine_id, 
        (SELECT machine_name FROM machine m WHERE m.m_id = a.m_id) machine_name ,
        count(mc_id) empty_num', '', '', 'm_id');
        if ($list) {
            foreach ($list as $key => $value) {
                $whereEmpty = [];
                // 副柜不可用状态下，只查主柜货道
                $sub_cabinet = $this->getMachineInfoValue(['m_id' => $value['m_id']],'sub_cabinet');
                if (!$sub_cabinet || $sub_cabinet == 2 ) $whereEmpty['channel_position'] = 1;
                $whereEmpty['m_id'] = $value['m_id'];
                $whereEmpty[] = ['status',"<>",2];
                $value['total_channel'] = $this->getMachineChannelCount($whereEmpty);

                $whereEmpty["g_id"] = 0;
                $emptyList = $this->getMachineChannelColumn($whereEmpty, 'channel_code');
                $value['empty_channel'] = implode(",", $emptyList ?? []);
                $value['empty_ratio'] = $value['total_channel'] > 0 ? (bcmul(bcdiv($value['empty_num'], $value['total_channel'], 3), 100, 1) . "%" ): "0%";
            }
        }
        return $this->rQ($list);
    }

    /**
     * 获取Bad货道列表
     * @param $where
     * @return array|string
     */
    public function getBadList($where)
    {
        if ($this->manager['pid'] > 0) {
            $mIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], "m_id");
            $where[] = ['m_id', 'in', $mIds];
        }
        $where['status'] = 3;
        $expr = "(a.channel_position <> 2 OR EXISTS(SELECT 1 FROM machine_info mi WHERE mi.m_id = a.m_id AND mi.sub_cabinet = 1))";
        if (!empty($where['raw'])) {
            $where['raw'] .= " AND " . $expr;
        } else {
            $where['raw'] = $expr;
        }
        $list = $this->getMachineChannelList($where, 0, 'm_id,machine_id, 
            (SELECT machine_name FROM machine m WHERE m.m_id = a.m_id) machine_name ,
            count(mc_id) bad_num', '', '', 'm_id');
        if ($list) {
            foreach ($list as $key => $value) {
                $whereBad = [];
                // 副柜不可用状态下，只查主柜货道
                $sub_cabinet = $this->getMachineInfoValue(['m_id' => $value['m_id']],'sub_cabinet');
                if (!$sub_cabinet || $sub_cabinet == 2 ) $whereBad['channel_position'] = 1;
                $whereBad['m_id'] = $value['m_id'];
                $value['total_channel'] = $this->getMachineChannelCount($whereBad);
                $whereBad['status'] = 3;
                $badList = $this->getMachineChannelColumn($whereBad, 'channel_code');
                //badList为空时，unset掉
                // if (count($badList) == 0) {
                //     unset($list[$key]);
                //     continue;
                // }
                $value['bad_channel'] = implode(",", $badList ?? []);
                $value['bad_ratio'] = $value['total_channel'] > 0 ? (bcmul(bcdiv($value['bad_num'], $value['total_channel'], 3), 100, 1) . "%") : "0%";
            }
        }
        return $this->rQ($list);
    }

    /**
     * 获取空货列表
     * @param $where
     * @return array|string
     */
    public function getStockOutList($where)
    {
        if ($this->manager['pid'] > 0) {
            $mIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], "m_id");
            $where[] = ['m_id', 'in', $mIds];
        }
        $where['stock'] = 0;
        $list = $this->getMachineChannelList($where, 0, 'm_id,machine_id, 
            (SELECT machine_name FROM machine m WHERE m.m_id = a.m_id) machine_name ,
            count(mc_id) stock_out_num', '', '', 'm_id');
        if ($list) {
            foreach ($list as $key => $value) {
                $whereStockOut = [];

                // 副柜不可用状态下，只查主柜货道
                $sub_cabinet = $this->getMachineInfoValue(['m_id' => $value['m_id']],'sub_cabinet');
                if (!$sub_cabinet || $sub_cabinet == 2 )
                    $whereStockOut['channel_position'] = 1;

                $whereStockOut['m_id'] = $value['m_id'];
                $value['total_channel'] = $this->getMachineChannelCount($whereStockOut);
                $whereStockOut['stock'] = 0;
                $stockOutList = $this->getMachineChannelColumn($whereStockOut, 'channel_code');
                $value['stock_out_channel'] = implode(",", $stockOutList ?? []);
                $value['stock_out_ratio'] = $value['total_channel'] > 0 ? (bcmul(bcdiv($value['stock_out_num'], $value['total_channel'], 3), 100, 1) . "%") : "0%";
            }
        }
        return $this->rQ($list);
    }

    /**
     * 修改货道信息
     * @param $postData
     * @return array|string
     */
    public function updateMc($postData)
    {
        $mc = $this->getMachineChannelFind(['mc_id' => $postData['mc_id']],'m_id,channel_position,machine_id,mc_id,channel_code,mg_id,g_id,g_name,gc_id,gc_name,pic,sku,bar_code,stock');
        //如果是货道是边柜，查询主柜信息
        // if(isset($mc['channel_position']) && $mc['channel_position'] == 3){
        //     $main_m_id = $this->getMachineMainRelationValue(['b_mc_id' => $mc['m_id']], 'main_mc_id');
        //     $mc['m_id'] = $main_m_id;
        // }
        $machine = $this->getMachineFind(['m_id' => $mc['m_id']],'m_id,machine_id,machine_name,ao_id');
        // 商品变化基础数据
        $insertGChange = [
            "m_id" => $machine['m_id'],
            "machine_id" => $machine['machine_id'],
            "machine_name" => $machine['machine_name'],
            "mc_id" => $mc['mc_id'],
            "channel_code" => $mc['channel_code'],
            "mg_id" => $mc['mg_id'],
            "g_id" => $mc['g_id'],
            "g_name" => $mc['g_name'],
            "gc_id" => $mc['gc_id'],
            "gc_name" => $mc['gc_name'],
            "pic" => $mc['pic'],
            "sku" => $mc['sku'],
            "bar_code" => $mc['bar_code'],
            "ao_id" => $machine['ao_id'],
        ];

        // 更换货道商品处理
        if (isset($postData['g_id']) && $postData['g_id'] > 0) {
            $old = $this->getMachineChannelFind(['mc_id' => $postData['mc_id']]);
            if ($old['g_id'] != $postData['g_id']) {
                $goods = $this->getGoodsFind(['g_id' => $postData['g_id']],"g_id,g_name,gc_id,gc_name,pic,sku,bar_code");
                $postData = array_merge($postData,$goods->toArray() ?? []);

                // 20250604 更换货道商品，后台换货-货架下货旧商品，下货数量为当前库存
                $insertGc = array_merge($insertGChange,[
                    "change_value" => $mc['stock'],  // 变化数量为当前货道库存
                    "type" => 7,
                    "desc" => $this->lang("goodsChange.backstage_exchange_mc_under_old"),
                    "position" => 1,
                ]);
                $this->addGoodsChange($insertGc);

                // 20250604 更换货道商品，后台换货-货架上货新商品，新商品数据替换原货道数据，上货数量为postData的stock参数
                $insertGc = array_merge($insertGChange,[
                    "mg_id" => $postData['mg_id'] ?? 0,
                    "g_id" => $goods['g_id'],
                    "g_name" => $goods['g_name'],
                    "gc_id" => $goods['gc_id'],
                    "gc_name" => $goods['gc_name'],
                    "pic" => $goods['pic'],
                    "sku" => $goods['sku'],
                    "bar_code" => $goods['bar_code'],
                    "change_value" => $postData['stock'],  // 变化数量为postData的stock参数
                    "type" => 6,
                    "desc" => $this->lang("goodsChange.backstage_exchange_mc_display_new"),
                    "position" => 1,
                ]);
                $this->addGoodsChange($insertGc);
            }
        } else {
            // 20250604 非更新货道商品，检查库存变化（后台上货6、后台下货7），库存值相等时不记录变化
            if (isset($postData['stock']) && $postData['stock'] > 0 && $mc['stock'] != $postData['stock']) {
                $changeValue = bcsub($postData['stock'] ,$mc['stock']); // 变化数量为：postData的stock - 当前货道库存
                $insertGc = array_merge($insertGChange, [
                    "change_value" => $changeValue,
                    "type" => $changeValue > 0 ? 6 : 7 ,   // >0：后台上货，<0 后台下货
                    "desc" => $changeValue > 0 ? $this->lang("goodsChange.backstage_rep_mc_inc_stock"): $this->lang("goodsChange.backstage_rep_mc_dec_stock"),
                    "position" => 1,
                ]);
                $this->addGoodsChange($insertGc);
            }
            // 20250604 bad状态变化（后台BAD 8，后台恢复BAD 9），变化数量为当前货架库存值
            if (isset($postData['status']) && $postData['status'] != $mc['status'] && in_array($postData['status'],[1,3])) {
                $insertGc = array_merge($insertGChange, [
                    "change_value" => $mc['stock'],
                    "type" => $postData['status'] == 3 ? 8 : 9 ,   // 3：后台BAD，1：后台恢复BAD
                    "desc" => $postData['status'] == 3 ? $this->lang("goodsChange.backstage_mc_bad") : $this->lang("goodsChange.backstage_mc_not_bad"),
                    "position" => 1,
                ]);
                $this->addGoodsChange($insertGc);
            }
        }
        $result = $this->updateMachineChannel($postData);
        if ($result) {
            // 发送触发货道更新数据,如果是边柜货道不发送
            if ($mc['channel_position'] != 3) {
                $this->sendToMachine(['machine_id' => $mc['machine_id']],'updateMc',['mc_id' => $mc['mc_id']]);
            }
            return $this->r(200,$this->lang("action_success"));
        }
        return $this->r(100,$this->lang('action_fail'));
    }

    public function lockPrice($postData)
    {
        if (!isset($postData['m_id']) || !$postData['m_id']) return $this->r(100,$this->lang("VMachineChannel.m_id_require"));
        if (!isset($postData['update_price']) || !$postData['update_price'] || !in_array($postData['update_price'],[1,2]))
            return $this->r(100,$this->lang("VMachineChannel.update_price_error"));
        // 解锁，同步设备商品库或核心商品库价格
        if ($postData['update_price'] == 2) {
            $mc = $this->getMachineChannelList(['m_id' => $postData['m_id']],0,'update_price,cost_price,market_price,retail_price,mg_id,g_id,mc_id,machine_id');
            if ($mc) {
                $mc = $mc->toArray();
                foreach ($mc as $key => $value) {
                    $mg = $this->getMachineGoodsFind(['mg_id' => $value['mg_id']],'cost_price,market_price,retail_price');
                    if ($mg) {
                        $mg = $mg->toArray();
                        $update = $mg;
                        $update['mc_id'] = $value['mc_id'];
                        $update['update_price'] = $postData['update_price'];
                    } else {
                        $goods = $this->getGoodsFind(['g_id' => $value['g_id']],'cost_price,market_price,retail_price');
                        $update = $goods;
                        $update['mc_id'] = $value['mc_id'];
                        $update['update_price'] = $postData['update_price'];
                    }
                    $this->updateMachineChannel($update);
                    // 发送触发货道更新数据
                    $this->sendToMachine(['machine_id' => $value['machine_id']],'updateMc',['mc_id' => $value['mc_id']]);
                }
            }
        }
        // 锁定货架价格
        if ($postData['update_price'] == 1) {
            $this->updateMachineChannel(['update_price' => $postData['update_price']], ['m_id' => $postData['m_id']]);
        }
        return $this->r(200,$this->lang("action_success"));
    }

    /**
     * 按SKU导出货道数据
     * @param $m_id
     * @return mixed
     */
    public function exportMcSku($m_id)
    {
        $field = "machine_id,sku,g_name,count(mc_id) channel_num,sum(capacity) capacity,sum(stock) stock,sum(frozen_stock) frozen_stock,cost_price,retail_price";
        $list = $this->getMachineChannelList(['m_id' => $m_id],0,$field,"","","sku");
        if ($list) {
            $list = $list->toArray();
            if ($list) {
                $sku = array_column($list,'sku');
                $machine_name = "";
                foreach ($list as $key => $value) {
                    if (!$machine_name) $machine_name = $this->getMachineValue(['m_id' => $m_id],'machine_name');
                    $value['machine_name'] = $machine_name;
                    $value['channel_code'] = $this->getMachineChannelValue(['m_id' => $m_id,'sku' => $sku],'channel_code');
                    $list[$key] = $value;
                }
                $title = [
                    "machine_id" => "设备编号",
                    "machine_name" => "设备名称",
                    "sku" => "SKU",
                    "g_name" => "商品名称",
                    "channel_code" => "槽位",
                    "channel_num" => "货道数量",
                    "capacity" => "最大数量",
                    "stock" => "当前数量",
                    "frozen_stock" => "预定数量",
                    "retail_price" => "售价",
                    "cost_price" => "成本价",
                ];
                $filename = "按SKU铺货计划-" . date("YmdHis");
                return $this->sendToExport("设备管理-设备货架", $filename, $title, $list);
            }
        }
        return $this->r(100,$this->lang("query_fail"));
    }

    /**
     * 导出货架列表
     * @param $m_id
     * @return array|\think\response\Json
     */
    public function exportMc($m_id)
    {
        $field = "machine_id,channel_code,sku,g_name,capacity,stock,frozen_stock,cost_price,retail_price";
        $list = $this->getMachineChannelList(['m_id' => $m_id],0,$field);
        if ($list) {
            $list = $list->toArray();
            if ($list) {
                $machine_name = "";
                foreach ($list as $key => $value) {
                    if (!$machine_name) $machine_name = $this->getMachineValue(['m_id' => $m_id],'machine_name');
                    $value['machine_name'] = $machine_name;
                    $list[$key] = $value;
                }
                $title = [
                    "machine_id" => "设备编号",
                    "machine_name" => "设备名称",
                    "channel_code" => "槽位",
                    "sku" => "SKU",
                    "g_name" => "商品名称",
                    "capacity" => "最大数量",
                    "stock" => "当前数量",
                    "frozen_stock" => "预定数量",
                    "retail_price" => "售价",
                    "cost_price" => "成本价",
                ];
                $filename =  "货架铺货计划-" . date("YmdHis");
                return $this->sendToExport("设备管理-设备货架", $filename, $title, $list);
            }
        }
        return $this->r(100,$this->lang("query_fail"));
    }


    public function setMachineChannelGiftPoints($m_id, $integral_rate)
    {
        if (!$m_id) return $this->r(100,$this->lang("VMachineChannel.m_id_require"));
        if ($integral_rate <= 0) return $this->r(100,$this->lang("请设置正常积分比例"));
        $machine_channel_lists = $this->getMachineChannelList(['m_id' => $m_id])->toArray();
        foreach($machine_channel_lists as $v){
            $gift_points = bcmul($v['retail_price'], $integral_rate, 2);
            $flag[] = $this->updateMachineChannel(['gift_points' => $gift_points], ['mc_id' => $v['mc_id']]);
        }
        if ($this->checkFlag($flag)) {
            return $this->r(200,$this->lang("action_success"));
        }
        return $this->r(100,$this->lang('action_fail'));
    }

    public function getMChannelList($where,$pageNum = 0,$field = "",$order = "")
    {
        //先查询设备详情
        $machine = $this->getMachineFind($where,'m_id,machine_id,machine_name,ao_id,vending_machine_type');
        if (!$machine) return $this->r(100,$this->lang("VMachine.machine_no_data"));
        //把货道的channel_position设置成设备相同的vending_machine_type
        $list = $this->getMachineChannelList($where,$pageNum,$field,$order);
        $list = $list->toArray();
        // foreach ($list as $key => &$value) {
        //     if (!isset($value['channel_code'])) {
        //         continue;
        //     }
        //     $channelCode = strval($value['channel_code']);
        //     if (strpos($channelCode, '02') === 0) {
        //         $value['channel_position'] = 3;
        //     } elseif (strpos($channelCode, '01') === 0) {
        //         $value['channel_position'] = 2;
        //     }
        // }
        return $this->rQ($list);
    }

    
    /**
     * 批量修改货道信息
     * @param $postData
     * @return array|string
     */
    public function batchUpdateMc($postData, $where = [])
    {
        //先查询是否有这台设备的权限
        $machine = $this->getMachineFind($where,'m_id,machine_id,machine_name,ao_id');
        if (!$machine) return $this->r(100,$this->lang("VMachine.machine_no_data"));
        $mc_ids = $postData['mc_ids'] ?? '';
        $mc_ids = explode(",",$mc_ids);

        if (!$mc_ids) return $this->r(100, $this->lang("VMachineChannel.mc_id_require"));

        $updateData = [];
        if(isset($postData['retail_price'])){
            $updateData['retail_price'] = $postData['retail_price'] < 0 ? 0 : $postData['retail_price'];
        }
        if(isset($postData['gift_points'])){
            $updateData['gift_points'] = $postData['gift_points'] < 0 ? 0 : $postData['gift_points'];
        }
        if(isset($postData['stock_warning'])){
            $updateData['stock_warning'] = $postData['stock_warning'] < 0 ? 0 : $postData['stock_warning'];
        }
        if (!$updateData) return $this->r(100, $this->lang("action_fail"));

        try {
            foreach ($mc_ids as $mc_id) {
                $mc = $this->getMachineChannelFind(['mc_id' => $mc_id,'m_id' => $machine['m_id']], 'mc_id,retail_price,gift_points,stock_warning,old_retail_price,old_gift_points,old_stock_warning,machine_id');
                if (!$mc) continue;

                $saveData = $updateData;
                // 只要传了这个字段，就要保存当前值为旧值
                if (isset($updateData['retail_price'])) {
                    $saveData['old_retail_price'] = $mc['retail_price'];
                }
                if (isset($updateData['gift_points'])) {
                    $saveData['old_gift_points'] = $mc['gift_points'];
                }
                if (isset($updateData['stock_warning'])) {
                    $saveData['old_stock_warning'] = $mc['stock_warning'];
                }
                $this->updateMachineChannel($saveData, ['mc_id' => $mc_id]);
                // 发送触发货道更新数据
                $this->sendToMachine(['machine_id' => $mc['machine_id']], 'updateMc', ['mc_id' => (int)$mc_id]);
            }
            return $this->r(200, $this->lang("action_success"));
        } catch (\Exception $e) {
            return $this->r(100, $e->getMessage());
        }
    }

    /**
     * 批量还原货道信息
     * @param $postData
     * @return array|string
     */
    public function batchRestoreMc($postData,$where = [])
    {
        //先查询是否有这台设备的权限
        $machine = $this->getMachineFind($where,'m_id,machine_id,machine_name,ao_id');
        if (!$machine) return $this->r(100,$this->lang("VMachine.machine_no_data"));
        $mc_ids = $postData['mc_ids'] ?? [];
        $fields = $postData['fields'] ?? []; // ['retail_price', 'gift_points', 'stock_warning']
        if (!$mc_ids || !$fields) return $this->r(100, $this->lang("VMachineChannel.mc_id_require"));

        $this->startTrans();
        try {
            foreach ($mc_ids as $mc_id) {
                $mc = $this->getMachineChannelFind(['mc_id' => $mc_id,'m_id' => $machine['m_id']], 'mc_id,old_retail_price,old_gift_points,old_stock_warning,machine_id');
                if (!$mc) continue;

                $restoreData = [];
                if (in_array('retail_price', $fields) && $mc['old_retail_price'] != -1) {
                    $restoreData['retail_price'] = $mc['old_retail_price'];
                    $restoreData['old_retail_price'] = -1;
                }
                if (in_array('gift_points', $fields) && $mc['old_gift_points'] != -1) {
                    $restoreData['gift_points'] = $mc['old_gift_points'];
                    $restoreData['old_gift_points'] = -1;
                }
                if (in_array('stock_warning', $fields) && $mc['old_stock_warning'] != -1) {
                    $restoreData['stock_warning'] = $mc['old_stock_warning'];
                    $restoreData['old_stock_warning'] = -1;
                }

                if ($restoreData) {
                    $this->updateMachineChannel($restoreData, ['mc_id' => $mc_id]);
                    // 发送触发货道更新数据
                    $this->sendToMachine(['machine_id' => $mc['machine_id']], 'updateMc', ['mc_id' => (int)$mc_id]);
                }
            }
            $this->commitTrans();
            return $this->r(200, $this->lang("action_success"));
        } catch (\Exception $e) {
            $this->rollbackTrans();
            return $this->r(100, $e->getMessage());
        }
    }
}