<?php

namespace app\AppFactory\Kernel\Service\Api;

/**
 * 构造第三方商品同步请求，并生成可复算的 HMAC-SHA256 签名。
 */
class ThirdPartyProductSyncPayloadBuilder
{
    private $appId;
    private $secret;

    public function __construct($appId, $secret)
    {
        $this->appId = (string)$appId;
        $this->secret = (string)$secret;
    }

    public function buildMachineInventory($machineId, array $items, $version, $eventId, $timestamp)
    {
        return $this->build('machine_inventory.sync', [
            'machine_id' => (string)$machineId,
            'sync_mode' => 'snapshot',
            'items' => array_values($items),
        ], $version, $eventId, $timestamp);
    }

    public function buildCoreGoods($productId, array $goods, $operation, $version, $eventId, $timestamp)
    {
        $item = $goods;
        $item['operation'] = $operation === 'delete' ? 'delete' : 'upsert';
        $item['product_id'] = intval($productId);
        return $this->build('core_goods.sync', [
            'sync_mode' => 'delta',
            'items' => [$item],
        ], $version, $eventId, $timestamp);
    }

    public function makeSign(array $payload)
    {
        unset($payload['sign']);
        return hash_hmac('sha256', $this->canonicalJson($payload), $this->secret);
    }

    private function build($eventType, array $data, $version, $eventId, $timestamp)
    {
        $payload = [
            'app_id' => $this->appId,
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
