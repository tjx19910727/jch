<?php

namespace app\AppFactory\Kernel\Service\Api;

/**
 * 构造第三方商品同步请求。
 *
 * - 核心商品：对接微程 msvc-shop「嘉潮汇商品同步推送」syncGoods，
 *   扁平字段 + MD5(apisecret + product_id + apisecret) 签名。
 * - 设备商品：syncMachineProduct 的请求/签名结构待第三方文档确认，
 *   暂保留全量快照信封用于本地联调，接入真实地址前必须按文档替换。
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
     * 文档 syncGoods 只做“修改”，没有删除语义；本地删除商品时
     * 以 status=0 表达下架，需与接收方确认。
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
     * 设备商品完整快照信封（syncMachineProduct 文档确认前的过渡实现）。
     */
    public function buildMachineInventory($machineId, array $items, $version, $eventId, $timestamp)
    {
        return $this->buildEnvelope('machine_inventory.sync', [
            'machine_id' => (string)$machineId,
            'sync_mode' => 'snapshot',
            'items' => array_values($items),
        ], $version, $eventId, $timestamp);
    }

    /**
     * 信封签名（旧过渡协议的 HMAC-SHA256，待 syncMachineProduct 文档确认后移除）。
     */
    public function makeSign(array $payload)
    {
        unset($payload['sign']);
        return hash_hmac('sha256', $this->canonicalJson($payload), $this->secret);
    }

    private function buildEnvelope($eventType, array $data, $version, $eventId, $timestamp)
    {
        $payload = [
            'event_id' => (string)$eventId,
            'event_type' => (string)$eventType,
            'timestamp' => intval($timestamp),
            'version' => intval($version),
            'sign_type' => 'HMAC-SHA256',
            'data' => $data,
        ];
        $payload['sign'] = $this->makeSign($payload);
        return $payload;
    }

    private function canonicalJson($value)
    {
        $value = $this->canonicalize($value);
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function canonicalize($value)
    {
        if (!is_array($value)) {
            return $value;
        }

        // 对象键递归排序以保证双方签名一致；列表保留业务顺序。
        $isList = $value === [] || array_keys($value) === range(0, count($value) - 1);
        if (!$isList) {
            ksort($value, SORT_STRING);
        }
        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalize($item);
        }
        return $value;
    }
}

