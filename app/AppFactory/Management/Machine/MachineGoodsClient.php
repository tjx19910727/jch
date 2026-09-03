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
use app\AppFactory\Kernel\Service\Currency\GoodsCurrencyPriceService;
use app\AppFactory\Kernel\Service\Currency\MachineCurrencyAccessService;
use app\AppFactory\Kernel\Service\Currency\MachineCurrencyPriceService;
use app\AppFactory\Kernel\Support\Currency\CurrencyPriceSupport;
use app\AppFactory\Kernel\Traits\Goods\GoodsCategoryTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineChannelTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineGoodsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Management\ManagementClient;

class MachineGoodsClient extends ManagementClient
{
    use MachineGoodsTrait;
    use MachineChannelTrait;
    use MachineTrait;
    use GoodsCategoryTrait,GoodsTrait;

    public function getMgList($where, $pageNum = 0, $field = "*", $order = "", $currencyCode = '', $hasCostPriceAuth = true)
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
        $data = $this->appendCurrencyPrice($data, $currencyCode, $hasCostPriceAuth);
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
        try {
            (new MachineCurrencyAccessService())->assertManagementAccess($postData['m_id'], $this->manager);
            $currencyCode = $this->resolveCurrencyCode(intval($postData['m_id']), isset($postData['currency_code']) ? $postData['currency_code'] : '');
            unset($postData['currency_code']);
            $this->startTrans();
            $mg_id = $this->addMachineGoods($postData);
            if (!$mg_id) {
                throw new \RuntimeException($this->lang('add_fail'));
            }
            $sync = (new MachineCurrencyPriceService())->syncMachineGoods(
                intval($postData['m_id']),
                $currencyCode,
                [$mg_id],
                intval($this->manager['manager_id'] ?? 0),
                false
            );
            $this->commitTrans();
            $mg = $this->getMachineGoodsFind(['mg_id' => $mg_id], 'mg_id,machine_id');
            $this->afterMgInsert($mg);
            $this->notifyCurrencySnapshot($sync);
            return $this->r(200, $this->lang("add_success"), ['mg_id' => $mg_id, 'currency' => $sync]);
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1, 'addMachineGoodsCurrency');
            return $this->rValidate($e->getMessage());
        }
    }

    public function getMgFindCurrency($where, $field = '*', $currencyCode = '', $hasCostPriceAuth = true)
    {
        try {
            $item = $this->getMachineGoodsFind($where, $field);
            if (!$item) return $this->rNoData();
            $currencyCode = $this->resolveCurrencyCode(intval($item['m_id']), $currencyCode);
            $machineMap = (new MachineCurrencyPriceService())->getMachineGoodsPriceMap([intval($item['mg_id'])], $currencyCode);
            $goodsRows = (new GoodsCurrencyPriceService())->getPriceMapByGoodsIds([intval($item['g_id'])]);
            $machinePrice = isset($machineMap[intval($item['mg_id'])]) ? $machineMap[intval($item['mg_id'])] : null;
            $machineConfigured = $machinePrice !== null;
            // 存量 CNY 未落事实行：回退该行活跃快照作为人民币价并按已配置处理。
            if (!$machineConfigured && $currencyCode === 'CNY') {
                $activeTriple = $this->activeRowTriple($item);
                if ($activeTriple !== null) {
                    $machinePrice = $activeTriple;
                    $machineConfigured = true;
                }
            }
            if (!$machineConfigured) $machinePrice = $this->emptyCurrencyPrice();
            $goodsPrice = null;
            foreach (isset($goodsRows[intval($item['g_id'])]) ? $goodsRows[intval($item['g_id'])] : [] as $price) {
                if ($price['currency_code'] === $currencyCode) $goodsPrice = $price;
            }
            if ($goodsPrice === null) {
                $goodsPrice = ($currencyCode === 'CNY') ? $this->activeRowTriple($item) : $this->emptyCurrencyPrice();
                if ($goodsPrice === null) $goodsPrice = $this->emptyCurrencyPrice();
            }
            if (!$hasCostPriceAuth) {
                if ($machinePrice) $machinePrice['cost_price'] = '';
                if ($goodsPrice) $goodsPrice['cost_price'] = '';
            }
            $item['target_currency_code'] = $currencyCode;
            $item['currency_price'] = $machinePrice;
            $item['goods_currency_price'] = $goodsPrice;
            return $this->rQ($item);
        } catch (\Exception $e) {
            return $this->rValidate($e->getMessage());
        }
    }

    /**
     * 该币种价格行缺失时返回默认占位三价（0），保证页面可正常加载与编辑提交。
     * @return array
     */
    protected function emptyCurrencyPrice()
    {
        return ['cost_price' => '0.000', 'market_price' => '0.000', 'retail_price' => '0.000'];
    }

    /**
     * 取行内活跃快照三价（CNY 存量未落事实行时回退）。
     * @param array $row
     * @return array|null
     */
    protected function activeRowTriple(array $row)
    {
        if (!array_key_exists('cost_price', $row) && !array_key_exists('retail_price', $row)) {
            return null;
        }
        return [
            'cost_price' => array_key_exists('cost_price', $row) ? $row['cost_price'] : '0.000',
            'market_price' => array_key_exists('market_price', $row) ? $row['market_price'] : '0.000',
            'retail_price' => array_key_exists('retail_price', $row) ? $row['retail_price'] : '0.000',
        ];
    }

    public function updateMg($postData)
    {
        // 设备商品改价走普通编辑，仅当前币种有效：三价按设备当前币种保存（事实表 + 活跃快照 + 版本一次）。
        $mgId = intval($this->getMachineGoodsValue($postData, 'mg_id'));
        if ($mgId <= 0) {
            return $this->r(100, $this->lang('VMachineGoods.mg_id_require'));
        }
        $priceInput = [];
        $hasPrice = false;
        foreach (CurrencyPriceSupport::PRICE_FIELDS as $field) {
            if (array_key_exists($field, $postData)) {
                $priceInput[$field] = $postData[$field];
                $hasPrice = true;
                unset($postData[$field]);
            }
        }
        try {
            \think\facade\Db::startTrans();
            // 只改价时剥离后只剩主键，避免空字段更新报错。
            $basicKeys = array_diff(array_keys($postData), ['mg_id', 'm_id', 'machine_id']);
            $result = true;
            if ($basicKeys) {
                $result = $this->updateMachineGoods($postData);
                if (!$result) {
                    throw new \RuntimeException('更新设备商品失败');
                }
            }
            $priceResult = null;
            if ($hasPrice) {
                $priceService = new MachineCurrencyPriceService();
                $mId = intval($this->getMachineGoodsValue(['mg_id' => $mgId], 'm_id'));
                $priceResult = $priceService->saveMachineGoodsPrice(
                    $mId,
                    $mgId,
                    $priceService->getMachineCurrency($mId)['currency_code'],
                    $priceInput,
                    intval($this->manager['manager_id'] ?? 0)
                );
            }
            \think\facade\Db::commit();
        } catch (\Exception $e) {
            \think\facade\Db::rollback();
            actionException($e, 1, 'updateMgCurrencyPrice');
            return $this->rValidate($e->getMessage());
        }
        $this->afterMgUpdate($mgId);
        if ($priceResult) {
            $this->notifyCurrencySnapshot($priceResult);
        }
        return $this->rU(true);
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

    protected function appendCurrencyPrice($data, $currencyCode, $hasCostPriceAuth)
    {
        if (!$data) {
            return $data;
        }
        $rows = [];
        foreach ($data as $item) {
            $rows[] = $item;
        }
        if (!$rows) {
            return $data;
        }
        $mgIds = array_map(function ($item) { return intval($item['mg_id']); }, $rows);
        $gIds = array_map(function ($item) { return intval($item['g_id']); }, $rows);
        $targetCodeMap = [];
        $idsByCode = [];
        foreach ($rows as $row) {
            $targetCode = $this->resolveCurrencyCode(intval($row['m_id']), $currencyCode);
            $targetCodeMap[intval($row['mg_id'])] = $targetCode;
            $idsByCode[$targetCode][] = intval($row['mg_id']);
        }
        $machineMap = [];
        $priceService = new MachineCurrencyPriceService();
        foreach ($idsByCode as $targetCode => $ids) {
            $machineMap += $priceService->getMachineGoodsPriceMap($ids, $targetCode);
        }
        $goodsRows = (new GoodsCurrencyPriceService())->getPriceMapByGoodsIds($gIds);
        return $data->each(function ($item) use ($targetCodeMap, $machineMap, $goodsRows, $hasCostPriceAuth) {
            $currencyCode = $targetCodeMap[intval($item['mg_id'])];
            $machinePrice = $machineMap[intval($item['mg_id'])] ?? null;
            $machineConfigured = $machinePrice !== null;
            // 存量 CNY 未落事实行：回退该行活跃快照作为人民币价并按已配置处理。
            if (!$machineConfigured && $currencyCode === 'CNY') {
                $activeTriple = $this->activeRowTriple($item);
                if ($activeTriple !== null) {
                    $machinePrice = $activeTriple;
                    $machineConfigured = true;
                }
            }
            if (!$machineConfigured) $machinePrice = $this->emptyCurrencyPrice();
            $goodsPrice = null;
            foreach (isset($goodsRows[intval($item['g_id'])]) ? $goodsRows[intval($item['g_id'])] : [] as $price) {
                if ($price['currency_code'] === $currencyCode) $goodsPrice = $price;
            }
            if ($goodsPrice === null) {
                $goodsPrice = ($currencyCode === 'CNY') ? $this->activeRowTriple($item) : $this->emptyCurrencyPrice();
                if ($goodsPrice === null) $goodsPrice = $this->emptyCurrencyPrice();
            }
            if (!$hasCostPriceAuth) {
                if ($machinePrice) $machinePrice['cost_price'] = '';
                if ($goodsPrice) $goodsPrice['cost_price'] = '';
            }
            $item['target_currency_code'] = $currencyCode;
            $item['currency_price'] = $machinePrice;
            $item['goods_currency_price'] = $goodsPrice;
            $item['price_diff_status'] = !$machineConfigured ? 0 : ($goodsPrice && CurrencyPriceSupport::pricesEqual($machinePrice, $goodsPrice) ? 1 : 2);
            return $item;
        });
    }

    /**
     * 按设备当前币种自动同步：把核心商品当前币种三价同步到选中的设备商品及其在本机的普通货道。
     * 请求参数：m_id + mg_ids[]（不再接收币种参数，币种以 machine_config.currency_code 为准，空则按 CNY）。
     * @param array $postData
     * @return array|\think\response\Json
     */
    public function synchronizationGoodsPrice($postData)
    {
        try {
            $mId = intval($postData['m_id'] ?? 0);
            if ($mId <= 0) {
                throw new \InvalidArgumentException($this->lang('VMachineGoods.m_id_require'));
            }
            (new MachineCurrencyAccessService())->assertManagementAccess($mId, $this->manager);
            $result = (new MachineCurrencyPriceService())->syncMachineGoodsChannelsByDeviceCurrency(
                $mId,
                isset($postData['mg_ids']) ? $postData['mg_ids'] : ($postData['mg_id'] ?? []),
                intval($this->manager['manager_id'] ?? 0)
            );
            $this->notifyCurrencySnapshot($result);
            return $this->r(200, $this->lang('action_success'), $result);
        } catch (\Exception $e) {
            actionException($e, 1, 'syncMachineGoodsCurrency');
            return $this->rValidate($e->getMessage());
        }
    }
    protected function resolveCurrencyCode($mId, $currencyCode)
    {
        if ($currencyCode) {
            return CurrencyPriceSupport::normalizeCurrencyCode($currencyCode);
        }
        return (new MachineCurrencyPriceService())->getMachineCurrency($mId)['currency_code'];
    }

    protected function notifyCurrencySnapshot(array $result)
    {
        // 预配置非当前币种不会改变售卖快照，因此无需通知设备刷新。
        if (intval($result['active_snapshot_changed'] ?? 0) !== 1) {
            return;
        }
        $this->sendToMachine(
            ['machine_id' => $result['machine_id']],
            'currencySnapshotUpdated',
            ['currency_code' => $result['active_currency_code'], 'currency_version' => intval($result['currency_version'])]
        );
    }
}
