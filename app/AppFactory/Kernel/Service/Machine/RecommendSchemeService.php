<?php

namespace app\AppFactory\Kernel\Service\Machine;

use app\AppFactory\Kernel\Model\Machine\MachineLayoutDetailModel;
use app\AppFactory\Kernel\Model\Machine\MachineLayoutModelModel;
use app\AppFactory\Kernel\Model\Goods\GoodsModel;
use app\AppFactory\Kernel\Model\Machine\MachineModel;
use app\AppFactory\Kernel\Model\Machine\MachineChannelModel;
use think\facade\Db;

class RecommendSchemeService
{
    protected $error = '';
    protected $skippedGoods = []; // 记录被跳过的商品
    protected $layoutChannelCount = 0;

    /**
     * 计算推荐上架方案
     * @param int $mlmId 布局模板ID
     * @param array $goodsList 商品列表 [{g_id, cost_price, retail_price, length, width, height, g_name, sku}]
     * @param string $priorityType 优先级: amount/sku/quantity
     * @return array|false
     */
    public function calculate($mlmId, $goodsList, $priorityType = 'amount')
    {
        $this->skippedGoods = [];
        $this->layoutChannelCount = 0;

        // 1. 获取货道明细
        $details = MachineLayoutDetailModel::where(['mlm_id' => intval($mlmId)])
            ->order('row_index asc,col_index asc')
            ->select()
            ->toArray();
        if (!$details) {
            $this->error = '布局模板未生成货道明细';
            return false;
        }
        $this->layoutChannelCount = count($details);

        // 2. 获取布局模型，使用模板配置的货道可用深度计算容量。
        $model = MachineLayoutModelModel::getFind(
            ['mlm_id' => intval($mlmId)],
            'mlm_id,channel_depth'
        );
        if (!$model) {
            $this->error = '布局模板不存在';
            return false;
        }
        $channelDepth = floatval($model['channel_depth'] ?? 0);
        if ($channelDepth <= 0) {
            $this->error = '布局模板货道深度未配置';
            return false;
        }

        // 3. 为每个商品计算兼容的货道
        $compatibleMap = []; // g_id => [{mld_id, channel_code, max_qty, pos_x, pos_y}]
        foreach ($goodsList as $goods) {
            $gId = intval($goods['g_id']);
            $goodsW = floatval($goods['width'] ?? 0);
            $goodsH = floatval($goods['height'] ?? 0);
            $goodsL = floatval($goods['length'] ?? 0);

            if ($goodsW <= 0 || $goodsH <= 0 || $goodsL <= 0) {
                $this->skippedGoods[] = [
                    'g_id' => $gId,
                    'g_name' => $goods['g_name'] ?? '未知',
                    'sku' => $goods['sku'] ?? '',
                    'pic' => $goods['pic'] ?? '',
                    'reason' => '长宽高未配置或为0（length=' . $goodsL . ', width=' . $goodsW . ', height=' . $goodsH . '）',
                ];
                continue;
            }

            $maxQty = $this->calculateChannelCapacity($channelDepth, $goodsL);
            if ($maxQty <= 0) {
                $this->skippedGoods[] = [
                    'g_id' => $gId,
                    'g_name' => $goods['g_name'] ?? '未知',
                    'sku' => $goods['sku'] ?? '',
                    'pic' => $goods['pic'] ?? '',
                    'reason' => '商品长度超过模板货道可用深度（length='
                        . $goodsL . ', channel_depth=' . $channelDepth . '）',
                ];
                continue;
            }

            foreach ($details as $detail) {
                $chW = floatval($detail['actual_width'] ?? 0);
                $chH = floatval($detail['actual_height'] ?? 0);

                // 商品宽高不能超过货道宽高
                if ($goodsW > $chW || $goodsH > $chH) {
                    continue;
                }

                $compatibleMap[$gId][] = [
                    'mld_id' => intval($detail['mld_id']),
                    'channel_code' => $detail['channel_code'] ?? '',
                    'max_qty' => $maxQty,
                    'pos_x' => floatval($detail['pos_x'] ?? 0),
                    'pos_y' => floatval($detail['pos_y'] ?? 0),
                    'actual_width' => $chW,
                    'actual_height' => $chH,
                    'channel_depth' => $channelDepth,
                ];
            }
        }

        if (!$compatibleMap) {
            $this->error = '所选商品无法适配任何货道';
            return false;
        }

        // 4. 按优先级排序分配
        $schemeDetails = $this->allocateByPriority($goodsList, $compatibleMap, $priorityType);

        return $schemeDetails;
    }

    /**
     * 根据模板货道可用深度计算单货道理论容量。
     */
    protected function calculateChannelCapacity($channelDepth, $goodsLength)
    {
        $channelDepth = floatval($channelDepth);
        $goodsLength = floatval($goodsLength);
        if ($channelDepth <= 0 || $goodsLength <= 0) {
            return 0;
        }
        return intval(floor($channelDepth / $goodsLength));
    }

    /**
     * 按优先级分配货道
     */
    protected function allocateByPriority($goodsList, $compatibleMap, $priorityType)
    {
        // 标记已被占用的货道mld_id集合
        $usedMldIds = [];

        // 对商品排序
        $sortedGoods = [];
        foreach ($goodsList as $goods) {
            $gId = intval($goods['g_id']);
            $compatibles = $compatibleMap[$gId] ?? [];
            if (!$compatibles) continue;
            // 过滤已占用货道
            $availCompatibles = [];
            foreach ($compatibles as $c) {
                if (!in_array($c['mld_id'], $usedMldIds)) {
                    $availCompatibles[] = $c;
                }
            }
            if (!$availCompatibles) continue;
            $sortedGoods[] = [
                'g_id' => $gId,
                'g_name' => $goods['g_name'] ?? '',
                'sku' => $goods['sku'] ?? '',
                'pic' => $goods['pic'] ?? '',
                'cost_price' => floatval($goods['cost_price'] ?? 0),
                'retail_price' => floatval($goods['retail_price'] ?? 0),
                'length' => floatval($goods['length'] ?? 0),
                'width' => floatval($goods['width'] ?? 0),
                'height' => floatval($goods['height'] ?? 0),
                'compatibles' => $availCompatibles,
            ];
        }

        // 根据优先级排序
        if ($priorityType === 'amount') {
            // 金额优先：高单价优先
            usort($sortedGoods, function ($a, $b) {
                return $b['retail_price'] <=> $a['retail_price'];
            });
        } elseif ($priorityType === 'quantity') {
            // 数量优先：可兼容货道多的优先
            usort($sortedGoods, function ($a, $b) {
                return count($b['compatibles']) <=> count($a['compatibles']);
            });
        } else {
            // sku优先：SKU数量优先（尽量覆盖更多品类）
            // 按兼容货道少的优先分配（先难后易）
            usort($sortedGoods, function ($a, $b) {
                $c = count($a['compatibles']) <=> count($b['compatibles']);
                return $c;
            });
        }

        $result = [];

        // 按优先级轮询商品，直到所有可适配货道都被铺满。
        do {
            $allocatedInRound = false;
            foreach ($sortedGoods as $item) {
                foreach ($item['compatibles'] as $chosen) {
                    if (in_array($chosen['mld_id'], $usedMldIds, true)) {
                        continue;
                    }

                    $planQty = intval($chosen['max_qty']);
                    if ($planQty <= 0) {
                        continue;
                    }

                    $usedMldIds[] = $chosen['mld_id'];
                    $allocatedInRound = true;
                    $result[] = [
                        'mld_id' => $chosen['mld_id'],
                        'channel_code' => $chosen['channel_code'],
                        'g_id' => $item['g_id'],
                        'g_name' => $item['g_name'],
                        'sku' => $item['sku'],
                        'pic' => $item['pic'],
                        'cost_price' => $item['cost_price'],
                        'retail_price' => $item['retail_price'],
                        'length' => $item['length'],
                        'width' => $item['width'],
                        'height' => $item['height'],
                        'quantity' => $planQty,
                        'total_cost' => round($item['cost_price'] * $planQty, 2),
                        'total_amount' => round($item['retail_price'] * $planQty, 2),
                        'channel_width' => $chosen['actual_width'],
                        'channel_height' => $chosen['actual_height'],
                        'channel_depth' => $chosen['channel_depth'],
                        'pos_x' => $chosen['pos_x'],
                        'pos_y' => $chosen['pos_y'],
                    ];
                    break;
                }
            }
        } while ($allocatedInRound && count($usedMldIds) < $this->layoutChannelCount);

        return $result;
    }

    /**
     * 获取商品详情（从goods表查询）
     */
    public function getGoodsDetails($goodsList)
    {
        $gIds = [];
        foreach ($goodsList as $gId) {
            $gId = intval($gId);
            if ($gId > 0) {
                $gIds[] = $gId;
            }
        }
        if (!$gIds) return [];

        $goods = GoodsModel::whereIn('g_id', $gIds)
            ->field('g_id,g_name,sku,pic,cost_price,retail_price,length,width,height')
            ->select()
            ->toArray();

        $result = [];
        foreach ($goods as $g) {
            $gId = intval($g['g_id']);
            $result[] = [
                'g_id' => $gId,
                'g_name' => $g['g_name'] ?? '',
                'sku' => $g['sku'] ?? '',
                'pic' => $g['pic'] ?? '',
                'cost_price' => floatval($g['cost_price'] ?? 0),
                'retail_price' => floatval($g['retail_price'] ?? 0),
                'length' => floatval($g['length'] ?? 0),
                'width' => floatval($g['width'] ?? 0),
                'height' => floatval($g['height'] ?? 0),
            ];
        }
        return $result;
    }

    public function getError()
    {
        return $this->error;
    }

    /**
     * 获取被跳过的商品列表
     * @return array
     */
    public function getSkippedGoods()
    {
        return $this->skippedGoods;
    }

    public function getLayoutChannelCount()
    {
        return $this->layoutChannelCount;
    }
}
