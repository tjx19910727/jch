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
        $g_id = $this->addGoods($postData);
        if ($g_id) {
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
        }
        return $this->rA($g_id);
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
        $list = $this->getSaleOrdersGoodsCountList($where, 0,
            'g_id,g_name,totalPrice,totalQuantity,retail_price,pic',
            // 'totalPrice desc,totalQuantity desc, g_id desc', '', '', 10);
            'totalPrice desc', '', '', 10);
        if ($list) {
            $list = $list->toArray();
            $lang = input("lang");
            if ($lang) {
                $whereGl['lang'] = $lang;
                foreach ($list as $key => $value) {
                    $whereGl['g_id'] = $value['g_id'];
                    $gl = $this->getGoodsLangFind($whereGl, 0, 'g_name');
                    if ($gl) {
                        $value['g_name'] = $gl['g_name'];
                    }
                    $list[$key] = $value;
                }
            }
        }
        return $this->rQ($list);
    }

    public function getAuthList($where,$pageNum,$field,$order,$input){
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
        $result = $this->app->goods->getList($where,$pageNum,$field,'g_id desc');
        return $result;
    }

    /**
     * 商品编辑：主表价格正常更新，设备商品/货道价格仅按传入的 mg_id、mc_id 覆盖
     * 不走通用 update 入口，控制器直接调用本方法。
     * @param array $postData
     * @return mixed
     */
    public function updateForEdit($postData)
    {
        $gId = $postData['g_id'] ?? 0;
        if (!$gId) {
            return false;
        }

        $oldGoods = $this->getGoodsFind(['g_id' => $gId], 'g_id,cost_price,market_price,retail_price');
        if (!$oldGoods) {
            return false;
        }
        $oldGoods = $oldGoods->toArray();

        $selectedMgIds = $this->parseIds($postData['mg_id'] ?? []);
        $selectedMcIds = $this->parseIds($postData['mc_id'] ?? []);
        unset($postData['mg_id'], $postData['mc_id']);

        $priceChanged = false;
        foreach ($this->priceFields as $fieldName) {
            if (array_key_exists($fieldName, $postData) && (string)$postData[$fieldName] !== (string)$oldGoods[$fieldName]) {
                $priceChanged = true;
                break;
            }
        }

        $result = $this->updateGoods($postData, ['g_id' => $gId]);
        if (!$result) {
            return $result;
        }

        if ($priceChanged) {
            $priceUpdate = [];
            foreach ($this->priceFields as $fieldName) {
                if (array_key_exists($fieldName, $postData)) {
                    $priceUpdate[$fieldName] = $postData[$fieldName];
                }
            }

            if ($priceUpdate) {
                if ($selectedMgIds) {
                    $whereMg = [];
                    $whereMg[] = ['g_id', '=', $gId];
                    $whereMg[] = ['mg_id', 'in', $selectedMgIds];
                    $this->updateMachineGoods($priceUpdate, $whereMg, array_keys($priceUpdate));
                }
                if ($selectedMcIds) {
                    $whereMc = [];
                    $whereMc[] = ['g_id', '=', $gId];
                    $whereMc[] = ['mc_id', 'in', $selectedMcIds];
                    $this->updateMachineChannel($priceUpdate, $whereMc, array_keys($priceUpdate));
                }
            }
        }

        $mgList = $this->getMachineGoodsList([['g_id', '=', $gId]], 0, 'mg_id,machine_id');
        if ($mgList) {
            $mgList = $mgList->toArray();
            foreach ($mgList as $mg) {
                $this->sendToMachine(['machine_id' => $mg['machine_id']], 'updateMg', ['mg_id' => $mg['mg_id']]);
            }
        }

        $mcList = $this->getMachineChannelList([['g_id', '=', $gId]], 0, 'mc_id,machine_id');
        if ($mcList) {
            $mcList = $mcList->toArray();
            foreach ($mcList as $mc) {
                $this->sendToMachine(['machine_id' => $mc['machine_id']], 'updateMc', ['mc_id' => $mc['mc_id']]);
            }
        }

        return $result;
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

        $goods = $this->getGoodsFind(['g_id' => $gId], 'g_id,cost_price,market_price,retail_price');
        if (!$goods) {
            return $this->rFail($this->lang('goods_no_data'));
        }
        $goods = $goods->toArray();

        $latestCost = $postData['cost_price'] ?? $goods['cost_price'];
        $latestMarket = $postData['market_price'] ?? $goods['market_price'];
        $latestRetail = $postData['retail_price'] ?? $goods['retail_price'];

        $mgDiff = Db::name('machine_goods')
            ->where('g_id', $gId)
            ->where(function ($query) use ($latestCost, $latestMarket, $latestRetail) {
                $query->where('cost_price', '<>', $latestCost)
                    ->whereOr('market_price', '<>', $latestMarket)
                    ->whereOr('retail_price', '<>', $latestRetail);
            })
            ->field('mg_id,m_id,machine_id,g_id,g_name,cost_price,market_price,retail_price')
            ->order('mg_id desc')
            ->select()
            ->toArray();

        $mcDiff = Db::name('machine_channel')
            ->where('g_id', $gId)
            ->where(function ($query) use ($latestCost, $latestMarket, $latestRetail) {
                $query->where('cost_price', '<>', $latestCost)
                    ->whereOr('market_price', '<>', $latestMarket)
                    ->whereOr('retail_price', '<>', $latestRetail);
            })
            ->field('mc_id,m_id,machine_id,channel_code,g_id,g_name,cost_price,market_price,retail_price')
            ->order('mc_id desc')
            ->select()
            ->toArray();

        return $this->r(200, 'success', [
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

    public function exportRankingList($where)
    {
        $list = $this->getSaleOrdersGoodsCountList($where, 0,
            'g_name,totalPrice,totalQuantity,retail_price,pic',
            'totalPrice desc,totalQuantity desc, g_id desc', '', '', 10);
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
            $goods = Excel::importExcel($path, $title, $other);
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
    public function importExcelV2($data)
    {
        try {
            $path = root_path() . "public" . $data['file_path'];
            $title = ["g_name", "gc_id", "gc_name", "model", "sku", "sku2", "pic", "bar_code", "cost_price", "market_price", "retail_price", "manufacturer", "service_phone", "status",'length','width','height',"g_id"];
            $other = ['creator' => $this->manager['manager_id'] ?? 0, 'ao_id' => $this->manager['ao_id'] ?? 0];
            $goods = Excel::importExcel($path, $title, $other);
            if (is_object($goods)) return $goods;
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
                    $gId = intval($value['g_id'] ?? 0);
                    if ($gId > 0) {
                        $goodsFind = $this->getGoodsFind(['g_id' => $gId], 'g_id');
                        if ($goodsFind) {
                            $update = ['bar_code' => trim($value['bar_code'] ?? '')];
                            $updateResult = $this->updateGoods($update, ['g_id' => $gId], ['bar_code']);
                            if ($updateResult) {
                                $resultData['update_success']++;
                            } else {
                                $resultData['update_fail']++;
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
                    $insertGoods[] = $value;
                }

                $resultData['insert_total'] = count($insertGoods);
                if ($insertGoods) {
                    $insertResult = $this->addMoreGoods($insertGoods);
                    if ($insertResult) {
                        $resultData['insert_success'] = count($insertGoods);
                    } else {
                        $resultData['insert_fail'] += count($insertGoods);
                    }
                }

                return $this->r(200, '导入完成', $resultData);
            }
            return $this->r(100, '获取不到Excel文档中的数据');
        } catch (\Exception $e) {
            actionException($e, 1);
            return $this->rValidate($e->getMessage());
        }
    }

    /**
     * 导出商品
     * @param $where
     * @return array|string
     */
    public function exportExcel($where)
    {
        $list = $this->getGoodsList($where, 0,
            'g_id,g_name,gc_name,gift_points,cost_points,
            (case g_type when 1 THEN "' . $this->lang("export.g_type1") .
            '" WHEN 2 THEN "' . $this->lang("export.g_type2") .
            '" WHEN 3 THEN "' . $this->lang("export.g_type3") .
            '" ELSE "' . $this->lang("export.g_type_unDefine") . '" END) g_type,
            model,sku,bar_code,cost_price,market_price,retail_price');
        if ($list) {
            $list = $list->toArray();
            $title = [
                'g_id' => $this->lang("export.g_id"),
                'g_name' => $this->lang("export.g_name") ,
                'g_type' => $this->lang("export.g_type"),
                'gc_name' => $this->lang("export.gc_name"),
                'model' => $this->lang("export.model"),
                'sku' => $this->lang("export.sku"),
                'bar_code' => $this->lang("export.bar_code"),
                'cost_price' => $this->lang("export.cost_price"),
                'market_price' => $this->lang("export.market_price"),
                'retail_price' => $this->lang("export.retail_price"),
                'gift_points' => $this->lang("export.gift_points"),
                'cost_points' => $this->lang("export.cost_points"),
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
    public function exportAllGoodsToExcel($where)
    {
        $list = $this->getGoodsList([], 0,
            'g_id,g_name,gc_name,
            (case g_type when 1 THEN "' . $this->lang("export.g_type1") .
            '" WHEN 2 THEN "' . $this->lang("export.g_type2") .                                                                                                             
            '" WHEN 3 THEN "' . $this->lang("export.g_type3") .
            '" ELSE "' . $this->lang("export.g_type_unDefine") . '" END) g_type,
            (case status when 1 THEN "' . $this->lang("export.status1") .
            '" WHEN 2 THEN "' . $this->lang("export.status2") .                                                                                                             
            '" END) status,
            model,bar_code,sku,pic,cost_price,market_price,retail_price,manufacturer,service_phone,length,width,height');
        if ($list) {
            $list = $list->toArray();
            $title = [
                'g_id' => $this->lang("export.g_id"),
                'g_name' => $this->lang("export.g_name") ,
                'g_type' => $this->lang("export.g_type"),
                'gc_name' => $this->lang("export.gc_name"),
                'model' => $this->lang("export.model"),
                'bar_code' => $this->lang("export.bar_code"),
                'sku' => $this->lang("export.sku"),
                'pic' => $this->lang("export.pic"),
                'cost_price' => $this->lang("export.cost_price"),
                'market_price' => $this->lang("export.market_price"),
                'retail_price' => $this->lang("export.retail_price"),
                'status' => $this->lang("export.status"),
                'manufacturer' => $this->lang("export.manufacturer"),
                'service_phone' => $this->lang("export.service_phone"),
                'length' => $this->lang("export.length"),
                'width' => $this->lang("export.width"),
                'height' => $this->lang("export.height"),
            ];
            $filename =  $this->lang("export.goods_list") . "-" . date("Ymd");
            $result = $this->sendToExport($this->lang("menu.goods_management") . "-" . $this->lang("export.goods_list"), $filename, $title, $list);
            return $result;
        }
        return $this->r(100, $this->lang("action_fail"));
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
    public function exportAbnormalBarCodeExcel($where)
    {
        $list = $this->getGoodsList($where, 0, 'g_id,g_name,bar_code');
        if ($list) {
            $list = $list->toArray();
            $title = [
                'g_id' => $this->lang("export.g_id"),
                'g_name' => $this->lang("export.g_name"),
                'bar_code' => $this->lang("export.bar_code"),
            ];
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
        if($this->manager['account']=='meichitu'){
            $where[] = ['gc_name','like','%美驰图%'];
        }

        $list = $this->queryGoodsRanking($where, $topType, $pageNum);

        if ($list) {
            $lang = input("lang");
            if ($lang) {
                if ($pageNum) {
                    $list = $list->each(function ($item) use ($lang) {
                        $gl = $this->getGoodsLangFind(['lang' => $lang, 'g_id' => $item['g_id']], 0, 'g_name');
                        if ($gl) {
                            $item['g_name'] = $gl['g_name'];
                        }
                        return $item;
                    });
                } else {
                    $list = $list->toArray();
                    foreach ($list as $key => $value) {
                        $gl = $this->getGoodsLangFind(['lang' => $lang, 'g_id' => $value['g_id']], 0, 'g_name');
                        if ($gl) {
                            $value['g_name'] = $gl['g_name'];
                        }
                        $list[$key] = $value;
                    }
                }
            }
        }

        return $this->rQ($list);
    }

    
    /**
     * 导出商品排行榜（V2）
     * @param array $where
     * @return array|\think\response\Json|string
     */
    public function exportRankingListV2($where, $topType = 1)
    {
        if($this->manager['account']=='meichitu'){
            $where[] = ['gc_name','like','%美驰图%'];
        }

        $list = $this->queryGoodsRanking($where, $topType, 0);
        if ($list) {
            $list = $list->toArray();
            $title = [
                "g_name" => $this->lang("export.g_name"),
                "totalPrice" => $this->lang("export.totalPrice"),
                "totalQuantity" => $this->lang("export.totalQuantity"),
                "totalDiscountPrice" => "优惠金额",
                "totalRefundAmount" => "退款金额",
                "totalRefundQuantity" => "退款数量",
                "retail_price" => $this->lang("export.retail_price"),
            ];
            $filename = $this->lang("export.goodsTopList") . date("Ymd");
            $result = $this->sendToExport($this->lang("export.goodsTopRankFileName"), $filename, $title, $list);
            return $result;
        }
        return $this->rQ($list);
    }

    /**
     * 商品排行榜聚合查询（基于订单明细，不依赖统计视图）
     * @param array $where
     * @param int $topType
     * @param int $pageNum
     * @return \think\Collection|\think\Paginator
     */
    private function queryGoodsRanking($where, $topType = 1, $pageNum = 0)
    {
        $order = 'totalPrice desc,totalQuantity desc,g_id desc';
        if ($topType == 2) {
            $order = 'totalQuantity desc,totalPrice desc,g_id desc';
        }

        $query = Db::name('sale_orders_details')->alias('sod')
            ->join('sale_orders so', 'so.order_id = sod.order_id')
            ->where('so.pay_status', 3)
            ->field([
                'sod.g_id' => 'g_id',
                'MAX(sod.g_name)' => 'g_name',
                'MAX(sod.pic)' => 'pic',
                'MAX(sod.sku)' => 'sku',
                'MAX(sod.gc_id)' => 'gc_id',
                'MAX(sod.gc_name)' => 'gc_name',
                'ROUND(MAX(sod.cost_price),2)' => 'cost_price',
                'ROUND(MAX(sod.market_price),2)' => 'market_price',
                'ROUND(MAX(sod.retail_price),2)' => 'retail_price',
                'ROUND(SUM(sod.total_sod_price),2)' => 'totalPrice',
                'SUM(sod.quantity)' => 'totalQuantity',
                'ROUND(SUM(IFNULL(sod.refund_amount,0)),2)' => 'totalRefundAmount',
                'SUM(IFNULL(sod.refund_quantity,0))' => 'totalRefundQuantity',
                'ROUND(SUM(sod.discount_price),2)' => 'totalDiscountPrice',
            ])
            ->group('sod.g_id')
            ->orderRaw($order);

        $this->applyGoodsRankingWhere($query, $where);

        if ($pageNum) {
            return $query->paginate($pageNum, false, ["query" => request()->param()]);
        }
        return $query->select();
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
        }
    }
    
}