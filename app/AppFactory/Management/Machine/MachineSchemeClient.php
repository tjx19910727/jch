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
use app\AppFactory\Kernel\Traits\Machine\MachineChannelTrait;
use app\AppFactory\Kernel\Service\Machine\RecommendSchemeService;

class MachineSchemeClient extends ManagementClient
{
    use MachineChannelSchemeTrait;
    use MachineChannelTrait;

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

        // 检查是否有已存储的待确认方案
        $existScheme = $this->getMachineChannelSchemeFind([
            'm_id' => $mId,
            'mlm_id' => $mlmId,
            'priority_type' => $priorityType,
            'status' => 1,
        ]);
        if ($existScheme) {
            $mcsId = intval($existScheme['mcs_id']);
            $details = $this->getMachineChannelSchemeDetailList(['mcs_id' => $mcsId])->toArray();
            return $this->r(200, "查询成功（已有方案）", [
                'mcs_id' => $mcsId,
                'scheme' => $existScheme,
                'details' => $details,
            ]);
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
     * 确认方案 - 写入machine_channel表
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

        $mId = intval($scheme['m_id']);

        $this->startTrans();
        try {
            // 更新machine_channel表
            foreach ($details as $d) {
                $channelCode = trim($d['channel_code']);
                $gId = intval($d['g_id']);
                $quantity = intval($d['quantity']);

                // 根据m_id和channel_code找到mc_id
                $mc = $this->getMachineChannelFind([
                    'm_id' => $mId,
                    'channel_code' => $channelCode,
                ], 'mc_id');
                if (!$mc) continue;

                $this->updateMachineChannel([
                    'g_id' => $gId,
                    'retail_price' => floatval($d['retail_price']),
                ], ['mc_id' => intval($mc['mc_id'])]);
            }

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