<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:42
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\AppFactory;
use app\AppFactory\Kernel\Support\SubCarMixPolicy;
use app\AppFactory\Kernel\Support\Tree;
use app\AppFactory\Kernel\Traits\Goods\GoodsCategoryTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineChannelTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineGoodsTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyPayeeTrait;
use app\AppFactory\Management\ManagementClient;
use think\facade\Db;

class MachineGoodsClient extends ManagementClient
{
    use MachineGoodsTrait;
    use StrategyPayeeTrait;
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
        $spIds = $this->parsePayeeStrategyIds($postData);
        $check = $this->validatePayeeStrategies($postData, $spIds);
        if ($check !== true) return $check;
        unset($postData['sp_ids']);
        unset($postData['sp_id']);
        $this->startTrans();
        try {
            $mg_id = $this->addMachineGoods($postData);
            if (!$mg_id) throw new \Exception($this->lang('add_fail'));
            $this->syncPayeeStrategies($mg_id, $spIds);
            $mg = $this->getMachineGoodsFind(['mg_id' => $mg_id], 'mg_id,machine_id');
            $this->afterMgInsert($mg);
            $this->commitTrans();
            return $this->r(200, $this->lang("add_success"));
        } catch (\Exception $e) {
            $this->rollbackTrans();
            return $this->rFail($e->getMessage());
        }
    }

    public function updateMg($postData)
    {
        $hasStrategyInput = array_key_exists('sp_ids', $postData) || array_key_exists('sp_id', $postData);
        $spIds = $hasStrategyInput ? $this->parsePayeeStrategyIds($postData) : [];
        $check = $hasStrategyInput ? $this->validatePayeeStrategies($postData, $spIds) : true;
        if ($check !== true) return $check;
        $mgId = intval($postData['mg_id']);
        unset($postData['sp_ids']);
        unset($postData['sp_id']);
        $machineGoodsFields = array_diff(array_keys($postData), ['mg_id', 'lang']);
        $hasMachineGoodsUpdate = !empty($machineGoodsFields);
        $this->startTrans();
        try {
            $result = $hasMachineGoodsUpdate ? $this->updateMachineGoods($postData) : 0;
            if ($hasStrategyInput) $this->syncPayeeStrategies($mgId, $spIds);
            if ($result) $this->afterMgUpdate($mgId);
            $this->commitTrans();
            return $this->r(200, $this->lang('update_success'));
        } catch (\Exception $e) {
            $this->rollbackTrans();
            return $this->rFail($e->getMessage());
        }
    }

    private function parsePayeeStrategyIds($postData)
    {
        if (array_key_exists('sp_ids', $postData)) return SubCarMixPolicy::parsePayeeIds($postData['sp_ids']);
        $spId = intval($postData['sp_id'] ?? 0);
        return $spId > 0 ? [$spId] : [];
    }

    private function validatePayeeStrategies($postData, array $spIds)
    {
        if (!$spIds) return true;
        $mg = [];
        if (!empty($postData['mg_id'])) $mg = obj2arr($this->getMachineGoodsFind(['mg_id' => $postData['mg_id']], 'mg_id,ao_id'));
        $goodsAoId = intval($postData['ao_id'] ?? ($mg['ao_id'] ?? 0));
        if ($goodsAoId <= 0 && !empty($postData['g_id'])) {
            $goodsAoId = intval($this->getGoodsValue(['g_id' => $postData['g_id']], 'ao_id'));
        }
        $strategies = Db::name('strategy_payee')->where('sp_id', 'in', $spIds)->where('status', 1)
            ->field('sp_id,ao_id')->select()->toArray();
        if (count($strategies) !== count($spIds)) return $this->rFail('存在无效或已停用的收款策略');
        foreach ($strategies as $strategy) {
            if (intval($strategy['ao_id'] ?? 0) > 0 && $goodsAoId > 0 && intval($strategy['ao_id']) !== $goodsAoId) {
                return $this->rFail('收款策略与设备商品所属组织不匹配');
            }
        }
        return true;
    }

    private function syncPayeeStrategies($mgId, array $spIds)
    {
        Db::name('machine_goods_payee_strategy')->where('mg_id', intval($mgId))->delete();
        foreach ($spIds as $sort => $spId) {
            Db::name('machine_goods_payee_strategy')->insert([
                'mg_id' => intval($mgId),
                'sp_id' => intval($spId),
                'sort' => intval($sort) + 1,
                'status' => 1,
                'create_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * 将多个设备商品的独立收款策略整体替换为同一有序集合。
     */
    public function updatePayeeStrategiesBatch($postData)
    {
        $mgIds = SubCarMixPolicy::parsePayeeIds($postData['mg_ids'] ?? []);
        $spIds = SubCarMixPolicy::parsePayeeIds($postData['sp_ids'] ?? []);
        if (!$mgIds) return $this->rValidate('设备商品ID列表不能为空');
        if (count($mgIds) > 500) return $this->rValidate('单次最多配置500个设备商品');
        if (count($spIds) > 50) return $this->rValidate('单个设备商品最多配置50个收款策略');

        $goodsList = Db::name('machine_goods')->where('mg_id', 'in', $mgIds)
            ->field('mg_id,m_id,machine_id,g_id,g_name,ao_id')->select()->toArray();
        if (count($goodsList) !== count($mgIds)) return $this->rFail('部分设备商品不存在或已删除');

        $permittedMachineIds = $this->resolvePermittedMachineIdsForBatch();
        foreach ($goodsList as $goods) {
            if ($permittedMachineIds !== null && !in_array(intval($goods['m_id']), $permittedMachineIds, true)) {
                return $this->rFail('无权配置部分设备商品，请重新选择');
            }
            $check = $this->validatePayeeStrategies($goods, $spIds);
            if ($check !== true) return $check;
        }

        $this->startTrans();
        try {
            foreach ($goodsList as $goods) {
                $mgId = intval($goods['mg_id']);
                $this->syncPayeeStrategies($mgId, $spIds);
            }
            $this->commitTrans();
        } catch (\Exception $e) {
            $this->rollbackTrans();
            return $this->rFail($e->getMessage());
        }

        return $this->r(200, $this->lang('action_success'), [
            'updated_count' => count($goodsList),
            'mg_ids' => $mgIds,
            'sp_ids' => $spIds,
        ]);
    }

    /**
     * 返回当前后台账号允许管理的设备ID；null表示不限制。
     */
    private function resolvePermittedMachineIdsForBatch()
    {
        if (intval($this->manager['pid'] ?? 0) <= 0) return null;
        $managerId = intval($this->manager['manager_id'] ?? 0);
        $authorized = Db::name('auth_manager_machine')->where('manager_id', $managerId)->column('m_id');
        $created = Db::name('machine')->where('creator', $managerId)->column('m_id');
        return array_values(array_unique(array_map('intval', array_merge($authorized ?: [], $created ?: []))));
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
        $this->startTrans();
        try {
            Db::name('machine_goods_payee_strategy')->where('mg_id', intval($postData['mg_id']))->delete();
            $result = $this->delMachineGoods($postData);
            if (!$result) throw new \Exception($this->lang('del_fail'));
            $this->commitTrans();
            return $this->r(200, $this->lang("del_success"));
        } catch (\Exception $e) {
            $this->rollbackTrans();
            return $this->rFail($e->getMessage());
        }
    }

    public function exportMg($where, $hasCostPriceAuth = true)
    {
        $costPriceField = $hasCostPriceAuth ? 'cost_price' : '0 cost_price';
        $field = 'pic,g_name,sku,' . $costPriceField . ',market_price,retail_price,
        (CASE is_shelf WHEN 1 THEN "已上架" ELSE "未上架" END) is_shelf, available_stock,disabled_stock,reserve_stock,standby_stock';
        $list = $this->getMachineGoodsList($where, 0, $field);
        if ($list) {
            $list = $list->toArray();
            $title = [
                "g_name" => "商品名称",
                "sku" => "SKU",
                "market_price" => "市场价",
                "retail_price" => "售卖价",
                "is_shelf" => "已上架",
                "available_stock" => "可用库存",
                "disabled_stock" => "不可用库存",
                "reserve_stock" => "预定量",
                "standby_stock" => "备用库存",
            ];
            if ($hasCostPriceAuth) {
                $title['cost_price'] = "成本价";
                // $title = array_merge(
                //     array_slice($title, 0, 2, true),
                //     ["cost_price" => "成本价"],
                //     array_slice($title, 2, null, true)
                // );
            }
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
