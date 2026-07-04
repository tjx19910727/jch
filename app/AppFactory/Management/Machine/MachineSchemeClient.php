<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/7/1
 * Time: 19:11
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Management\ManagementClient;
use app\AppFactory\Kernel\Traits\Machine\MachineChannelSchemeTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineLevelLayoutRelTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineGoodsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsChangeTrait;
use app\AppFactory\Kernel\Service\Machine\RecommendSchemeService;

class MachineSchemeClient extends ManagementClient
{
    use MachineChannelSchemeTrait;
    use MachineLevelLayoutRelTrait;
    use MachineGoodsTrait;
    use MachineTrait;
    use GoodsTrait;
    use GoodsChangeTrait;

    /**
     * 获取推荐上架方案（已存储则直接返回，否则计算后存储）
     */
    public function getRecommendScheme()
    {
        $postData = input();
        $mId = intval($postData['m_id'] ?? 0);
        $mlmId = intval($postData['mlm_id'] ?? 0);
        $machineId = trim($postData['machine_id'] ?? '');
        $priorityType = in_array($postData['priority_type'] ?? '', ['amount', 'sku', 'quantity'])
            ? $postData['priority_type'] : 'amount';
        $goodsList = $postData['goods_list'] ?? [];

        if (!$mId || !$mlmId || !$goodsList) {
            return $this->rFail("参数不完整");
        }
        if (!is_array($goodsList)) {
            return $this->rFail("goods_list格式错误");
        }

        $machineLevel = $this->getMachineValue(['m_id' => $mId], 'machine_level');
        if (!$machineLevel) {
            return $this->rFail("设备不存在或未配置设备等级");
        }
        $rel = $this->getMachineLevelLayoutRelFind([
            'machine_level' => intval($machineLevel),
            'mlm_id' => $mlmId,
        ], 'id');
        if (!$rel) {
            return $this->rFail("布局模板不属于当前设备等级");
        }

        // 同一设备、模板、优先级重新生成方案时，取消旧待确认方案，避免 goods_list 变化后复用旧结果。
        $existScheme = $this->getMachineChannelSchemeFind([
            'm_id' => $mId,
            'mlm_id' => $mlmId,
            'priority_type' => $priorityType,
            'status' => 1,
        ]);
        if ($existScheme) {
            $mcsId = intval($existScheme['mcs_id']);
            $this->updateMachineChannelScheme([
                'status' => 3,
                'update_time' => time(),
            ], ['mcs_id' => $mcsId]);
            $this->updateMachineChannelSchemeDetailStatus($mcsId, 3);
        }

        // 调用算法服务计算
        $service = new RecommendSchemeService();
        $goodsDetails = $service->getGoodsDetails($goodsList);
        if (!$goodsDetails) {
            return $this->rFail("商品不存在或ID无效");
        }

        $schemeDetails = $service->calculate($mId, $mlmId, $goodsDetails, $priorityType);
        if ($schemeDetails === false) {
            return $this->rFail($service->getError());
        }

        // 获取被跳过的商品（无长宽高）
        $skippedGoods = $service->getSkippedGoods();

        // 统计汇总
        $totalGoods = 0;
        $totalAmount = 0;
        $skuSet = [];
        foreach ($schemeDetails as $d) {
            $totalGoods += intval($d['quantity']);
            $totalAmount += floatval($d['total_amount']);
            $skuSet[intval($d['g_id'])] = true;
        }

        // 存储方案主表（含跳过的商品信息）
        $saveData = [
            'machine_id' => $machineId,
            'm_id' => $mId,
            'mlm_id' => $mlmId,
            'scheme_name' => '推荐方案-' . date('YmdHis'),
            'priority_type' => $priorityType,
            'total_goods' => $totalGoods,
            'total_amount' => round($totalAmount, 2),
            'total_sku' => count($skuSet),
            'status' => 1,
            'create_time' => time(),
            'update_time' => time(),
        ];
        if (!empty($skippedGoods)) {
            $saveData['skipped_goods'] = json_encode($skippedGoods, JSON_UNESCAPED_UNICODE);
        }
        $mcsId = $this->addMachineChannelScheme($saveData);

        // 存储方案明细
        $insertAll = [];
        $now = time();
        foreach ($schemeDetails as $d) {
            $insertAll[] = [
                'mcs_id' => $mcsId,
                'mld_id' => intval($d['mld_id']),
                'channel_code' => $d['channel_code'],
                'g_id' => intval($d['g_id']),
                'g_name' => $d['g_name'],
                'sku' => $d['sku'],
                'retail_price' => floatval($d['retail_price']),
                'quantity' => intval($d['quantity']),
                'total_amount' => round(floatval($d['total_amount']), 2),
                'pos_x' => floatval($d['pos_x']),
                'pos_y' => floatval($d['pos_y']),
                'status' => 1,
                'create_time' => $now,
                'update_time' => $now,
            ];
        }
        $this->addMachineChannelSchemeDetailAll($insertAll);

        $responseData = [
            'mcs_id' => $mcsId,
            'scheme' => [
                'total_goods' => $totalGoods,
                'total_amount' => round($totalAmount, 2),
                'total_sku' => count($skuSet),
            ],
            'details' => $schemeDetails,
        ];

        // 如有被跳过的商品（无长宽高），附带提示
        if (!empty($skippedGoods)) {
            $responseData['warn_skipped_goods'] = $skippedGoods;
        }

        return $this->r(200, "方案生成成功", $responseData);
    }

    /**
     * 获取方案列表
     */
    public function getList($where = [], $pageNum = 0, $field = "*", $order = "mcs_id desc", $rQ = 1)
    {
        $postData = input();
        if (!$where && $postData) {
            $pageNum = $postData['pageNum'] ?? 0;
            if (!empty($postData['machine_id'])) {
                $where[] = ['machine_id', 'like', '%' . $postData['machine_id'] . '%'];
            }
            if (!empty($postData['status'])) {
                $where[] = ['status', '=', intval($postData['status'])];
            }
        }
        $data = $this->getMachineChannelSchemeList($where, $pageNum, $field, $order);
        return $this->rQ($data);
    }

    /**
     * 获取方案详情
     */
    public function getDetail()
    {
        $mcsId = intval(input('mcs_id'));
        if (!$mcsId) return $this->rFail("方案ID不能为空");

        $scheme = $this->getMachineChannelSchemeFind(['mcs_id' => $mcsId]);
        if (!$scheme) return $this->rFail("方案不存在");

        $details = $this->getMachineChannelSchemeDetailList(['mcs_id' => $mcsId])->toArray();

        // 解析被跳过的商品JSON
        $skippedGoods = [];
        if (!empty($scheme['skipped_goods'])) {
            $decoded = json_decode($scheme['skipped_goods'], true);
            if (is_array($decoded)) {
                $skippedGoods = $decoded;
            }
        }

        $result = [
            'scheme' => $scheme,
            'details' => $details,
        ];
        if (!empty($skippedGoods)) {
            $result['warn_skipped_goods'] = $skippedGoods;
        }

        return $this->r(200, "查询成功", $result);
    }

    /**
     * 确认方案 - 只保存方案状态，不执行真实上架。
     */
    public function confirmScheme()
    {
        $mcsId = intval(input('mcs_id'));
        if (!$mcsId) return $this->rFail("方案ID不能为空");

        $scheme = $this->getMachineChannelSchemeFind(['mcs_id' => $mcsId]);
        if (!$scheme) return $this->rFail("方案不存在");
        if (intval($scheme['status']) !== 1) return $this->rFail("方案状态不允许确认");

        $details = $this->getMachineChannelSchemeDetailList(['mcs_id' => $mcsId])->toArray();
        if (!$details) return $this->rFail("方案明细为空");

        $this->startTrans();
        try {
            // 更新方案状态为已确认
            $this->updateMachineChannelScheme([
                'status' => 2,
                'update_time' => time(),
            ], ['mcs_id' => $mcsId]);

            // 更新所有明细状态为已确认
            $this->updateMachineChannelSchemeDetailStatus($mcsId, 2);

            $this->commitTrans();
            return $this->rSuccess();
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rFail($e->getMessage());
        }
    }

    /**
     * 方案真实上架 - 将已确认方案写入真实货道。
     */
    public function applyScheme()
    {
        $mcsId = intval(input('mcs_id'));
        if (!$mcsId) return $this->rFail("方案ID不能为空");

        $scheme = $this->getMachineChannelSchemeFind(['mcs_id' => $mcsId]);
        if (!$scheme) return $this->rFail("方案不存在");
        if (intval($scheme['status']) !== 2) return $this->rFail("方案状态不允许真实上架");

        $details = $this->getMachineChannelSchemeDetailList(['mcs_id' => $mcsId])->toArray();
        if (!$details) return $this->rFail("方案明细为空");

        $mId = intval($scheme['m_id']);
        $machine = $this->getMachineFind(['m_id' => $mId], 'm_id,machine_id,machine_name,ao_id');
        if (!$machine) return $this->rFail("设备不存在");
        $machine = $machine->toArray();

        $missingChannels = [];
        $applyRows = [];
        foreach ($details as $d) {
            $channelCode = trim($d['channel_code']);
            $mc = $this->getMachineChannelFind([
                'm_id' => $mId,
                'channel_code' => $channelCode,
            ], 'mc_id,m_id,machine_id,channel_position,channel_code,mg_id,g_id,g_name,gc_id,gc_name,pic,sku,bar_code,stock,out_fail_stock,status');
            if (!$mc) {
                $missingChannels[] = $channelCode;
                continue;
            }
            $goods = $this->getGoodsFind(['g_id' => intval($d['g_id'])], 'g_id,g_name,gc_id,gc_name,pic,sku,bar_code,cost_price,market_price,retail_price');
            if (!$goods) {
                return $this->rFail("方案商品不存在：" . intval($d['g_id']));
            }
            $applyRows[] = [
                'detail' => $d,
                'mc' => $mc->toArray(),
                'goods' => $goods->toArray(),
            ];
        }
        if (!empty($missingChannels)) {
            return $this->rFail("方案货道不存在：" . implode(",", $missingChannels));
        }

        $this->startTrans();
        try {
            $sendList = [];
            foreach ($applyRows as $row) {
                $d = $row['detail'];
                $mc = $row['mc'];
                $goods = $row['goods'];
                $gId = intval($goods['g_id']);
                $oldGId = intval($mc['g_id'] ?? 0);
                $quantity = intval($d['quantity']);

                $baseChange = [
                    "m_id" => $machine['m_id'],
                    "machine_id" => $machine['machine_id'],
                    "machine_name" => $machine['machine_name'],
                    "mc_id" => $mc['mc_id'],
                    "channel_code" => $mc['channel_code'],
                    "mg_id" => $mc['mg_id'] ?? 0,
                    "g_id" => $mc['g_id'] ?? 0,
                    "g_name" => $mc['g_name'] ?? '',
                    "gc_id" => $mc['gc_id'] ?? 0,
                    "gc_name" => $mc['gc_name'] ?? '',
                    "pic" => $mc['pic'] ?? '',
                    "sku" => $mc['sku'] ?? '',
                    "bar_code" => $mc['bar_code'] ?? '',
                    "ao_id" => $machine['ao_id'],
                ];
                if ($oldGId > 0 && $oldGId !== $gId) {
                    $this->addGoodsChange(array_merge($baseChange, [
                        "change_value" => $mc['stock'] ?? 0,
                        "type" => 7,
                        "desc" => $this->lang("goodsChange.backstage_exchange_mc_under_old"),
                        "position" => 1,
                    ]));
                }

                $mgId = $this->getMachineGoodsValue(['m_id' => $mId, 'g_id' => $gId], 'mg_id') ?? 0;
                $this->updateMachineChannel([
                    'g_id' => $gId,
                    'mg_id' => $mgId,
                    'g_name' => $goods['g_name'] ?? '',
                    'gc_id' => $goods['gc_id'] ?? 0,
                    'gc_name' => $goods['gc_name'] ?? '',
                    'pic' => $goods['pic'] ?? '',
                    'sku' => $goods['sku'] ?? '',
                    'bar_code' => $goods['bar_code'] ?? '',
                    'cost_price' => $goods['cost_price'] ?? 0,
                    'market_price' => $goods['market_price'] ?? 0,
                    'retail_price' => floatval($d['retail_price']),
                    'stock' => $quantity,
                    'out_fail_stock' => 0,
                    'status' => 1,
                    'update_time' => time(),
                ], ['mc_id' => intval($mc['mc_id'])]);

                $this->addGoodsChange(array_merge($baseChange, [
                    "mg_id" => $mgId,
                    "g_id" => $gId,
                    "g_name" => $goods['g_name'] ?? '',
                    "gc_id" => $goods['gc_id'] ?? 0,
                    "gc_name" => $goods['gc_name'] ?? '',
                    "pic" => $goods['pic'] ?? '',
                    "sku" => $goods['sku'] ?? '',
                    "bar_code" => $goods['bar_code'] ?? '',
                    "change_value" => $quantity,
                    "type" => 6,
                    "desc" => $this->lang("goodsChange.backstage_exchange_mc_display_new"),
                    "position" => 1,
                ]));

                if (intval($mc['channel_position']) != 3) {
                    $sendList[] = [
                        'machine_id' => $mc['machine_id'],
                        'mc_id' => intval($mc['mc_id']),
                    ];
                }
            }

            $this->commitTrans();
            foreach ($sendList as $send) {
                $this->sendToMachine(['machine_id' => $send['machine_id']], 'updateMc', ['mc_id' => $send['mc_id']]);
            }
            return $this->rSuccess();
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rFail($e->getMessage());
        }
    }

    /**
     * 取消方案
     */
    public function cancelScheme()
    {
        $mcsId = intval(input('mcs_id'));
        if (!$mcsId) return $this->rFail("方案ID不能为空");

        $scheme = $this->getMachineChannelSchemeFind(['mcs_id' => $mcsId]);
        if (!$scheme) return $this->rFail("方案不存在");
        if (intval($scheme['status']) !== 1) return $this->rFail("方案状态不允许取消");

        $this->updateMachineChannelScheme([
            'status' => 3,
            'update_time' => time(),
        ], ['mcs_id' => $mcsId]);

        $this->updateMachineChannelSchemeDetailStatus($mcsId, 3);

        return $this->rSuccess();
    }

    /**
     * 批量更新方案明细状态
     */
    protected function updateMachineChannelSchemeDetailStatus($mcsId, $status)
    {
        $model = new \app\AppFactory\Kernel\Model\Machine\MachineChannelSchemeDetailModel();
        return $model->where(['mcs_id' => intval($mcsId)])->update([
            'status' => intval($status),
            'update_time' => time(),
        ]);
    }
}
