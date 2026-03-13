<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:42
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\AppFactory;
use app\AppFactory\Kernel\Support\Tree;
use app\AppFactory\Kernel\Traits\Goods\GoodsCategoryTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineChannelTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineGoodsTrait;
use app\AppFactory\Management\ManagementClient;

class MachineGoodsClient extends ManagementClient
{
    use MachineGoodsTrait;
    use MachineChannelTrait;
    use GoodsCategoryTrait,GoodsTrait;

    public function getMgList($where, $pageNum = 0, $field = "*", $order = "")
    {
        $data = $this->getMachineGoodsList($where, $pageNum, $field, $order);
        if ($pageNum) {
            $isChanged = false;
            $data = $data->each(function ($item) use (&$isChanged) {
                if ($item['is_shelf'] == 2) {
                    $mc = $this->getMachineChannelFind(['g_id' => $item['g_id'],'m_id' => $item['m_id']]);
                    if ($mc) {
                        $item['is_shelf'] = 1;
                        $this->updateMachineGoods(['mg_id' => $item['mg_id'], 'is_shelf' => 1]);
                        $isChanged = true;
                    }
                }
                if ($item['is_shelf'] == 1) {
                    $mc = $this->getMachineChannelFind(['g_id' => $item['g_id'],'m_id' => $item['m_id']]);
                    if (!$mc) {
                        $item['is_shelf'] = 2;
                        $this->updateMachineGoods(['mg_id' => $item['mg_id'], 'is_shelf' => 2]);
                        $isChanged = true;
                    }
                }
                return $item;
            });
            //修复is_shelf状态异常导致的前端展示问题
            if ($isChanged) {
                $data = $this->getMachineGoodsList($where, $pageNum, $field, $order);
            }
        }
        return $this->r(200, $this->lang("query_success"), $data);
    }

    public function getGcList($where)
    {
        $tree = [];
        $data = $this->getMachineGoodsColumn($where, "gc_id");
        if ($data) {
            $tree = $this->buildGcTree($data);
        }
        return $this->r(200, $this->lang("query_success"), $tree);
    }

    protected function buildGcTree($gcIds)
    {
        $tree = [];
        $packData = [];
        foreach ($gcIds as $k => $v) {
            $gc = $this->getGoodsCategoryFind(['gc_id' => $v], "gc_id,gc_name,sort gc_sort,gc_pid");
            if ($gc) {
                $gc = $gc->toArray();
                $packData[] = $gc;
                if ($gc['gc_pid']) {
                    $packData = array_merge($packData, $this->getGcParent($gc['gc_pid'], 'gc_id,gc_name,sort gc_sort,gc_pid'));
                }
            }
        }
        if ($packData) {
            $tree = Tree::generateTree($packData, 'gc_id', 'gc_pid');
        }
        return $tree;
    }


    public function addMg($postData)
    {
        $mg_id = $this->addMachineGoods($postData);
        if ($mg_id) {
            $mg = $this->getMachineGoodsFind(['mg_id' => $mg_id], 'mg_id,machine_id');
            $this->afterMgInsert($mg);
            return $this->r(200, $this->lang("add_success"));
        }
        return $this->r(100, $this->lang("add_fail"));
    }

    public function updateMg($postData)
    {
        $result = $this->updateMachineGoods($postData);
        if ($result) {
            $mg_id = $this->getMachineGoodsValue($postData, 'mg_id');
            $this->afterMgUpdate($mg_id);
        }
        return $this->rU($result);
    }

    /**
     * 根据条件修改设备商品信息
     * @param $postData
     * @return array|string
     */
    public function updateByWhere($postData)
    {
        if (isset($postData['where']['g_id'])) $where["g_id"] = $postData['where']['g_id'];
        if (isset($postData['where']['m_id'])) $where[] = ['m_id',"in",$postData['where']['m_id']];
        $result = $this->updateMachineGoods($postData['update'], $where);
        if ($result) {
            $mgList = $this->getMachineGoodsList($where, 0, 'mg_id');
            if ($mgList) {
                $mgList = $mgList->toArray();
                foreach ($mgList as $mgk => $mgv) {
                    $this->afterMgUpdate($mgv['mg_id']);
                }
            }
            return $this->r(200, $this->lang("action_success"));
        }
        return $this->r(100, $this->lang("action_fail"));
    }

    public function delMg($postData)
    {
        $result = $this->delMachineGoods($postData);
        if ($result) {
            return $this->r(200, $this->lang("del_success"));
        }
        return $this->r(100, $this->lang("del_fail"));
    }

    public function exportMg($where)
    {
        $list = $this->getMachineGoodsList($where, 0, 'pic,g_name,sku,cost_price,market_price,retail_price,
        (CASE is_shelf WHEN 1 THEN "已上架" ELSE "未上架" END) is_shelf, available_stock,disabled_stock,reserve_stock,standby_stock');
        if ($list) {
            $list = $list->toArray();
            $title = [
                "g_name" => "商品名称",
                "sku" => "SKU",
                "cost_price" => "成本价",
                "market_price" => "市场价",
                "retail_price" => "售卖价",
                "is_shelf" => "已上架",
                "available_stock" => "可用库存",
                "disabled_stock" => "不可用库存",
                "reserve_stock" => "预定量",
                "standby_stock" => "备用库存",
            ];
            $filename = "设备商品-" . date("Ymd");
            return $this->sendToExport("设备列表-设备商品", $filename, $title, $list);
        }
        return $this->r(100, $this->lang("query_fail"));
    }

    /**
     * 设备商品库同步商品库价格
     * @param $postData
     * @return array|\think\response\Json
     */
    public function synchronizationGoodsPrice($postData)
    {
        $where = [];
        if (isset($postData['mg_id']) && $postData['mg_id']) $where['mg_id'] = $postData['mg_id'];
        if (isset($postData['m_id']) && $postData['m_id'])  $where['m_id'] = $postData['m_id'];

        $mg = $this->getMachineGoodsList($where,0,'mg_id,g_id,cost_price,market_price,retail_price');
        if (!$mg) return $this->r(100,$this->lang("VMachineGoods.mg_id_require"));
        $mg = $mg->toArray();
        if ($mg) {
            foreach ($mg as $key => $value) {
                $goods = $this->getGoodsFind(['g_id' => $value['g_id']], 'cost_price,market_price,retail_price');
                if (!$goods) continue;
                $goods = $goods->toArray();
                if ($value['cost_price'] != $goods['cost_price'] || $value['market_price'] != $goods['market_price'] || $value['retail_price'] != $goods['retail_price']) {
                    $value = array_merge($value, $goods);
                    $result = $this->updateMachineGoods($value);
                    if ($result) {
                        $this->afterMgUpdate($value['mg_id']);
                    }
                }
            }
        }
        return $this->r(200, $this->lang("action_success"));
    }
}