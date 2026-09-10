<?php

namespace app\AppFactory\Kernel\Service\Api;

/**
 * 构造微程 msvc-shop 同步请求（已按接收方实测口径实现）。
 *
 * - 核心商品 syncGoods：扁平字段 + MD5(apisecret + product_id + apisecret)
 * - 设备商品 syncMachineProduct：扁平行级字段（一台售卖机 + 一个商品/货道一条），
 *   签名 MD5(apisecret + machine_id + product_id + apisecret)
 *
 * 说明：设备商品是“明细级”接口，dispatch 时按货道逐条生成 api_callback。
 */
class ThirdPartyProductSyncPayloadBuilder
{
    private $secret;

    public function __construct($secret)
    {
        $this->secret = (string)$secret;
    }

    /**
     * syncGoods 请求体（扁平字段，sign 为 MD5）。
     *
     * 文档 syncGoods 只做“修改”，没有删除语义；本地删除/下架商品时
     * 以 status=0 表达（已与接收方确认：后台删除视为下架推送）。
     */
    public function buildSyncGoods($productId, array $goods, $operation = 'upsert')
    {
        $productId = intval($productId);
        $delete = $operation === 'delete' || !$goods;
        $body = [
            'product_id' => (string)$productId,
        ];

        if ($delete) {
            $body['status'] = '0';
        } else {
            foreach (['g_name', 'cost_price', 'retail_price', 'market_price', 'status', 'sku', 'bar_code', 'gc_id', 'gc_name'] as $field) {
                if (!array_key_exists($field, $goods)) {
                    continue;
                }
                $value = is_scalar($goods[$field]) || $goods[$field] === null
                    ? (string)($goods[$field] ?? '')
                    : json_encode($goods[$field], JSON_UNESCAPED_UNICODE);
                if ($value !== '' && $value !== null) {
                    $body[$field] = $value;
                }
            }
            if (array_key_exists('desc', $goods) && (string)($goods['desc'] ?? '') !== '') {
                $body['desc'] = (string)$goods['desc'];
            }
            if (!isset($body['status'])) {
                $body['status'] = '0';
            }
        }

        $body['sign'] = $this->makeSyncGoodsSign($productId);
        return $body;
    }

    /**
     * syncGoods 签名：MD5(apisecret + product_id + apisecret)，32 位小写。
     */
    public function makeSyncGoodsSign($productId)
    {
        $productId = trim((string)$productId);
        return md5($this->secret . $productId . $this->secret);
    }

    /**
     * 设备商品单条明细报文（扁平行级，对接 syncMachineProduct）。
     *
     * 字段与签名口径已按接收方实测通过：一台售卖机 + 一个商品（货道）= 一条请求。
     *
     * @param array  $item      货道快照（ThirdPartyProductSnapshotService::getMachineInventorySnapshot）
     * @param string $machineId 微程侧售卖机编号
     * @return array|null       无有效商品时返回 null（调用方跳过）
     */
    public function buildMachineProduct(array $item, $machineId)
    {
        $machineId = trim((string)$machineId);
        $productId = (string)intval($item['product_id'] ?? 0);
        if ($machineId === '' || $productId === '0') {
            return null;
        }
        $stock = intval($item['quantity'] ?? ($item['stock'] ?? 0));

        $body = [
            'machine_id' => $machineId,
            'channel_code' => (string)($item['channel_code'] ?? ''),
            'product_id' => $productId,
            'out_no' => (string)($item['out_no'] ?? $productId),
            'g_id' => $productId,
            'sku' => (string)($item['sku'] ?? ''),
            'bar_code' => (string)($item['bar_code'] ?? ''),
            'g_name' => (string)($item['g_name'] ?? ''),
            'quantity' => $stock,
            'stock' => $stock,
            'sale_price' => (string)($item['sale_price'] ?? ''),
            'market_price' => (string)($item['market_price'] ?? ''),
            'cost_price' => (string)($item['cost_price'] ?? ''),
            'status' => (string)($item['status'] ?? ''),
        ];
        $body['sign'] = $this->makeMachineProductSign($machineId, $productId);

        return $body;
    }

    /**
     * 批量构造某台设备的货道明细报文（自动跳过无商品货道）。
     *
     * @return array 明细报文列表
     */
    public function buildMachineProducts($machineId, array $items)
    {
        $payloads = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $payload = $this->buildMachineProduct($item, $machineId);
            if ($payload !== null) {
                $payloads[] = $payload;
            }
        }
        return $payloads;
    }

    /**
     * 设备商品签名：MD5(apisecret + machine_id + product_id + apisecret)，32 位小写。
     */
    public function makeMachineProductSign($machineId, $productId)
    {
        return md5($this->secret . trim((string)$machineId) . trim((string)$productId) . $this->secret);
    }
}

