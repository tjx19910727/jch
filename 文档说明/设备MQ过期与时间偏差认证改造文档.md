# 设备MQ过期与时间偏差认证改造文档

## 一、背景

当前设备与后台通信存在时间依赖问题：

1. 设备端使用本机 `DateTime.now()` 判断 `authKey/signKey` 是否过期。
2. 设备 HTTP/MQ 请求会携带本机生成的 `timestamp`。
3. 后台 HTTP 设备接口会校验请求 `timestamp` 是否落后服务器时间过多。
4. 后台下发设备 MQ 消息设置了 3 分钟过期时间。

当设备本机时间与服务器时间偏差较大时，设备可能反复认为认证不可用，或请求被后台判定时间戳超时，最终表现为认证失败、验签失败、重复获取 authKey。

## 二、现有逻辑说明

### 2.1 后台 HTTP 设备入口

入口文件：

```text
app/machine/controller/Receive.php
```

核心流程：

1. 读取设备请求参数。
2. 按当前 action 执行 `VReceive.{action}` 参数校验。
3. 读取请求头 `mac`。
4. 创建设备应用实例 `AppFactory::machine($config)`。
5. 执行 `$this->app->api->checkSign($postData)`。

验签失败时返回：

```json
{
  "state": 100,
  "msg": "验签失败"
}
```

### 2.2 HTTP timestamp 校验

校验文件：

```text
app/machine/validate/VReceive.php
```

当前设备 HTTP timestamp 容忍窗口已配置化：

```php
$tolerance = intval(config('rabbit_mq.machine_receive_timestamp_tolerance') ?: 180);
if ($tolerance < 120) $tolerance = 120;
if (time() - intval($item) > $tolerance) return "VReceive.timestamp_checkTimestamp_overdue";
```

含义：

```text
服务器当前时间 - 设备请求 timestamp > 容忍秒数
```

则后台返回时间戳超时。

### 2.3 MQ 过期时间

后台下发设备 MQ 消息位置：

```text
app/AppFactory/RabbitMq/MqProducer.php
```

当前 MQ 消息过期时间：

```php
config('rabbit_mq.data_send_expiration_ms') ?: (180 * 1000)
```

默认 180 秒。

注意：MQ 过期时间只控制消息在 RabbitMQ 队列里的存活时间，不等同于 HTTP 请求 timestamp 校验，也不能解决设备本机时间偏差导致的 authKey 误判。

## 三、问题根因

### 3.1 MQ TTL 与 HTTP timestamp 是两套机制

MQ TTL：

```text
后台下发给设备的 MQ 消息，在队列中最多保留多久。
```

HTTP timestamp 校验：

```text
设备发起 HTTP 请求时，请求时间戳是否落后服务器太多。
```

因此，即使 MQ 过期时间是 3 分钟，只要设备请求 timestamp 比服务器慢超过后台容忍窗口，HTTP 请求仍会失败。

### 3.2 设备使用本机绝对时间判断 authKey

设备端如果使用：

```text
DateTime.now() >= authKeyExpiresAt
```

判断 authKey 过期，则当设备本机时间快很多时，会把刚收到的 authKey 立即判定为过期；当设备时间慢很多时，请求 timestamp 又可能被后台判定为过期。

### 3.3 timestamp 参与签名

后台签名规则会对请求字段排序后拼接，再追加 key 生成 MD5。`timestamp` 是签名字段之一。

如果设备签名时使用一个 timestamp，真正发送时又重新取了另一个 timestamp，则后台必然验签失败。

## 四、后台已完成改造

### 4.1 新增/确认通信配置

文件：

```text
config/rabbit_mq.php
```

配置项：

```php
// 后台下发到设备的 MQ 消息过期时间，单位：毫秒
'data_send_expiration_ms' => 180 * 1000,

// 设备 HTTP 请求 timestamp 允许落后服务器的秒数
'machine_receive_timestamp_tolerance' => 180,

// 设备签名密钥有效期提示，单位：秒。设备端应按收到后的经过时间判断，不依赖本机绝对时间。
'machine_sign_key_expires_in' => 3600,
```

### 4.2 authKey/signKey 下发新增服务器时间字段

文件：

```text
app/AppFactory/Machine/Receive/ReceiveBaseClient.php
```

设备无签名、只带 mac 请求时，后台通过 MQ 下发 signKey。下发数据新增：

```json
{
  "msg_id": "unique_id",
  "timestamp": 1710000000,
  "server_time": 1710000000,
  "machine_id": "machine001",
  "signKey": "xxx",
  "expires_in": 3600,
  "expires_at": 1710003600,
  "timestamp_tolerance": 180
}
```

字段说明：

| 字段 | 说明 |
| --- | --- |
| timestamp | 后台当前时间，兼容旧字段 |
| server_time | 后台服务器当前 Unix 时间戳 |
| signKey | 设备后续签名使用的 key |
| expires_in | signKey 建议有效时长，单位秒 |
| expires_at | 按服务器时间计算的过期时间，仅用于展示/日志/兼容 |
| timestamp_tolerance | 后台 HTTP timestamp 容忍窗口，单位秒 |

### 4.3 HTTP timestamp 超时响应返回服务器时间

文件：

```text
app/machine/controller/Receive.php
```

当设备 HTTP 请求 timestamp 超时时，后台返回：

```json
{
  "state": 300,
  "msg": "时间戳超时，请更新时间",
  "data": {
    "server_time": 1710000000,
    "request_timestamp": 1709999800,
    "server_time_offset": 200,
    "timestamp_tolerance": 180
  }
}
```

字段说明：

| 字段 | 说明 |
| --- | --- |
| server_time | 后台服务器当前 Unix 时间戳 |
| request_timestamp | 本次设备请求传入的 timestamp |
| server_time_offset | `server_time - request_timestamp` |
| timestamp_tolerance | 后台允许 timestamp 落后的秒数 |

### 4.4 机器人设备入口同步配置化

文件：

```text
app/machine/validate/VRobot.php
```

机器人设备 timestamp 校验由硬编码 120 秒调整为读取：

```php
config('rabbit_mq.machine_receive_timestamp_tolerance')
```

## 五、设备端必须配合的改动

### 5.1 不再用本机绝对时间判断 authKey 过期

禁止使用：

```text
DateTime.now() >= expires_at
```

判断 authKey 是否过期。

应改为：

```text
authKeyReceivedAt = monotonicNow
authKeyExpiresIn = expires_in

authKeyExpired = monotonicNow - authKeyReceivedAt >= authKeyExpiresIn
```

说明：

`monotonicNow` 应使用设备系统提供的单调时钟或运行时长计时，不受手动修改系统时间影响。

### 5.2 保存服务器时间偏移

设备收到 authKey 下发 MQ 或 timestamp 超时响应时，计算并保存：

```text
serverTimeOffset = server_time - deviceNowUnix
```

后续请求 timestamp 使用：

```text
timestamp = deviceNowUnix + serverTimeOffset
```

### 5.3 签名前固定 timestamp

设备生成请求时，必须先确定最终发送的 timestamp，再生成 sign：

```text
payload.timestamp = deviceNowUnix + serverTimeOffset
payload.sign = makeSign(payload, authKey)
```

签名完成后，不允许再次更新 `payload.timestamp`。

错误示例：

```text
timestamp1 = DateTime.now()
sign = makeSign(timestamp1)
timestamp2 = DateTime.now()
send(timestamp2, sign)
```

上述行为会导致后台验签失败。

### 5.4 timestamp 超时后自动校准并重试一次

当 HTTP 返回：

```text
时间戳超时，请更新时间
```

并携带 `server_time` 时，设备端处理流程：

1. 更新 `serverTimeOffset`。
2. 使用新 timestamp 重新生成 sign。
3. 重试当前请求一次。
4. 如果重试仍失败，再进入 authKey 刷新流程。

不要在第一次 timestamp 超时时立即清空 authKey 或反复重新认证。

### 5.5 authKey 刷新条件

设备只应在以下情况重新获取 authKey：

1. 本地没有 authKey。
2. `monotonicNow - authKeyReceivedAt >= expires_in`。
3. 使用修正后的 timestamp 重试一次后，后台仍返回验签失败。
4. 后台未来明确返回要求重新认证的错误码。

## 六、兼容性说明

### 6.1 只更新后台、不更新设备端

只更新后台一般不会产生新的致命异常，但不能根治。

原因：

1. 后台普通 MQ 下发未新增顶层签名字段，避免影响老设备验签。
2. authKey 下发 MQ 新增了 `server_time/expires_in/expires_at/timestamp_tolerance` 字段。
3. 如果老设备解析 MQ 时忽略未知字段，则不会异常。
4. 如果老设备对 authKey 下发字段做严格白名单校验，则可能因新增字段解析失败，需要设备端确认。

### 6.2 后台改造带来的改善

1. HTTP timestamp 容忍窗口从硬编码 120 秒改为配置化，当前为 180 秒。
2. timestamp 超时时返回服务器时间，设备可据此修正偏移。
3. authKey 下发携带 `expires_in/server_time`，设备可摆脱本机绝对时间。

### 6.3 未升级设备的残留风险

如果设备仍使用本机 `DateTime.now()` 判断 authKey 过期，当本机时间严重偏差时，仍可能反复认为认证不可用。

## 七、推荐设备端流程

```text
设备启动
  |
  |-- 无 authKey
  |     |
  |     |-- mac-only 请求后台
  |     |-- 收到 signKey + server_time + expires_in
  |     |-- 保存 authKey
  |     |-- 保存 serverTimeOffset
  |     |-- 保存 authKeyReceivedAt = monotonicNow
  |
  |-- 发起 HTTP/MQ 请求
        |
        |-- timestamp = deviceNowUnix + serverTimeOffset
        |-- 使用 authKey 生成 sign
        |-- 发送请求
        |
        |-- 如果 timestamp 超时
        |     |
        |     |-- 用响应 server_time 更新 serverTimeOffset
        |     |-- 重新生成 timestamp 和 sign
        |     |-- 重试一次
        |
        |-- 如果验签失败
              |
              |-- 若已用新 offset 重试仍失败
              |-- 重新获取 authKey
```

## 八、测试建议

### 8.1 后台测试

1. 设备 timestamp 比服务器慢 60 秒，请求成功。
2. 设备 timestamp 比服务器慢 200 秒，请求返回 timestamp 超时，并携带：
   - `server_time`
   - `request_timestamp`
   - `server_time_offset`
   - `timestamp_tolerance`
3. 设备 mac-only 请求 authKey，后台 MQ 下发包含：
   - `server_time`
   - `expires_in`
   - `expires_at`
   - `timestamp_tolerance`
4. 普通后台 MQ 下发结构不新增额外顶层字段，避免影响老设备验签。

### 8.2 设备测试

1. 设备本机时间慢 5 分钟，首次请求 timestamp 超时后，自动校准 offset 并重试成功。
2. 设备本机时间快 2 小时，收到 authKey 后不应立即判定过期。
3. 修改设备系统时间后，authKey 过期判断不受影响。
4. 签名时 timestamp 与实际发送 timestamp 保持一致。
5. 网络延迟或 MQ 堆积未超过 TTL 时，设备能正常消费 authKey 下发消息。

## 九、上线顺序建议

1. 先上线后台兼容改造。
2. 灰度升级设备端：
   - 先支持忽略 authKey MQ 未知字段。
   - 再支持 `server_time/serverTimeOffset`。
   - 最后将 authKey 过期判断切换为 `expires_in + monotonicNow`。
3. 观察日志：
   - `timestamp_checkTimestamp_overdue` 次数是否下降。
   - `验签失败` 次数是否下降。
   - mac-only 获取 authKey 是否不再频繁循环。

## 十、结论

MQ 3 分钟过期时间只能保证后台下发消息不过期堆积，不能解决设备本机时间错误导致的认证问题。

根治方案是：

```text
timestamp 使用服务器时间偏移修正；
authKey 过期使用收到后的经过时长判断；
timestamp 超时后自动校准并重试；
签名字段与发送字段保持一致。
```

后台已提供必要字段和兼容响应，设备端完成配合后，可避免因本机时间偏差导致的反复认证和验签失败。
