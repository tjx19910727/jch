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
use app\AppFactory\Kernel\Traits\Goods\GoodsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineChannelTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineGoodsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Management\ManagementClient;

class MachineChannelClient extends ManagementClient
{
    use MachineTrait,MachineChannelTrait,MachineGoodsTrait;
    use GoodsTrait;
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
     * 获取空槽货道列表
     * @param $where
     * @return array|string
     */
    public function getEmptyList($where)
    {
        $list = [];
        $machineIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']],"machine_id");
        if ($machineIds) {
            $where[] = ['machine_id', 'in', $machineIds];
            $where['g_id'] = 0;
            $list = $this->getMachineChannelList($where, 0, 'm_id,machine_id, 
        (SELECT machine_name FROM machine m WHERE m.m_id = a.m_id) machine_name ,
        count(mc_id) empty_num', '', '', 'm_id');
            if ($list) {
                foreach ($list as $key => $value) {
                    $value['total_channel'] = $this->getMachineChannelCount(['machine_id' => $value['machine_id']]);
                    $emptyList = $this->getMachineChannelColumn(['machine_id' => $value['machine_id'], 'g_id' => 0], 'channel_code');
                    $value['empty_channel'] = implode(",", $emptyList ?? []);
                    $value['empty_ratio'] = bcmul(bcdiv($value['empty_num'], $value['total_channel'], 3), 100, 1) . "%";
                }
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
        $list = [];
        $machineIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']],"machine_id");
        if ($machineIds) {
            $where[] = ['machine_id', 'in', $machineIds];
            $where['status'] = 3;
            $list = $this->getMachineChannelList($where, 0, 'm_id,machine_id, 
        (SELECT machine_name FROM machine m WHERE m.m_id = a.m_id) machine_name ,
        count(mc_id) bad_num', '', '', 'm_id');
            if ($list) {
                foreach ($list as $key => $value) {
                    $value['total_channel'] = $this->getMachineChannelCount(['machine_id' => $value['machine_id']]);
                    $badList = $this->getMachineChannelColumn(['machine_id' => $value['machine_id'], 'status' => 3], 'channel_code');
                    $value['bad_channel'] = implode(",", $badList ?? []);
                    $value['bad_ratio'] = bcmul(bcdiv($value['bad_num'], $value['total_channel'], 3), 100, 1) . "%";
                }
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
        $list = [];
        $machineIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']],"machine_id");
        if ($machineIds) {
            $where[] = ['machine_id', 'in', $machineIds];
            $where['stock'] = 0;
            $list = $this->getMachineChannelList($where, 0, 'm_id,machine_id, 
        (SELECT machine_name FROM machine m WHERE m.m_id = a.m_id) machine_name ,
        count(mc_id) stock_out_num', '', '', 'm_id');
            if ($list) {
                foreach ($list as $key => $value) {
                    $value['total_channel'] = $this->getMachineChannelCount(['machine_id' => $value['machine_id']]);
                    $stockOutList = $this->getMachineChannelColumn(['machine_id' => $value['machine_id'], 'stock' => 0], 'channel_code');
                    $value['stock_out_channel'] = implode(",", $stockOutList ?? []);
                    $value['stock_out_ratio'] = bcmul(bcdiv($value['stock_out_num'], $value['total_channel'], 3), 100, 1) . "%";
                }
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
        $result = $this->updateMachineChannel($postData);
        if ($result) {
            $mc = $this->getMachineChannelFind(['mc_id' => $postData['mc_id']],'machine_id,mc_id');
            // 发送触发货道更新数据
            $this->sendToMachine(['machine_id' => $mc['machine_id']],'updateMc',['mc_id' => $mc['mc_id']]);
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
        $field = "machine_id,sku,g_name,count(mc_id) channel_num,sum(capacity) capacity,sum(stock) stock,sum(frozen_stock) frozen_stock";
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
                    "frozen_stock" => "预定数量"
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
        $field = "machine_id,channel_code,sku,g_name,capacity,stock,frozen_stock";
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
                    "frozen_stock" => "预定数量"
                ];
                $filename =  "货架铺货计划-" . date("YmdHis");
                return $this->sendToExport("设备管理-设备货架", $filename, $title, $list);
            }
        }
        return $this->r(100,$this->lang("query_fail"));
    }
}