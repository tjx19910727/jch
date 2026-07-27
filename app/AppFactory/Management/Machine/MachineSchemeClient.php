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
use app\AppFactory\Kernel\Traits\Machine\MachineGoodsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsChangeTrait;
use app\AppFactory\Kernel\Service\Machine\RecommendSchemeService;

class MachineSchemeClient extends ManagementClient
{
    use MachineChannelSchemeTrait;
    use MachineGoodsTrait;
    use MachineTrait;
    use GoodsTrait;
    use GoodsChangeTrait;

    /**
     * 根据布局模板和外部商品列表生成推荐预览，不绑定设备、不保存方案。
     */
    public function getRecommendScheme()
    {
        $postData = input();
        $mlmId = intval($postData['mlm_id'] ?? 0);
        $priorityType = 'amount';
        $goodsListInput = $postData['goods_list'] ?? ($postData['goods_lists'] ?? null);
        $goodsList = $this->normalizeRecommendGoodsLists($goodsListInput);

        if (!$mlmId) {
            return $this->rFail("请提交mlm_id参数");
        }
        if ($goodsList === false) {
            return $this->rFail("goods_list格式错误");
        }
        if (!$goodsList) {
            return $this->rFail("请提交goods_list参数");
        }

        $service = new RecommendSchemeService();
        $goodsDetails = $service->getGoodsDetails($goodsList);
        if (!$goodsDetails) {
            return $this->rFail("商品不存在或ID无效");
        }

        $schemeDetails = $service->calculate($mlmId, $goodsDetails, $priorityType);
        if ($schemeDetails === false) {
            return $this->rFail($service->getError());
        }
        if (!$schemeDetails) {
            return $this->rFail("未生成可用推荐方案");
        }

        $skippedGoods = $service->getSkippedGoods();
        $totalGoods = 0;
        $totalAmount = 0;
        $totalCost = 0;
        $skuSet = [];
        foreach ($schemeDetails as $d) {
            $totalGoods += intval($d['quantity']);
            $totalAmount += floatval($d['total_amount']);
            $totalCost += floatval($d['total_cost']);
            $skuSet[intval($d['g_id'])] = true;
        }
        $totalChannels = $service->getLayoutChannelCount();
        $assignedChannels = count($schemeDetails);

        $responseData = [
            'priority_type' => $priorityType,
            'scheme' => [
                'total_goods' => $totalGoods,
                'total_quantity' => $totalGoods,
                'total_amount' => round($totalAmount, 2),
                'total_cost' => round($totalCost, 2),
                'total_sku' => count($skuSet),
                'total_channels' => $totalChannels,
                'assigned_channels' => $assignedChannels,
                'unassigned_channels' => max(0, $totalChannels - $assignedChannels),
            ],
            'details' => $schemeDetails,
        ];

        if (!empty($skippedGoods)) {
            $responseData['warn_skipped_goods'] = $skippedGoods;
        }

        return $this->r(200, "推荐方案计算成功", $responseData);
    }

    protected function normalizeRecommendGoodsLists($goodsLists)
    {
        if (is_string($goodsLists)) {
            if ($goodsLists === '') {
                return [];
            }
            $goodsLists = json_decode($goodsLists, true);
            if (!is_array($goodsLists)) {
                return false;
            }
        }
        if (!is_array($goodsLists)) {
            return false;
        }

        $gIds = [];
        foreach ($goodsLists as $gId) {
            if (is_array($gId) || is_object($gId) || intval($gId) <= 0) {
                return false;
            }
            $gIds[] = intval($gId);
        }
        return array_values(array_unique($gIds));
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
     * 方案真实上架 - 待确认方案可直接执行，已确认方案保持兼容。
     */
    public function applyScheme()
    {
        $mcsId = intval(input('mcs_id'));
        if (!$mcsId) return $this->rFail("方案ID不能为空");

        $scheme = $this->getMachineChannelSchemeFind(['mcs_id' => $mcsId]);
        if (!$scheme) return $this->rFail("方案不存在");
        if (!in_array(intval($scheme['status']), [1, 2], true)) return $this->rFail("方案状态不允许真实上架");

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

            // 标记为已上架，避免同一方案重复执行并重复写入库存变更记录。
            $this->updateMachineChannelScheme([
                'status' => 4,
                'update_time' => time(),
            ], ['mcs_id' => $mcsId]);
            $this->updateMachineChannelSchemeDetailStatus($mcsId, 4);

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
