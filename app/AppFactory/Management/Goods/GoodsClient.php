<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/20
 * Time: 10:56
 */

namespace app\AppFactory\Management\Goods;


use app\AppFactory\Kernel\Support\Excel;
use app\AppFactory\Kernel\Traits\Auth\AuthManagerMachineTrait;
use app\AppFactory\Kernel\Traits\Auth\AuthManagerTrait;
use app\AppFactory\Kernel\Model\Goods\GoodsModel;
use app\AppFactory\Kernel\Service\Currency\GoodsCurrencyPriceService;
use app\AppFactory\Kernel\Support\Currency\CurrencyPriceSupport;
use app\AppFactory\Kernel\Traits\Goods\GoodsLangTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineChannelTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineGoodsTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersGoodsCountTrait;
use app\AppFactory\Management\ManagementClient;
use app\management\validate\VGoods;
use think\facade\Db;

class GoodsClient extends ManagementClient
{
    use GoodsTrait, GoodsLangTrait;
    use AuthManagerTrait;
    use SaleOrdersGoodsCountTrait;
    use MachineTrait,MachineChannelTrait,MachineGoodsTrait;

    protected $priceFields = ['cost_price', 'market_price', 'retail_price'];

    public function addG($postData)
    {
        unset($postData['stocks'], $postData['locked_stocks'], $postData['available_stocks']);
        try {
            $priceService = new GoodsCurrencyPriceService();
            $currencyPrices = $this->extractCurrencyPrices($postData, true);
            $cnyPrice = CurrencyPriceSupport::normalizePriceRow($currencyPrices['CNY']);
            $postData = array_merge($postData, $cnyPrice);
            Db::startTrans();
            $g_id = $this->addGoods($postData);
            if (!$g_id) {
                throw new \RuntimeException('新增商品失败');
            }
            $insertLang = [
                "g_id" => $g_id,
                "g_name" => $postData['g_name'] ?? "",
                "gc_id" => $postData['gc_id'] ?? "",
                "gc_name" => $postData['gc_name'] ?? "",
                "manufacturer" => $postData['manufacturer'] ?? "",
                "desc" => $postData['desc'] ?? "",
                "performance" => $postData['performance'] ?? "",
                "lang" => "zh-cn",
            ];
            $this->addGoodsLang($insertLang);
            $priceService->savePrices($g_id, $currencyPrices, intval($this->manager['manager_id'] ?? 0), true, false);
            Db::commit();
            return $this->rA($g_id);
        } catch (\Exception $e) {
            Db::rollback();
            actionException($e, 1, 'addGoodsCurrencyPrice');
            return $this->rValidate($e->getMessage());
        }
    }

    public function getListWithCurrencyPrices($where, $pageNum, $field, $order, $hasCostPriceAuth = true)
    {
        $data = $this->getGoodsList($where, $pageNum, $field, $order);
        return $this->rQ($this->appendCurrencyPrices($data, $hasCostPriceAuth));
    }

    public function getFindWithCurrencyPrices($where, $field, $hasCostPriceAuth = true)
    {
        $data = $this->getGoodsFind($where, $field);
        if (!$data) {
            return $this->rQ($data);
        }
        $gId = intval($data['g_id']);
        $prices = (new GoodsCurrencyPriceService())->getPrices($gId);
        if (!$hasCostPriceAuth) {
            foreach ($prices as &$price) {
                $price['cost_price'] = '';
            }
            unset($price);
        }
        $data['currency_prices'] = $prices;
        return $this->rQ($data);
    }

    public function getPageList($where, $pageNum = 0, $field = "*", $order = "")
    {
        $data = $this->getGoodsList($where, $pageNum, $field, $order);
        return $this->rQ($data);
    }

    /**
     * 概览——商品前10排行榜
     * @param $where
     * @return array|string
     */
    public function get10List($where)
    {
        if($this->manager['account']=='meichitu'){
            $where[] = ['gc_name','like','%美驰图%'];
        }
        // 非超管账号按绑定设备过滤（与 getData / getDataV2 一致）；无绑定设备时返回空数据
        if ($this->manager['pid'] > 0) {
            $mIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], "m_id");
            if ($mIds) {
                $where[] = ['m_id', 'in', $mIds];
            } else {
                $where[] = ['m_id', '=', 0];
            }
        }
        $list = $this->queryGoodsRanking($where, 1, 0, 10);
        if ($list) {
            $list = $this->formatGoodsRankingList($list)->toArray();
            $fields = array_flip(['g_id', 'g_name', 'totalPrice', 'totalQuantity', 'retail_price', 'pic']);
            foreach ($list as &$item) {
                $item = array_intersect_key($item, $fields);
            }
            unset($item);
        }
        return $this->rQ($list);
    }

    public function getAuthList($where,$pageNum,$field,$order,$input,$hasCostPriceAuth = true){
        $whereG = [];
        if(!empty($input['machine_id'])) $whereG[] = ['machine_id','in',$input['machine_id']];
        if($input['sale_check']){
            $mIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], "m_id");
            $createMIds = $this->getMachineColumn(['creator' => $this->manager['manager_id']],'m_id');
            if ($createMIds && $mIds) $mIds = array_unique(array_merge($mIds,$createMIds));
            $whereG[] = ['m_id', 'in', $mIds];
        }
        $gIds = $this->getMachineChannelList($whereG,0,'g_id');
        $gIds = $gIds->toArray();
        $g_id = [];
        foreach($gIds as $item){
            array_push($g_id,$item['g_id']);
        }
        
        $where[] = ['g_id','in',$g_id];
        $result = $this->getListWithCurrencyPrices($where,$pageNum,$field,'g_id desc',$hasCostPriceAuth);
        return $result;
    }

    /**
     * 商品编辑只维护核心商品与核心币种价，设备商品/货道必须走人工同步接口。
     * @param array $postData
     * @return mixed
     */
    public function updateForEdit($postData)
    {
        if (array_key_exists('stocks', $postData) || array_key_exists('locked_stocks', $postData) || array_key_exists('available_stocks', $postData)) {
            return $this->r(100, '商品库存不允许通过商品编辑接口修改');
        }
        $gId = $postData['g_id'] ?? 0;
        if (!$gId) {
            return $this->r(100, '参数有误');
        }

        $oldGoods = $this->getGoodsFind(['g_id' => $gId], 'g_id');
        if (!$oldGoods) {
            return $this->r(100, '商品不存在');
        }
        $selectedMgIds = $this->parseIds($postData['mg_id'] ?? []);
        $selectedMcIds = $this->parseIds($postData['mc_id'] ?? []);
        unset($postData['mg_id'], $postData['mc_id']);
        if ($selectedMgIds || $selectedMcIds) {
            return $this->r(100, '商品编辑不再隐式覆盖设备商品或货道价格，请使用对应的一键同步接口');
        }
        try {
            $currencyPrices = $this->extractCurrencyPrices($postData, false);
            foreach (array_merge($this->priceFields, ['cny_cost_price', 'cny_market_price', 'cny_retail_price', 'hkd_cost_price', 'hkd_market_price', 'hkd_retail_price']) as $field) {
                unset($postData[$field]);
            }
            Db::startTrans();
            $result = true;
            $updateData = $postData;
            unset($updateData['g_id']);
            if ($updateData) {
                $result = $this->updateGoods($updateData, ['g_id' => $gId]);
                if (!$result) {
                    throw new \RuntimeException('更新商品失败');
                }
            }
            if ($currencyPrices) {
                (new GoodsCurrencyPriceService())->savePrices($gId, $currencyPrices, intval($this->manager['manager_id'] ?? 0), false, false);
            }
            Db::commit();
            $this->pushGoodsUpdate($gId);
            return $this->r(200, 'success', $result);
        } catch (\Exception $e) {
            Db::rollback();
            actionException($e, 1, 'updateGoodsCurrencyPrice');
            return $this->rValidate($e->getMessage());
        }
    }

    /**
     * 查询与最新输入价格不同的设备商品、货道列表
     * @param array $postData
     * @return array|\think\response\Json
     */
    public function getPriceDiff($postData)
    {
        $gId = $postData['g_id'] ?? 0;
        if (!$gId) {
            return $this->rFail($this->lang('VGoods.g_id_require'));
        }

        try {
            $currencyCode = CurrencyPriceSupport::normalizeCurrencyCode($postData['currency_code'] ?? 'CNY');
        } catch (\InvalidArgumentException $e) {
            return $this->rValidate($e->getMessage());
        }
        $priceService = new GoodsCurrencyPriceService();
        $prices = $priceService->getPrices($gId);
        $goodsPrice = null;
        foreach ($prices as $price) {
            if ($price['currency_code'] === $currencyCode) {
                $goodsPrice = $price;
                break;
            }
        }
        if (!$goodsPrice) {
            return $this->rFail($this->lang('goods_no_data'));
        }
        $latestCost = $postData['cost_price'] ?? $goodsPrice['cost_price'];
        $latestMarket = $postData['market_price'] ?? $goodsPrice['market_price'];
        $latestRetail = $postData['retail_price'] ?? $goodsPrice['retail_price'];

        $mgDiff = Db::name('machine_goods')->alias('mg')
            ->join('machine m', 'm.m_id = mg.m_id')
            ->leftJoin('machine_goods_currency_price p', 'p.mg_id=mg.mg_id AND p.currency_code="' . $currencyCode . '"')
            ->where('mg.g_id', $gId)
            ->where(function ($query) use ($latestCost, $latestMarket, $latestRetail) {
                $query->whereNull('p.mgcp_id')->whereOr('p.cost_price', '<>', $latestCost)
                    ->whereOr('p.market_price', '<>', $latestMarket)->whereOr('p.retail_price', '<>', $latestRetail);
            })
            ->field('mg.mg_id,mg.m_id,mg.machine_id,m.machine_name,mg.g_id,mg.g_name,p.cost_price,p.market_price,p.retail_price,p.updated_at')
            ->order('mg.mg_id desc')
            ->limit(200)
            ->select()
            ->toArray();

        foreach ($mgDiff as $key => $item) {
            if ($latestRetail > $item['retail_price']) {
                $mgDiff[$key]['goods_status'] = 1;
            } elseif ($latestRetail < $item['retail_price']) {
                $mgDiff[$key]['goods_status'] = 2;
            } else {
                $mgDiff[$key]['goods_status'] = 3;
            }
        }

        $mcDiff = Db::name('machine_channel')->alias('mc')
            ->join('machine m', 'm.m_id = mc.m_id')
            ->leftJoin('machine_channel_currency_price p', 'p.mc_id=mc.mc_id AND p.currency_code="' . $currencyCode . '"')
            ->where('mc.g_id', $gId)
            ->where(function ($query) use ($latestCost, $latestMarket, $latestRetail) {
                $query->whereNull('p.mccp_id')->whereOr('p.cost_price', '<>', $latestCost)
                    ->whereOr('p.market_price', '<>', $latestMarket)->whereOr('p.retail_price', '<>', $latestRetail);
            })
            ->field('mc.mc_id,mc.m_id,mc.machine_id,m.machine_name,mc.channel_code,mc.g_id,mc.g_name,p.cost_price,p.market_price,p.retail_price,p.updated_at,mc.update_price as update_status')
            ->order('mc.mc_id desc')
            ->limit(200)
            ->select()
            ->toArray();

        foreach ($mcDiff as $key => $item) {
            if ($latestRetail > $item['retail_price']) {
                $mcDiff[$key]['goods_status'] = 1;
            } elseif ($latestRetail < $item['retail_price']) {
                $mcDiff[$key]['goods_status'] = 2;
            } else {
                $mcDiff[$key]['goods_status'] = 3;
            }
        }

        return $this->r(200, 'success', [
            'currency_code' => $currencyCode,
            'mg_diff_list' => $mgDiff,
            'mc_diff_list' => $mcDiff,
            'mg_diff_count' => count($mgDiff),
            'mc_diff_count' => count($mcDiff),
        ]);
    }

    protected function parseIds($ids)
    {
        if (is_array($ids)) {
            $idList = $ids;
        } else {
            $idList = explode(',', (string)$ids);
        }
        $idList = array_map('trim', $idList);
        $idList = array_filter($idList, function ($item) {
            return $item !== '';
        });
        return array_values(array_unique($idList));
    }

    protected function appendCurrencyPrices($data, $hasCostPriceAuth)
    {
        if (!$data) {
            return $data;
        }
        $gIds = [];
        foreach ($data as $item) {
            $gIds[] = intval($item['g_id']);
        }
        $priceMap = (new GoodsCurrencyPriceService())->getPriceMapByGoodsIds($gIds);
        return $data->each(function ($item) use ($priceMap, $hasCostPriceAuth) {
            $prices = isset($priceMap[intval($item['g_id'])]) ? $priceMap[intval($item['g_id'])] : [];
            if (!$hasCostPriceAuth) {
                foreach ($prices as &$price) {
                    $price['cost_price'] = '';
                }
                unset($price);
            }
            $item['currency_prices'] = $prices;
            return $item;
        });
    }

    /**
     * currency_prices 承载任意启用币种；CNY/HKD 平面字段仅用于兼容现有表单和 Excel 模板。
     */
    protected function extractCurrencyPrices(array &$postData, $requireCny)
    {
        $prices = [];
        if (isset($postData['currency_prices'])) {
            $prices = (new GoodsCurrencyPriceService())->normalizePriceCollection($postData['currency_prices']);
            unset($postData['currency_prices']);
        }
        $flatCny = [];
        foreach ($this->priceFields as $field) {
            if (array_key_exists($field, $postData)) {
                $flatCny[$field] = $postData[$field];
            }
            $named = 'cny_' . $field;
            if (array_key_exists($named, $postData) && $postData[$named] !== '') {
                $flatCny[$field] = $postData[$named];
            }
        }
        if ($flatCny) {
            $prices['CNY'] = array_merge($flatCny, isset($prices['CNY']) ? $prices['CNY'] : []);
        }
        $flatHkd = [];
        foreach ($this->priceFields as $field) {
            $named = 'hkd_' . $field;
            if (array_key_exists($named, $postData) && $postData[$named] !== '') {
                $flatHkd[$field] = $postData[$named];
            }
        }
        if ($flatHkd) {
            $prices['HKD'] = array_merge($flatHkd, isset($prices['HKD']) ? $prices['HKD'] : []);
        }
        foreach (['cny_cost_price', 'cny_market_price', 'cny_retail_price', 'hkd_cost_price', 'hkd_market_price', 'hkd_retail_price'] as $field) {
            unset($postData[$field]);
        }
        if ($requireCny && !isset($prices['CNY'])) {
            throw new \InvalidArgumentException('新商品必须配置CNY三价');
        }
        return $prices;
    }

    /**
     * 商品价格覆盖后，将受影响的设备商品和货道放入旧商品同步队列。
     *
     * @param int $gId
     * @param array $mgIds
     * @param array $mcIds
     * @return bool
     */
    protected function pushGoodsUpdateV2($gId, $mgIds, $mcIds)
    {
        $redis = null;
        try {
            $task = json_encode([
                'version' => 2,
                'type' => 'goods_price_update',
                'g_id' => (int)$gId,
                'mg_ids' => array_values($mgIds),
                'mc_ids' => array_values($mcIds),
                'request_time' => date('Y-m-d H:i:s'),
                'manager_id' => $this->manager['manager_id'] ?? 0,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($task === false) {
                throw new \RuntimeException('Failed to encode goods update V2 task');
            }

            $redis = new \Redis();
            $config = config('redis');
            $redis->connect($config['host'], $config['port'], $config['timeout'], $config['reserved'], $config['retry_interval']);
            if (isset($config['password']) && $config['password']) {
                $redis->auth($config['password']);
            }
            $result = $redis->lPush('updateGoods', $task);
            if ($result === false) {
                throw new \RuntimeException('Failed to push goods update V2 task');
            }
            $redis->expire('updateGoods', 300);
            actionLog($task, '商品价格覆盖写入Redis队列', 'pushGoodsUpdateV2');
            return true;
        } catch (\Throwable $e) {
            actionException($e, 1, 'pushGoodsUpdateV2');
            return false;
        } finally {
            if ($redis) {
                try {
                    $redis->close();
                } catch (\Throwable $e) {
                    actionException($e, 1, 'pushGoodsUpdateV2Close');
                }
            }
        }
    }

    /**
     * 商品信息修改后，将商品ID放入同步队列。
     *
     * @param int $gId
     * @return bool
     */
    protected function pushGoodsUpdate($gId)
    {
        $redis = null;
        try {
            $redis = new \Redis();
            $config = config('redis');
            $redis->connect($config['host'], $config['port'], $config['timeout'], $config['reserved'], $config['retry_interval']);
            if (isset($config['password']) && $config['password']) {
                $redis->auth($config['password']);
            }
            $result = $redis->lPush('updateGoods', (int)$gId);
            if ($result === false) {
                throw new \RuntimeException('Failed to push goods update task');
            }
            $redis->expire('updateGoods', 300);
            actionLog($gId, '修改的商品ID', 'pushGoodsUpdate');
            actionLog($redis->lRange('updateGoods', 0, -1), '放入Redis数据', 'pushGoodsUpdate');
            return true;
        } catch (\Throwable $e) {
            actionException($e, 1, 'pushGoodsUpdate');
            return false;
        } finally {
            if ($redis) {
                try {
                    $redis->close();
                } catch (\Throwable $e) {
                    actionException($e, 1, 'pushGoodsUpdateClose');
                }
            }
        }
    }

    public function exportRankingList($where)
    {
        if($this->manager['account']=='meichitu'){
            $where[] = ['gc_name','like','%美驰图%'];
        }
        $list = $this->queryGoodsRanking($where, 1, 0, 10);
        if ($list) {
            $list = $list->toArray();
            $title = [
                "g_name" => $this->lang("export.g_name"),
                "totalQuantity" => $this->lang("export.totalQuantity"),
                "retail_price" => $this->lang("export.retail_price"),
            ];
            $filename = $this->lang("export.goods10List") . date("Ymd");
            $result = $this->sendToExport($this->lang("export.goodsRankingFileName"), $filename, $title, $list);
            return $result;
        }
        return $this->rQ($list);
    }

    /**
     * 删除商品
     * @param $where
     * @return array|\think\response\Json
     */
    public function delG($where)
    {
        $goods = $this->getGoodsList($where,0,'g_id,pic,banner,details_pic,`desc`');
        if ($goods) {
            $goods = $goods->toArray();
            $delList = [];
            foreach ($goods as $key => $value) {
                if ($value['pic'] && file_exists($value['pic'])) {
                    $delList = array_merge($delList,$value['pic']);
                }
                if ($value['banner']) {
                    $delList = array_merge($delList,explode(";",$value['banner']));
                }
                if ($value['details_pic']) {
                    $delList = array_merge($delList,explode(";",$value['details_pic']));
                }
                if ($value['desc']) {
                    $delList = array_merge($delList,getImagesFromRichText($value['desc']));
                }
            }
            $result = $this->delGoods($where);
            if ($result) {
                foreach ($delList as $v) {
                    if (file_exists($v) && is_file($v)) {
                        @unlink($v);
                    }
                }
                return $this->r(200,$this->lang("del_success"));
            }
        }
        return $this->r(100,$this->lang("del_fail"));
    }

    /**
     * 导入Excel
     * @param $data
     * @return array|string
     */
    public function importExcel($data)
    {
        try {
            $path = root_path() . "public" . $data['file_path'];
            $title = ["g_name", "gc_id", "gc_name", "model", "sku", "sku2", "pic", "bar_code", "cost_price", "market_price", "retail_price", "manufacturer", "service_phone", "status",'length','width','height'];
            $other = ['creator' => $this->manager['manager_id'] ?? 0, 'ao_id' => $this->manager['ao_id'] ?? 0];
            $goods = Excel::importExcel($path, $title, $other, 2, ['pic']);
            if (is_object($goods)) return $goods;
            actionLog($goods, '导入的商品数据');
            if ($goods) {
                foreach ($goods as $key => $value) {
                    try {
                        validate(VGoods::class)->scene("importExcel")->check($value);
                    } catch (\Exception $e) {
                        return $this->rFail($e->getMessage());
                    }
                }
                $result = $this->addMoreGoods($goods);
                return $this->rAction($result);
            }
            return $this->r(100, '获取不到Excel文档中的数据');
        } catch (\Exception $e) {
            actionException($e, 1);
            return $this->rValidate($e->getMessage());
        }
    }

    /**
     * 导入Excel有商品ID则更新，目前只更新（bar_code）
     * @param $data
     * @return array|string
     */
    public function importExcelV2($data, $hasCostPriceAuth = true)
    {
        try {
            $path = root_path() . "public" . $data['file_path'];
            $other = ['creator' => $this->manager['manager_id'] ?? 0, 'ao_id' => $this->manager['ao_id'] ?? 0];
            $goods = Excel::importExcelByHeader($path, $this->getGoodsImportHeaderMap(), $other, 2, ['pic']);
            if (is_object($goods)) return $goods;
            if (!$hasCostPriceAuth) {
                foreach ($goods as $row) {
                    foreach (['cost_price', 'cny_cost_price', 'hkd_cost_price'] as $costField) {
                        if (isset($row[$costField]) && trim((string)$row[$costField]) !== '') {
                            return $this->r(100, '当前账号无成本价权限，请清空人民币/港币成本价列后重试');
                        }
                    }
                }
            }
            actionLog($goods, '导入的商品数据');
            if ($goods) {
                $insertGoods = [];
                $resultData = [
                    'total' => count($goods),
                    'update_success' => 0,
                    'update_fail' => 0,
                    'insert_total' => 0,
                    'insert_success' => 0,
                    'insert_fail' => 0,
                    'insert_fail_list' => [],
                ];
                foreach ($goods as $key => $value) {
                    $value = $this->normalizeGoodsImportRow($value);
                    $gId = intval($value['g_id'] ?? 0);
                    if ($gId > 0) {
                        $goodsFind = $this->getGoodsFind(['g_id' => $gId], 'g_id');
                        if ($goodsFind) {
                            try {
                                $currencyPrices = $this->extractCurrencyPrices($value, false);
                                $update = ['bar_code' => trim(isset($value['bar_code']) ? $value['bar_code'] : '')];
                                Db::startTrans();
                                $this->updateGoods($update, ['g_id' => $gId], ['bar_code']);
                                if ($currencyPrices) {
                                    (new GoodsCurrencyPriceService())->savePrices($gId, $currencyPrices, intval($this->manager['manager_id'] ?? 0), false, false);
                                }
                                Db::commit();
                                $resultData['update_success']++;
                            } catch (\Exception $e) {
                                Db::rollback();
                                $resultData['update_fail']++;
                                $resultData['insert_fail_list'][] = ['row' => $key + 2, 'error' => $e->getMessage()];
                            }
                            continue;
                        }
                    }
                    unset($value['g_id']);
                    try {
                        validate(VGoods::class)->scene("importExcel")->check($value);
                    } catch (\Exception $e) {
                        $resultData['insert_fail']++;
                        $resultData['insert_fail_list'][] = [
                            'row' => $key + 2,
                            'error' => $e->getMessage(),
                        ];
                        continue;
                    }
                    try {
                        $currencyPrices = $this->extractCurrencyPrices($value, true);
                        $value = array_merge($value, CurrencyPriceSupport::normalizePriceRow($currencyPrices['CNY']));
                        Db::startTrans();
                        $gId = $this->addGoods($value);
                        if (!$gId) throw new \RuntimeException('新增商品失败');
                        $this->addGoodsLang([
                            'g_id' => $gId,
                            'g_name' => isset($value['g_name']) ? $value['g_name'] : '',
                            'gc_id' => isset($value['gc_id']) ? $value['gc_id'] : 0,
                            'gc_name' => isset($value['gc_name']) ? $value['gc_name'] : '',
                            'manufacturer' => isset($value['manufacturer']) ? $value['manufacturer'] : '',
                            'lang' => 'zh-cn',
                        ]);
                        (new GoodsCurrencyPriceService())->savePrices($gId, $currencyPrices, intval($this->manager['manager_id'] ?? 0), true, false);
                        Db::commit();
                        $resultData['insert_success']++;
                    } catch (\Exception $e) {
                        Db::rollback();
                        $resultData['insert_fail']++;
                        $resultData['insert_fail_list'][] = ['row' => $key + 2, 'error' => $e->getMessage()];
                    }
                }

                $resultData['insert_total'] = $resultData['insert_success'] + $resultData['insert_fail'];

                return $this->r(200, '导入完成', $resultData);
            }
            return $this->r(100, '获取不到Excel文档中的数据');
        } catch (\Exception $e) {
            actionException($e, 1);
            return $this->rValidate($e->getMessage());
        }
    }

    protected function getGoodsImportHeaderMap()
    {
        return [
            '商品名称' => 'g_name', '分类ID' => 'gc_id', '商品分类' => 'gc_name', '型号' => 'model',
            'SKU' => 'sku', 'SKU2' => 'sku2', '图片' => 'pic', '条形码' => 'bar_code',
            '人民币成本价' => 'cny_cost_price', '人民币市场价' => 'cny_market_price', '人民币零售价' => 'cny_retail_price',
            '港币成本价' => 'hkd_cost_price', '港币市场价' => 'hkd_market_price', '港币零售价' => 'hkd_retail_price',
            '成本价' => 'cost_price', '市场价' => 'market_price', '零售价' => 'retail_price', '售卖价' => 'retail_price',
            '生产厂家' => 'manufacturer', '售后电话' => 'service_phone', '状态' => 'status',
            '长' => 'length', '宽' => 'width', '高' => 'height', '商品ID' => 'g_id', 'g_id' => 'g_id',
        ];
    }

    protected function normalizeGoodsImportRow(array $row)
    {
        if (isset($row['status']) && !is_numeric($row['status'])) {
            $statusText = trim((string)$row['status']);
            if (in_array($statusText, ['启用', '正常', '上架'], true)) $row['status'] = 1;
            if (in_array($statusText, ['停用', '禁用', '下架'], true)) $row['status'] = 2;
        }
        return $row;
    }

    /**
     * 导出商品
     * @param $where
     * @return array|string
     */
    public function exportExcel($where, $hasCostPriceAuth = true)
    {
        $field = 'g_id,g_name,gc_name,gift_points,cost_points,
            (case g_type when 1 THEN "' . $this->lang("export.g_type1") .
            '" WHEN 2 THEN "' . $this->lang("export.g_type2") .
            '" WHEN 3 THEN "' . $this->lang("export.g_type3") .
            '" ELSE "' . $this->lang("export.g_type_unDefine") . '" END) g_type,
            model,sku,bar_code';
        $list = $this->getGoodsList($where, 0,
            $field);
        if ($list) {
            $list = $list->toArray();
            $list = $this->appendCurrencyPriceExportColumns($list, $hasCostPriceAuth);
            $title = [
                'g_name' => $this->lang("export.g_name") ,
                'g_type' => $this->lang("export.g_type"),
                'gc_name' => $this->lang("export.gc_name"),
                'model' => $this->lang("export.model"),
                'sku' => $this->lang("export.sku"),
                'bar_code' => $this->lang("export.bar_code"),
                'cny_cost_price' => '人民币成本价',
                'cny_market_price' => '人民币市场价',
                'cny_retail_price' => '人民币零售价',
                'hkd_cost_price' => '港币成本价',
                'hkd_market_price' => '港币市场价',
                'hkd_retail_price' => '港币零售价',
                'gift_points' => $this->lang("export.gift_points"),
                'cost_points' => $this->lang("export.cost_points"),
                'g_id' => 'g_id',
            ];
            $filename =  $this->lang("export.goods_list") . "-" . date("Ymd");
            $result = $this->sendToExport($this->lang("menu.goods_management") . "-" . $this->lang("export.goods_list"), $filename, $title, $list);
            return $result;
        }
        return $this->r(100, $this->lang("action_fail"));
    }

    /**
     * 导出所有商品
     * @param $where
     * @return array|string
     */
    public function exportAllGoodsToExcel($where, $hasCostPriceAuth = true)
    {
        $field = 'g_id,g_name,gc_id,gc_name,
            (case g_type when 1 THEN "' . $this->lang("export.g_type1") .
            '" WHEN 2 THEN "' . $this->lang("export.g_type2") .
            '" WHEN 3 THEN "' . $this->lang("export.g_type3") .
            '" ELSE "' . $this->lang("export.g_type_unDefine") . '" END) g_type,
            (case status when 1 THEN "' . $this->lang("export.status1") .
            '" WHEN 2 THEN "' . $this->lang("export.status2") .
            '" END) status,
            model,bar_code,sku,sku2,pic,manufacturer,service_phone,length,width,height';
        $list = $this->getGoodsList($where, 0,
            $field);
        if ($list) {
            $list = $list->toArray();
            $list = $this->appendCurrencyPriceExportColumns($list, $hasCostPriceAuth);
            $title = [
                'g_name' => '商品名称', 'gc_id' => '分类ID', 'gc_name' => '商品分类',
                'model' => '型号', 'sku' => 'SKU', 'sku2' => 'SKU2', 'pic' => '图片', 'bar_code' => '条形码',
                'cny_cost_price' => '人民币成本价', 'cny_market_price' => '人民币市场价', 'cny_retail_price' => '人民币零售价',
                'hkd_cost_price' => '港币成本价', 'hkd_market_price' => '港币市场价', 'hkd_retail_price' => '港币零售价',
                'manufacturer' => '生产厂家', 'service_phone' => '售后电话', 'status' => '状态',
                'length' => '长', 'width' => '宽', 'height' => '高', 'g_id' => 'g_id',
            ];
            $filename =  $this->lang("export.goods_list") . "-" . date("Ymd");
            $result = $this->sendToExport($this->lang("menu.goods_management") . "-" . $this->lang("export.goods_list"), $filename, $title, $list);
            return $result;
        }
        return $this->r(100, $this->lang("action_fail"));
    }

    protected function appendCurrencyPriceExportColumns(array $list, $hasCostPriceAuth)
    {
        $gIds = array_values(array_unique(array_filter(array_map('intval', array_column($list, 'g_id')))));
        $priceMap = (new GoodsCurrencyPriceService())->getPriceMapByGoodsIds($gIds);
        foreach ($list as &$row) {
            $byCode = [];
            foreach (isset($priceMap[intval($row['g_id'])]) ? $priceMap[intval($row['g_id'])] : [] as $price) {
                $byCode[$price['currency_code']] = $price;
            }
            foreach (['CNY' => 'cny', 'HKD' => 'hkd'] as $code => $prefix) {
                foreach ($this->priceFields as $field) {
                    $row[$prefix . '_' . $field] = isset($byCode[$code][$field]) ? $byCode[$code][$field] : '';
                }
                if (!$hasCostPriceAuth) $row[$prefix . '_cost_price'] = '';
            }
        }
        unset($row);
        return $list;
    }

    
    /**
     * 导入条形码
     * @param $data
     * @return array|string
     */
    public function importBarCode($data)
    {
        try {
            $path = root_path() . "public" . ($data['file_path'] ?? '');
            $title = ["g_id", "bar_code"];
            $rows = Excel::importExcel($path, $title);
            if (is_object($rows)) return $rows;
            if (!$rows) return $this->r(100, '获取不到Excel文档中的数据');
            $resultData = [
                'total' => count($rows),
                'success' => 0,
                'skip_exists' => 0,
                'skip_invalid' => 0,
                'failed' => 0,
                'skip_exists_list' => [],
                'skip_invalid_list' => [],
                'failed_list' => [],
            ];
            foreach ($rows as $value) {
                $gId = intval($value['g_id'] ?? 0);
                $barCode = trim($value['bar_code'] ?? '');
                if (!$gId || !$barCode) {
                    $resultData['skip_invalid']++;
                    $resultData['skip_invalid_list'][] = ['g_id' => $gId, 'bar_code' => $barCode];
                    continue;
                }
                $goods = $this->getGoodsFind(['g_id' => $gId], 'g_id');
                if (!$goods) {
                    $resultData['skip_invalid']++;
                    $resultData['skip_invalid_list'][] = ['g_id' => $gId, 'bar_code' => $barCode];
                    continue;
                }
                $whereExist = [];
                $whereExist[] = ['bar_code', '=', $barCode];
                $whereExist[] = ['g_id', '<>', $gId];
                $existGoods = $this->getGoodsFind($whereExist,'g_id');
                if ($existGoods) {
                    $resultData['skip_exists']++;
                    $resultData['skip_exists_list'][] = ['g_id' => $gId, 'bar_code' => $barCode];
                    continue;
                }
                $update = ['bar_code' => $barCode];
                $updateResult = $this->updateGoods($update, ['g_id' => $gId], ['bar_code']);
                if ($updateResult) {
                    $resultData['success']++;
                } else {
                    $resultData['failed']++;
                    $resultData['failed_list'][] = ['g_id' => $gId, 'bar_code' => $barCode];
                }
            }
            return $this->r(200, '导入完成', $resultData);
        } catch (\Exception $e) {
            actionException($e, 1);
            return $this->rValidate($e->getMessage());
        }
    }

    /**
     * 导出异常条形码商品
     * @param $where
     * @return array|string
     */
    public function exportAbnormalBarCodeExcel($where, $hasCostPriceAuth = true)
    {
        $costPriceField = $hasCostPriceAuth ? 'cost_price' : '0 cost_price';
        $field = 'g_id,g_name,bar_code,' . $costPriceField;
        $list = $this->getGoodsList($where, 0, $field);
        if ($list) {
            $list = $list->toArray();
            $title = [
                'g_id' => $this->lang("export.g_id"),
                'g_name' => $this->lang("export.g_name"),
                'bar_code' => $this->lang("export.bar_code"),
            ];
            if ($hasCostPriceAuth) $title['cost_price'] = $this->lang("export.cost_price");
            $filename = '异常条形码商品列表-' . date("Ymd");
            return $this->sendToExport('商品管理-异常条形码商品列表', $filename, $title, $list);
        }
        return $this->r(100, $this->lang("action_fail"));
    }
    
    /**
     * 概览——商品排行榜（分页）
     * @param array $where
     * @param int $pageNum
     * @return array|string
     */
    public function getRankingList($where = [], $pageNum = 0, $topType = 1)
    {
        $pageNum = max(0, intval($pageNum));

        if($this->manager['account']=='meichitu'){
            $where[] = ['gc_name','like','%美驰图%'];
        }

        $list = $this->queryGoodsRanking($where, $topType, $pageNum);

        if ($list) {
            $list = $this->formatGoodsRankingList($list);
        }

        if ($pageNum === 0) {
            $rows = $list ? $list->toArray() : [];
            $total = count($rows);
            $list = [
                'total' => $total,
                'per_page' => $total,
                'current_page' => 1,
                'last_page' => $total > 0 ? 1 : 0,
                'data' => $rows,
            ];
        }

        return $this->rQ($list);
    }

    
    /**
     * 导出商品排行榜（V2）
     * @param array $where
     * @param int $topType
     * @param int $pageNum 排行前多少条，0 表示全部
     * @return array|\think\response\Json|string
     */
    public function exportRankingListV2($where, $topType = 1, $pageNum = 0)
    {
        $pageNum = max(0, intval($pageNum));

        if($this->manager['account']=='meichitu'){
            $where[] = ['gc_name','like','%美驰图%'];
        }

        $list = $this->queryGoodsRanking($where, $topType, 0, $pageNum);
        if ($list) {
            $list = $list->toArray();
            $title = [
                "g_name" => $this->lang("export.g_name"),
                "totalPrice" => '实际销售额（扣除退款）',
                "totalQuantity" => '实际销量（扣除退款）',
                "totalRankPrice" => $this->lang("export.totalPrice"),
                "totalRankQuantity" => $this->lang("export.totalQuantity"),
                "totalDiscountPrice" => "优惠金额",
                "totalRefundAmount" => "退款金额",
                "totalRefundQuantity" => "退款数量",
                "retail_price" => $this->lang("export.retail_price"),
            ];
            $topTitle = "销售额-";
            if($topType === 2){
                $topTitle = "销量-";
            }
            $filename = $topTitle . $this->lang("export.goodsTopList") . date("Ymd");
            $result = $this->sendToExport($this->lang("export.goodsTopRankFileName"), $filename, $title, $list);
            return $result;
        }
        return $this->rQ($list);
    }

    /**
     * 按商品维度统计指定在营状态设备的上架、库存、货道与周期销量。
     * 设备状态口径：machine.is_operating，默认 1（在营），且设备/货道均为启用状态。
     * 销售口径：当前商品在对应状态设备上的已支付订单明细数量。
     * @param array $postData
     * @return array|\think\response\Json
     */
    public function getOperatingGoodsList($postData)
    {
        if (!$this->validateOperatingGoodsStatus($postData)) {
            return $this->rValidate('在营状态参数错误');
        }

        $pageNum = intval($postData['pageNum'] ?? 0);
        $pageIndex = max(1, intval($postData['page'] ?? 1));
        $query = $this->buildOperatingGoodsQuery($postData);
        $query->orderRaw($this->getOperatingGoodsOrder($postData));

        if ($pageNum > 0) {
            $page = $query->paginate([
                'list_rows' => $pageNum,
                'page' => $pageIndex,
                'query' => request()->param(),
            ], false);
            $result = $page->toArray();
            $rows = $result['data'] ?? [];
            $result['data'] = $this->appendOperatingGoodsDetail($rows, $postData);
            $result['request_params'] = $this->getOperatingGoodsRequestParams($postData);
            return $this->rQ($result);
        }

        $rows = $query->select()->toArray();
        $rows = $this->appendOperatingGoodsDetail($rows, $postData);
        return $this->rQ($rows);
    }

    /**
     * 返回已生效的业务查询参数，不回传 token 或未知字段。
     * @param array $postData
     * @return array
     */
    private function getOperatingGoodsRequestParams($postData)
    {
        $params = [];
        $fields = [
            'g_id', 'g_name', 'sku', 'm_id', 'machine_id', 'ao_id',
            'start_time', 'end_time', 'create_time', 'sort_by', 'sort_order',
        ];
        foreach ($fields as $field) {
            if (isset($postData[$field]) && $postData[$field] !== '') {
                $params[$field] = $postData[$field];
            }
        }
        $params['page'] = max(1, intval($postData['page'] ?? 1));
        $params['pageNum'] = max(0, intval($postData['pageNum'] ?? 0));
        $params['is_operating'] = $this->getOperatingGoodsStatus($postData);
        return $params;
    }

    /**
     * 导出商品维度在营库存、设备货道与周期销量。
     * @param array $postData
     * @return array|\think\response\Json
     */
    public function exportOperatingGoodsList($postData)
    {
        if (!$this->validateOperatingGoodsStatus($postData)) {
            return $this->rValidate('在营状态参数错误');
        }

        unset($postData['page'], $postData['pageNum']);
        $query = $this->buildOperatingGoodsQuery($postData);
        $query->orderRaw($this->getOperatingGoodsOrder($postData));

        $rows = $query->select()->toArray();
        if (!$rows) {
            return $this->rNoData();
        }

        $rows = $this->appendOperatingGoodsDetail($rows, $postData);
        $list = $this->formatOperatingGoodsExportRows($rows);
        if (!$list) {
            return $this->rNoData();
        }

        $title = [
            'g_id' => '商品ID',
            'g_name' => '商品名称',
            'sku' => 'SKU',
            'gc_name' => '商品分类',
            'operating_stock' => '在营库存',
            'operating_machine_count' => '在营设备数量',
            'operating_machine_names' => '在营设备列表',
            'operating_channel_count' => '对应设备货道数量',
            'operating_channel_info' => '对应设备货道信息',
            'period_sale_quantity' => '周期内销售数量',
            'period_refund_quantity' => '周期内退款数量',
            'period_net_sale_quantity' => '周期内净销售数量',
        ];
        $filename = '在营设备商品统计-' . date('YmdHis');
        return $this->sendToExport('商品管理-在营设备商品统计', $filename, $title, $list);
    }

    /**
     * 构造商品维度在营库存聚合查询。
     * @param array $postData
     * @return \think\db\Query
     */
    private function buildOperatingGoodsQuery($postData)
    {
        $query = Db::name('machine_channel')->alias('mc')
            ->join('machine m', 'm.m_id = mc.m_id')
            ->leftJoin('goods g', 'g.g_id = mc.g_id')
            ->where('m.status', 1)
            ->where('mc.status', 1)
            ->where('mc.g_id', '>', 0)

            ->fieldRaw('
                mc.g_id,
                MAX(IFNULL(NULLIF(g.g_name, ""), mc.g_name)) AS g_name,
                MAX(IFNULL(NULLIF(g.sku, ""), mc.sku)) AS sku,
                MAX(IFNULL(NULLIF(g.gc_name, ""), mc.gc_name)) AS gc_name,
                MAX(IFNULL(NULLIF(g.pic, ""), mc.pic)) AS pic,
                SUM(IFNULL(mc.stock, 0)) AS operating_stock,
                COUNT(DISTINCT mc.m_id) AS operating_machine_count,
                COUNT(mc.mc_id) AS operating_channel_count
            ')
            ->group('mc.g_id');

        $this->applyOperatingGoodsWhere($query, $postData);
        return $query;
    }

    /**
     * 查询条件映射。
     * @param \think\db\Query $query
     * @param array $postData
     */
    private function applyOperatingGoodsWhere(&$query, $postData)
    {
        if (isset($postData['is_operating']) && $postData['is_operating'] !== '') {
            $query->where('m.is_operating', '=', intval($postData['is_operating']));
        }

        $permittedMIds = $this->resolveGoodsOperatingPermittedMachineIds();
        if ($permittedMIds !== null) {
            if (!$permittedMIds) {
                $query->where('mc.m_id', '=', 0);
            } else {
                $query->where('mc.m_id', 'in', $permittedMIds);
            }
        }


        $gIds = $this->parseOperatingGoodsIds($postData['g_id'] ?? []);
        if ($gIds) {
            $query->where('mc.g_id', 'in', $gIds);
        }

        $mIds = $this->parseOperatingGoodsIds($postData['m_id'] ?? []);
        if ($mIds) {
            $query->where('mc.m_id', 'in', $mIds);
        }

        if (!empty($postData['machine_id'])) {
            $machineIds = $this->parseOperatingGoodsStrings($postData['machine_id']);
            if (count($machineIds) > 1) {
                $query->where('mc.machine_id', 'in', $machineIds);
            } else {
                $query->where('mc.machine_id', 'like', '%' . $postData['machine_id'] . '%');
            }
        }

        if (!empty($postData['g_name'])) {
            $gName = $postData['g_name'];
            $query->where(function ($q) use ($gName) {
                $q->where('g.g_name', 'like', '%' . $gName . '%')
                    ->whereOr('mc.g_name', 'like', '%' . $gName . '%');
            });
        }

        if (!empty($postData['sku'])) {
            $sku = $postData['sku'];
            $query->where(function ($q) use ($sku) {
                $q->where('g.sku', 'like', '%' . $sku . '%')
                    ->whereOr('mc.sku', 'like', '%' . $sku . '%');
            });
        }

        if (!empty($postData['ao_id'])) {
            $query->where('m.ao_id', '=', intval($postData['ao_id']));
        }
    }

    /**
     * 补充设备列表、货道列表和周期销量。
     * @param array $rows
     * @param array $postData
     * @return array
     */
    private function appendOperatingGoodsDetail($rows, $postData)
    {
        if (!$rows) {
            return [];
        }

        $gIds = array_values(array_unique(array_filter(array_map('intval', array_column($rows, 'g_id')))));
        if (!$gIds) {
            return $rows;
        }

        $channelRows = $this->queryOperatingGoodsChannels($gIds, $postData);
        $detailMap = [];
        $goodsMachineMap = [];

        foreach ($channelRows as $channel) {
            $gId = intval($channel['g_id']);
            $mId = intval($channel['m_id']);
            if (!isset($detailMap[$gId])) {
                $detailMap[$gId] = [
                    'operating_machine_ids' => [],
                    'operating_machine_list' => [],
                    'operating_channel_list' => [],
                ];
            }
            if (!isset($detailMap[$gId]['operating_machine_list'][$mId])) {
                $detailMap[$gId]['operating_machine_ids'][] = $mId;
                $detailMap[$gId]['operating_machine_list'][$mId] = [
                    'm_id' => $mId,
                    'machine_id' => $channel['machine_id'],
                    'machine_name' => $channel['machine_name'],
                    'ao_id' => intval($channel['ao_id']),
                    'channel_count' => 0,
                    'channel_stock' => 0,
                    'channel_list' => [],
                ];
            }

            $channelInfo = [
                'mc_id' => intval($channel['mc_id']),
                'mg_id' => intval($channel['mg_id']),
                'm_id' => $mId,
                'machine_id' => $channel['machine_id'],
                'machine_name' => $channel['machine_name'],
                'channel_code' => $channel['channel_code'],
                'channel_name' => $channel['channel_name'],
                'stock' => intval($channel['stock']),
                'capacity' => intval($channel['capacity']),
                'frozen_stock' => intval($channel['frozen_stock']),
                'sku' => $channel['sku'],
            ];

            $detailMap[$gId]['operating_machine_list'][$mId]['channel_count']++;
            $detailMap[$gId]['operating_machine_list'][$mId]['channel_stock'] += intval($channel['stock']);
            $detailMap[$gId]['operating_machine_list'][$mId]['channel_list'][] = $channelInfo;
            $detailMap[$gId]['operating_channel_list'][] = $channelInfo;
            $goodsMachineMap[$gId][$mId] = true;
        }

        $saleMap = $this->queryOperatingGoodsSales($gIds, $goodsMachineMap, $postData);

        foreach ($rows as $key => $row) {
            $gId = intval($row['g_id']);
            $detail = $detailMap[$gId] ?? [
                'operating_machine_ids' => [],
                'operating_machine_list' => [],
                'operating_channel_list' => [],
            ];

            $rows[$key]['g_id'] = $gId;
            $rows[$key]['operating_stock'] = intval($row['operating_stock'] ?? 0);
            $rows[$key]['operating_machine_count'] = intval($row['operating_machine_count'] ?? 0);
            $rows[$key]['operating_channel_count'] = intval($row['operating_channel_count'] ?? 0);
            $rows[$key]['operating_machine_ids'] = array_values($detail['operating_machine_ids']);
            $rows[$key]['operating_machine_list'] = array_values($detail['operating_machine_list']);
            $rows[$key]['operating_channel_list'] = array_values($detail['operating_channel_list']);
            $rows[$key]['period_sale_quantity'] = intval($saleMap[$gId]['period_sale_quantity'] ?? 0);
            $rows[$key]['period_refund_quantity'] = intval($saleMap[$gId]['period_refund_quantity'] ?? 0);
            $rows[$key]['period_net_sale_quantity'] = intval($saleMap[$gId]['period_net_sale_quantity'] ?? 0);
        }

        return $rows;
    }

    /**
     * 将嵌套设备/货道信息整理为导出行。
     * @param array $rows
     * @return array
     */
    private function formatOperatingGoodsExportRows($rows)
    {
        $list = [];
        foreach ($rows as $row) {
            $machineNames = [];
            foreach (($row['operating_machine_list'] ?? []) as $machine) {
                $machineNames[] = sprintf(
                    '%s/%s(货道:%d,库存:%d)',
                    $machine['machine_id'] ?? '',
                    $machine['machine_name'] ?? '',
                    intval($machine['channel_count'] ?? 0),
                    intval($machine['channel_stock'] ?? 0)
                );
            }

            $channelInfo = [];
            foreach (($row['operating_channel_list'] ?? []) as $channel) {
                $channelInfo[] = sprintf(
                    '%s/%s-%s(库存:%d,容量:%d,冻结:%d)',
                    $channel['machine_id'] ?? '',
                    $channel['machine_name'] ?? '',
                    $channel['channel_code'] ?? '',
                    intval($channel['stock'] ?? 0),
                    intval($channel['capacity'] ?? 0),
                    intval($channel['frozen_stock'] ?? 0)
                );
            }

            $list[] = [
                'g_id' => intval($row['g_id'] ?? 0),
                'g_name' => $row['g_name'] ?? '',
                'sku' => $row['sku'] ?? '',
                'gc_name' => $row['gc_name'] ?? '',
                'operating_stock' => intval($row['operating_stock'] ?? 0),
                'operating_machine_count' => intval($row['operating_machine_count'] ?? 0),
                'operating_machine_names' => implode('; ', $machineNames),
                'operating_channel_count' => intval($row['operating_channel_count'] ?? 0),
                'operating_channel_info' => implode('; ', $channelInfo),
                'period_sale_quantity' => intval($row['period_sale_quantity'] ?? 0),
                'period_refund_quantity' => intval($row['period_refund_quantity'] ?? 0),
                'period_net_sale_quantity' => intval($row['period_net_sale_quantity'] ?? 0),
            ];
        }

        return $list;
    }

    /**
     * 查询当前页商品对应的在营设备货道。
     * @param array $gIds
     * @param array $postData
     * @return array
     */
    private function queryOperatingGoodsChannels($gIds, $postData)
    {
        $query = Db::name('machine_channel')->alias('mc')
            ->join('machine m', 'm.m_id = mc.m_id')
            ->leftJoin('goods g', 'g.g_id = mc.g_id')
            ->where('m.status', 1)
            ->where('mc.status', 1)
            ->where('mc.g_id', 'in', $gIds)

            ->field('mc.mc_id,mc.mg_id,mc.m_id,mc.machine_id,m.machine_name,m.ao_id,mc.g_id,mc.channel_code,mc.channel_name,mc.stock,mc.capacity,mc.frozen_stock,mc.sku')
            ->order('mc.g_id desc,mc.m_id asc,mc.channel_code asc,mc.mc_id asc');

        $this->applyOperatingGoodsWhere($query, $postData);
        return $query->select()->toArray();
    }

    /**
     * 校验设备在营状态筛选值；不传时沿用原有在营设备口径。
     * @param array $postData
     * @return bool
     */
    private function validateOperatingGoodsStatus($postData)
    {
        if (!isset($postData['is_operating']) || $postData['is_operating'] === '') {
            return true;
        }

        return in_array(intval($postData['is_operating']), [1, 2, 3], true);
    }

    /**
     * 获取设备在营状态筛选值。
     * @param array $postData
     * @return int
     */
    private function getOperatingGoodsStatus($postData)
    {
        return isset($postData['is_operating']) && $postData['is_operating'] !== ''
            ? intval($postData['is_operating'])
            : 1;
    }

    /**
     * 查询周期内销量，按当前上架商品和对应在营设备匹配。
     * @param array $gIds
     * @param array $goodsMachineMap
     * @param array $postData
     * @return array
     */
    private function queryOperatingGoodsSales($gIds, $goodsMachineMap, $postData)
    {
        if (!$gIds || !$goodsMachineMap) {
            return [];
        }

        $allMIds = [];
        foreach ($goodsMachineMap as $machineMap) {
            $allMIds = array_merge($allMIds, array_keys($machineMap));
        }
        $allMIds = array_values(array_unique(array_map('intval', $allMIds)));
        if (!$allMIds) {
            return [];
        }

        $query = Db::name('sale_orders_details')->alias('sod')
            ->join('sale_orders so', 'so.order_id = sod.order_id')
            ->where('so.pay_status', 3)
            ->where('sod.g_id', 'in', $gIds)
            ->where('so.m_id', 'in', $allMIds)
            ->fieldRaw('sod.g_id,so.m_id,SUM(IFNULL(sod.quantity, 0)) AS period_sale_quantity,SUM(IFNULL(sod.refund_quantity, 0)) AS period_refund_quantity')
            ->group('sod.g_id,so.m_id');

        [$startTime, $endTime] = $this->parseOperatingGoodsPeriod($postData);
        if ($startTime > 0) {
            $query->where('so.create_date', '>=', $startTime);
        }
        if ($endTime > 0) {
            $query->where('so.create_date', '<=', $endTime);
        }

        $sales = $query->select()->toArray();
        $saleMap = [];
        foreach ($sales as $sale) {
            $gId = intval($sale['g_id']);
            $mId = intval($sale['m_id']);
            if (empty($goodsMachineMap[$gId][$mId])) {
                continue;
            }
            if (!isset($saleMap[$gId])) {
                $saleMap[$gId] = [
                    'period_sale_quantity' => 0,
                    'period_refund_quantity' => 0,
                    'period_net_sale_quantity' => 0,
                ];
            }
            $saleQuantity = intval($sale['period_sale_quantity']);
            $refundQuantity = intval($sale['period_refund_quantity']);
            $saleMap[$gId]['period_sale_quantity'] += $saleQuantity;
            $saleMap[$gId]['period_refund_quantity'] += $refundQuantity;
            $saleMap[$gId]['period_net_sale_quantity'] += max(0, $saleQuantity - $refundQuantity);
        }

        return $saleMap;
    }

    /**
     * 账号可见设备范围，保持与 MachineClient::getMList 一致。
     * @return array|null
     */
    private function resolveGoodsOperatingPermittedMachineIds()
    {
        if (($this->manager['pid'] ?? 0) > 0) {
            $mIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], 'm_id');
            $createMIds = $this->getMachineColumn(['creator' => $this->manager['manager_id']], 'm_id');
            return array_values(array_unique(array_map('intval', array_merge(
                is_array($mIds) ? $mIds : [],
                is_array($createMIds) ? $createMIds : []
            ))));
        }
        return null;
    }

    /**
     * 解析周期参数，支持 start_time/end_time 或 create_time=开始~结束。
     * @param array $postData
     * @return array
     */
    private function parseOperatingGoodsPeriod($postData)
    {
        $start = $postData['start_time'] ?? 0;
        $end = $postData['end_time'] ?? 0;

        if ((!$start || !$end) && !empty($postData['create_time']) && strpos($postData['create_time'], '~') !== false) {
            [$start, $end] = explode('~', $postData['create_time'], 2);
        }

        $start = $this->normalizeOperatingGoodsTime($start, false);
        $end = $this->normalizeOperatingGoodsTime($end, true);
        return [$start, $end];
    }

    /**
     * @param mixed $value
     * @param bool $endOfDay
     * @return int
     */
    private function normalizeOperatingGoodsTime($value, $endOfDay = false)
    {
        if ($value === '' || $value === null) {
            return 0;
        }
        if (is_numeric($value)) {
            return intval($value);
        }
        $value = trim($value);
        if ($value === '') {
            return 0;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            $value .= $endOfDay ? ' 23:59:59' : ' 00:00:00';
        }
        $time = strtotime($value);
        return $time ? intval($time) : 0;
    }

    /**
     * @param mixed $ids
     * @return array
     */
    private function parseOperatingGoodsIds($ids)
    {
        return array_values(array_unique(array_filter(array_map('intval', $this->parseOperatingGoodsStrings($ids)))));
    }

    /**
     * @param mixed $value
     * @return array
     */
    private function parseOperatingGoodsStrings($value)
    {
        if (is_array($value)) {
            $list = $value;
        } else {
            $list = explode(',', strval($value));
        }
        $list = array_map('trim', $list);
        return array_values(array_filter($list, function ($item) {
            return $item !== '';
        }));
    }

    /**
     * @param array $postData
     * @return string
     */
    private function getOperatingGoodsOrder($postData)
    {
        $sortBy = $postData['sort_by'] ?? 'g_id';
        $sortOrder = strtolower($postData['sort_order'] ?? 'desc') == 'asc' ? 'asc' : 'desc';
        $sortMap = [
            'g_id' => 'mc.g_id',
            'operating_stock' => 'operating_stock',
            'operating_machine_count' => 'operating_machine_count',
            'operating_channel_count' => 'operating_channel_count',
        ];
        $field = $sortMap[$sortBy] ?? $sortMap['g_id'];
        return $field . ' ' . $sortOrder . ',mc.g_id desc';
    }

    /**
     * 商品排行榜聚合查询（基于订单明细，不依赖统计视图）
     * @param array $where
     * @param int $topType
     * @param int $pageNum
     * @param int $limit
     * @return \think\Collection|\think\Paginator
     */
    private function queryGoodsRanking($where, $topType = 1, $pageNum = 0, $limit = 0)
    {
        $order = 'totalPrice desc,totalQuantity desc,g_id desc,g_name asc';
        if ($topType == 2) {
            $order = 'totalQuantity desc,totalPrice desc,g_id desc,g_name asc';
        }

        $query = Db::name('sale_orders_details')->alias('sod')
            ->join('sale_orders so', 'so.order_id = sod.order_id')
            ->where('so.pay_status', 3)
            ->field([
                'sod.g_id' => 'g_id',
                'MAX(sod.g_name)' => 'g_name',
                'MAX(sod.wc_order_no)' => 'wc_order_no',
                'MAX(sod.pic)' => 'pic',
                'MAX(sod.sku)' => 'sku',
                'MAX(sod.gc_id)' => 'gc_id',
                'MAX(sod.gc_name)' => 'gc_name',
                'ROUND(MAX(sod.cost_price),2)' => 'cost_price',
                'ROUND(MAX(sod.market_price),2)' => 'market_price',
                'ROUND(MAX(sod.retail_price),2)' => 'retail_price',
                'ROUND(SUM(sod.total_sod_price),2)' => 'totalRankPrice',
                'SUM(sod.quantity)' => 'totalRankQuantity',
                'ROUND(SUM(IFNULL(sod.refund_amount,0)),2)' => 'totalRefundAmount',
                'SUM(IFNULL(sod.refund_quantity,0))' => 'totalRefundQuantity',
                'ROUND(SUM(sod.total_sod_price)-SUM(IFNULL(sod.refund_amount,0)),2)' => 'totalPrice',
                'SUM(sod.quantity)-SUM(IFNULL(sod.refund_quantity,0))' => 'totalQuantity',
                'ROUND(SUM(sod.discount_price),2)' => 'totalDiscountPrice',
            ])
            ->group("sod.g_id,IF(sod.g_id = 0, sod.g_name, '')")
            ->having('ROUND(SUM(sod.total_sod_price), 2) > 0 AND SUM(sod.quantity) > 0')
            ->orderRaw($order);

        $this->applyGoodsRankingWhere($query, $where);

        if ($pageNum) {
            $res = $query->paginate($pageNum, false, ["query" => request()->param()]);
        }else{
            if ($limit > 0) {
                $query->limit($limit);
            }
            $res = $query->select();
        }
        
        return $res;
    }

    /**
     * 统一处理排行榜商品编号与多语言名称。
     * @param \think\Collection|\think\Paginator $list
     * @return \think\Collection|\think\Paginator
     */
    private function formatGoodsRankingList($list)
    {
        $lang = input("lang");
        return $list->each(function ($item) use ($lang) {
            if (intval($item['g_id'] ?? 0) === 0) {
                $onlineGoodsNo = $this->getGoodsRankingOnlineNo($item['wc_order_no'] ?? '');
                if ($onlineGoodsNo !== '') {
                    $item['g_id'] = $onlineGoodsNo;
                }
            } elseif ($lang) {
                $gl = $this->getGoodsLangFind(['lang' => $lang, 'g_id' => $item['g_id']], 0, 'g_name');
                if ($gl) {
                    $item['g_name'] = $gl['g_name'];
                }
            }
            unset($item['wc_order_no']);
            return $item;
        });
    }

    /**
     * 从线上商品订单信息中获取商品编号。
     * @param mixed $wcOrderNo
     * @return string
     */
    private function getGoodsRankingOnlineNo($wcOrderNo)
    {
        if (is_string($wcOrderNo)) {
            $wcOrderNo = json_decode($wcOrderNo, true);
        }
        if (!is_array($wcOrderNo)) {
            return '';
        }

        foreach ($wcOrderNo as $key => $item) {
            $no = is_array($item) ? trim(strval($item['no'] ?? '')) : '';
            if ($no !== '') {
                return $no;
            }
            if (is_string($key) && trim($key) !== '') {
                return trim($key);
            }
        }

        return '';
    }

    /**
     * 将通用where映射到订单明细排行榜查询
     * @param $query
     * @param array $where
     */
    private function applyGoodsRankingWhere(&$query, $where)
    {
        if (isset($where['ao_id']) && $where['ao_id'] !== '') {
            $query->where('so.ao_id', '=', $where['ao_id']);
        }

        foreach ($where as $k => $v) {
            if (!is_int($k) || !is_array($v) || count($v) < 3) {
                continue;
            }

            $field = $v[0];
            $op = strtolower($v[1]);
            $value = $v[2];

            if ($field == 'm_id') {
                $query->where('so.m_id', $op, $value);
                continue;
            }
            if ($field == 'countDate') {
                $query->where('so.create_date', $op, $value);
                continue;
            }
            if ($field == 'ao_id') {
                $query->where('so.ao_id', $op, $value);
                continue;
            }
            if ($field == 'gc_name') {
                $query->where('sod.gc_name', $op, $value);
                continue;
            }
            if ($field == 'g_id') {
                $query->where('sod.g_id', $op, $value);
                continue;
            }
            if ($field == 'sku') {
                $query->where('sod.sku', $op, $value);
                continue;
            }
            if ($field == 'g_name') {
                $query->where('sod.g_name', $op, $value);
                continue;
            }
        }
    }
    
}
