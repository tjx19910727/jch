# pay_bugfix 合并 main 上线操作文档

版本：V1.0  
日期：2026-07-16  
适用分支：`pay_bugfix` 合并至 `main`  
适用对象：后端、后台管理端、设备端、运维、测试

## 一、上线范围

本次上线包含：

1. `subCar` 创建订单后的优惠券处理与普通 0 元购自动完成。
2. 商场积分兑换订单与普通 0 元购流程隔离。
3. 下单、支付接口统一返回支付动作字段和订单明细。
4. 优惠券、满减券支持关联微程线上商品。
5. 优惠金额限制在订单及订单明细余额内，防止负数。
6. 优惠券修改时排除当前记录，解决原优惠码误报重复问题。
7. 测试环境微程虚拟会员登录。
8. 测试环境积分兑换商品快速配置接口。
9. 微程订单首次同步失败后自动重试三次。
10. 三次重试失败后发送公众号异常通知。
11. 后台订单列表支持手动推送订单到微程。

以下内容不属于本次上线范围：

- 微程商品邮寄到家。
- 用户收货地址快照。
- 固定 3 元邮费。
- 设备出货与线下寄送的混合履约。
- `previewWcFulfillment`、`getWcShippingFee` 等规划接口。
- 物流单号和寄送完成状态。

## 二、推荐上线顺序

```text
停止发布操作和后台营销配置
→ 备份数据库
→ 执行第三节 SQL
→ 合并 pay_bugfix 到 main
→ 确认重试及手动推送新增文件已提交
→ 发布后台代码
→ 重启 PHP-FPM 或清理 OPcache
→ 配置微程重试定时任务
→ 分配后台手动推送权限
→ 后台接口联调
→ 设备端联调
→ 恢复正常业务
```

数据库 SQL 必须在新代码发布前执行，否则营销活动接口会查询不存在的字段，微程同步失败时也无法写入重试任务。

## 三、数据库执行 SQL

本节仅提供直接执行语句，不判断字段、索引、表或权限节点是否已经存在。只能在确认目标 `main` 数据库尚未执行这些变更后执行一次。

### 3.1 优惠券关联微程线上商品

```sql
ALTER TABLE `activity_goods`
    ADD COLUMN `goods_source` tinyint NOT NULL DEFAULT 1 COMMENT '商品来源：1普通商品2微程线上商品' AFTER `g_id`,
    ADD COLUMN `source_no` varchar(64) NOT NULL DEFAULT '' COMMENT '来源商品编码，微程商品保存out_no' AFTER `goods_source`,
    ADD INDEX `idx_activity_online_goods` (`a_id`, `a_type`, `goods_source`, `source_no`);
```

### 3.2 满减活动关联微程线上商品

```sql
ALTER TABLE `activity_fd_content`
    ADD COLUMN `goods_source` tinyint NOT NULL DEFAULT 1 COMMENT '商品来源：1普通商品2微程线上商品' AFTER `g_id`,
    ADD COLUMN `source_no` varchar(64) NOT NULL DEFAULT '' COMMENT '来源商品编码，微程商品保存out_no' AFTER `goods_source`,
    ADD INDEX `idx_fd_online_goods` (`fd_id`, `goods_source`, `source_no`);
```

### 3.3 微程订单同步重试任务表

```sql
CREATE TABLE `wc_order_sync_task` (
  `wcst_id` bigint NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `sod_id` int NOT NULL,
  `request_type` tinyint NOT NULL DEFAULT 1 COMMENT '1创建微程订单',
  `idempotency_key` varchar(190) NOT NULL DEFAULT '',
  `status` tinyint NOT NULL DEFAULT 0 COMMENT '0待执行1执行中2成功3待重试4人工处理',
  `retry_count` int NOT NULL DEFAULT 0 COMMENT '定时任务已重试次数，不含支付后的首次同步',
  `max_retry_count` int NOT NULL DEFAULT 3,
  `next_retry_time` int NOT NULL DEFAULT 0,
  `last_error` varchar(1000) NOT NULL DEFAULT '',
  `response_payload` mediumtext,
  `notice_status` tinyint NOT NULL DEFAULT 0 COMMENT '公众号通知：0待发送1成功2失败3发送中',
  `create_time` int NOT NULL DEFAULT 0,
  `update_time` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`wcst_id`),
  UNIQUE KEY `uk_wc_sync_sod_request` (`sod_id`,`request_type`),
  UNIQUE KEY `uk_wc_sync_idempotency` (`idempotency_key`),
  KEY `idx_wc_sync_due` (`status`,`next_retry_time`),
  KEY `idx_wc_sync_order` (`order_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='微程订单同步失败重试任务';
```

### 3.4 后台手动推送微程订单权限节点

```sql
INSERT INTO `auth_node`
    (`pid`, `name`, `url`, `desc`, `sort`, `type`, `is_auth`, `is_button`, `data_auth`, `permission_action`, `status`, `create_time`, `update_time`)
SELECT
    `pid`,
    '手动推送微程订单',
    '/management/sale.sale_orders/manualPushToWeiCheng',
    '对已支付订单中尚未同步成功的微程商品进行手动推送',
    99,
    `type`,
    1,
    1,
    `data_auth`,
    'manage',
    1,
    UNIX_TIMESTAMP(),
    UNIX_TIMESTAMP()
FROM `auth_node`
WHERE `url` = '/management/sale.sale_orders/getList'
LIMIT 1;
```

权限节点写入后，在后台角色权限配置中给需要操作的角色分配“手动推送微程订单”权限。本文件不直接写入 `auth_role_node`，因为各环境角色 ID 不一致。

## 四、后端代码合并检查

除 `pay_bugfix` 已提交内容外，必须确认以下重试和手动推送文件也已加入 Git 并一起合并：

```text
app/AppFactory/Kernel/Model/WeiCheng/WcOrderSyncTaskModel.php
app/AppFactory/Kernel/Providers/TimeTask/WeiChengProvider.php
app/AppFactory/Kernel/Service/WeiCheng/WcOrderSyncRetryService.php
app/AppFactory/TimeTask/WeiCheng/WeiChengClient.php
app/AppFactory/Kernel/Traits/WeiCheng/WcBaseTrait.php
app/AppFactory/Management/Sale/SaleOrdersClient.php
app/AppFactory/TimeTask/Application.php
app/command/TimeTask.php
app/management/controller/sale/SaleOrders.php
app/management/validate/VSaleOrders.php
config/weicheng.php
```

发布时不得只合并 Controller 或 SQL。重试模型、服务、Provider、定时任务 Client 和 Application 注册必须同时上线。

## 五、后台管理端同步更新

### 5.1 优惠券配置页面

涉及接口：

```text
POST /management/activity.activity_coupon/add
POST /management/activity.activity_coupon/update
```

页面需要增加微程线上商品选择，提交字段：

```json
{
  "designated_goods": 2,
  "goodsList": [1001, 1002],
  "onlineGoodsList": ["VC2601071001", "VC2601071002"]
}
```

处理要求：

- `goodsList` 保存普通商品 `g_id`。
- `onlineGoodsList` 保存微程父商品 `out_no`。
- 指定商品和排除指定商品都支持线上商品。
- 修改时传 `onlineGoodsList` 表示全量替换线上商品关联。
- 修改时不传 `onlineGoodsList` 表示保持原线上商品关联。
- 详情返回的商品项增加 `goods_source`、`source_no`。
- `goods_source=1` 表示普通商品，`goods_source=2` 表示微程线上商品。
- 更新原优惠券时可继续使用自身优惠码，不应再提示优惠码重复。

### 5.2 满减活动配置页面

涉及接口：

```text
POST /management/activity.activity_fd/add
POST /management/activity.activity_fd/update
```

微程商品规则示例：

```json
{
  "condition_type": 3,
  "content": [
    {
      "goods_source": 2,
      "source_no": "VC2601071001",
      "active_value": 3,
      "sort": 1
    }
  ]
}
```

处理要求：

- 普通商品继续提交原 `g_id`、`condition_value`。
- 微程商品必须提交 `goods_source=2` 和父商品 `source_no`。
- 后台会校验微程商品是否存在。
- 详情和编辑回显必须保留 `goods_source`、`source_no`。

### 5.3 销售订单列表手动推送

新增接口：

```text
POST /management/sale.sale_orders/manualPushToWeiCheng
```

请求头：

```text
token: 后台登录令牌
```

按整单推送：

```json
{
  "order_id": 33613
}
```

按交易号推送：

```json
{
  "trade_no": "20260715094128127153173"
}
```

指定订单明细推送：

```json
{
  "order_id": 33613,
  "sod_id": 55251
}
```

后台页面调整要求：

- 在销售订单列表或订单详情增加“手动推送微程”按钮。
- 推荐仅在 `pay_status` 为已支付状态且 `has_wc_order_no=1` 时显示。
- 点击前二次确认，提示“只会推送尚未同步成功的微程子商品”。
- 3 秒内禁止重复点击。
- `sod_id` 不传时处理整单所有微程明细。
- 根据返回的 `details[].status` 分别展示 `success`、`failed`、`skipped`。
- `retry_queued=true` 表示本次失败已经进入自动重试队列。
- 自动重试正在执行时，接口会返回该明细已跳过，后台提示稍后再试。
- 后台操作受组织数据权限限制，不能推送其他组织订单。

接口完整文档：`文档说明/后台手动推送订单到微程接口.apifox.openapi.json`。

### 5.4 测试环境积分商品配置

测试接口：

```text
POST /machine/test/configurePointsGoods
```

该接口用于测试环境快速绑定设备与积分商城，并设置货道 `cost_points`。它不是生产后台页面的正式配置接口，生产后台无需增加按钮。

## 六、设备端同步更新

本节列出的内容均按必须更新处理，不判断设备当前版本是否已经实现。

### 6.1 `subCar` 微程商品参数

微程线上商品使用虚拟货道 `Z10` 时，购物车项必须包含：

```json
{
  "mc_id": 186,
  "quantity": 1,
  "channel_code": "Z10",
  "out_no": "VC2507151411",
  "no": "VC2507151415",
  "order_date": ["2026-07-16"]
}
```

字段要求：

- `out_no`：微程父商品编码，不能为空。
- `no`：微程当前子商品编码。
- `quantity`：购买数量。
- `order_date`：房态或预约商品日期；非房态商品按原协议处理。
- 普通实体货道继续提交原 `mc_id`、`quantity`、`channel_code`。
- `carList` 中同时存在普通商品和微程商品时，所有商品一起创建同一订单。

### 6.2 线上商品优惠券

微程线上商品使用优惠券时，继续在 `subCar` 顶层提交：

```json
{
  "coupon_code": "0000"
}
```

设备端要求：

- 不得因为购物车商品为 `Z10` 或微程线上商品而删除 `coupon_code`。
- 优惠券在订单创建完成后由后台统一核销和计算。
- 普通商品、微程商品和混合购物车使用同一优惠券提交流程。
- 优惠券导致金额为 0 时，不再发起第三方现金支付。
- 出货失败时按后台现有回执流程处理，后台负责将对应 0 元购优惠券使用记录置为作废。

### 6.3 分账优惠券接口

分账优惠券不使用普通 `/machine/receive/useCoupon` 流程。设备完成 `subCar`、取得 `order_id` 后，调用：

```text
POST /machine/receive/useRevenueCoupon
```

请求示例：

```json
{
  "msg_id": "202607160001",
  "machine_id": "JCHM-H2D-0064",
  "timestamp": 1784160000,
  "sign": "设备签名",
  "order_id": 33613,
  "coupon_code": "923456"
}
```

处理要求：

- 分账优惠券码为六位非零开头数字。
- 必须先完成 `subCar` 创建订单，再调用该接口。
- 使用成功后以接口返回的 `total_price` 和订单明细为准。
- 使用失败时不得进入支付，应展示后台返回原因。
- 使用成功后继续调用支付接口，由支付接口判断正常支付或免支付。
- 普通活动优惠券和分账优惠券不能混用同一调用方式。

完整接口文档：`文档说明/设备分账优惠券接口.apifox.openapi.json`。

### 6.4 下单和支付动作字段

设备端必须读取：

```json
{
  "pay_required": false,
  "zero_pay": true,
  "next_action": "wait_out_goods",
  "order": {
    "trade_no": "20260715094128127153173",
    "details": []
  }
}
```

字段含义：

| 字段 | 含义 |
|---|---|
| `pay_required` | 是否需要继续调用支付接口 |
| `zero_pay` | 是否为普通免支付 0 元订单 |
| `next_action=pay` | 调用支付接口 |
| `next_action=wait_out_goods` | 不再支付，进入等待出货 |
| `order.details` | 完整订单明细，用于设备展示和后续出货关联 |

设备端判断规则：

```javascript
if (response.pay_required === true || response.next_action === 'pay') {
  startPayment(response.order);
} else if (response.next_action === 'wait_out_goods') {
  waitOutGoods(response.order.trade_no);
}
```

禁止继续只根据 `total_price` 是否为 0 判断是否支付。

### 6.5 普通 0 元购

普通商品或优惠券抵扣后金额小于 0.01 元时：

```json
{
  "pay_required": false,
  "zero_pay": true,
  "next_action": "wait_out_goods"
}
```

设备端处理：

- 不调用支付接口。
- 不停留在“等待支付”状态。
- 直接进入等待出货流程。
- 继续处理后台下发的 `paySuccess` 和 `outGoods` MQ。
- MQ暂未到达时可使用现有订单状态查询和出货指令补偿机制。

### 6.6 商场积分兑换订单

以下任一条件成立时，后台按积分兑换处理：

- `pay_type=9`。
- `order_type=7`。
- `total_cost_points>0`。

即使现金金额为 0，后台仍返回需要支付动作：

```json
{
  "pay_required": true,
  "zero_pay": false,
  "next_action": "pay"
}
```

设备必须继续调用支付接口完成真实积分扣减。积分支付成功后返回：

```json
{
  "pay_required": false,
  "zero_pay": false,
  "next_action": "wait_out_goods"
}
```

### 6.7 普通有价订单

普通有价订单返回：

```json
{
  "pay_required": true,
  "zero_pay": false,
  "next_action": "pay"
}
```

设备按原流程支付。支付成功后统一进入 `wait_out_goods`。

### 6.8 取货码订单

- 继续沿用原取货码下单、查询和出货流程。
- 设备端无需新增工厂、库位字段处理。
- 不得把取货码订单误判为普通 0 元购。
- 仍需回归验证取货码成功出货和失败回执。

### 6.9 微程测试会员虚拟登录

仅测试环境支持：

```text
手机号：13000000000
验证码：000000
```

设备端使用原短信和登录接口即可，不新增接口。返回中可能包含：

```json
{
  "virtual_login": true,
  "phone": "13000000000",
  "card_lists": [],
  "address_lists": []
}
```

设备端应兼容 `virtual_login` 字段，卡券和地址列表为空时按空列表处理。生产环境不会触发虚拟登录。

### 6.10 设备端无需处理的内容

以下逻辑全部由后台处理：

- 微程订单首次同步。
- 同步失败任务入库。
- 自动重试三次。
- 最终失败公众号通知。
- 后台手动推送。
- 已同步成功子商品的幂等跳过。
- 优惠金额防负值。
- 营销活动与微程商品的数据库关联。

## 七、微程同步重试规则

支付成功后的首次同步不计入 `retry_count`。首次失败后创建任务，定时任务执行规则如下：

| 阶段 | 延迟 | 失败后状态 |
|---|---:|---|
| 首次同步 | 支付成功后立即执行 | 创建待重试任务 |
| 第 1 次重试 | 约 1 分钟 | 继续待重试 |
| 第 2 次重试 | 约 5 分钟 | 继续待重试 |
| 第 3 次重试 | 约 15 分钟 | 转人工处理 |

三次重试仍失败后：

- `status=4`，表示人工处理。
- 发送一次 `mFault` 类型公众号消息。
- `notice_status=1` 表示通知成功。
- `notice_status=2` 表示通知发送失败。
- 公众号通知失败不会回滚订单支付状态。
- 后台仍可使用手动推送接口再次处理。

组合商品部分子商品已经取得微程 `order_no` 时，后续重试和手动推送只处理尚未取得 `order_no` 的子商品。

## 八、定时任务配置

### 8.1 手动验证命令

在生产项目根目录执行：

```bash
php think time_task weiCheng retryOrderSync
```

正常输出示例：

```text
处理完成：总数0，成功0，失败0，转人工0
```

### 8.2 Linux crontab

先确认 PHP 和项目路径：

```bash
which php
cd /home/wwwroot/kiosk
pwd
```

编辑网站运行用户的 crontab：

```bash
sudo -u www crontab -e
```

每分钟执行一次：

```cron
* * * * * cd /home/wwwroot/kiosk && /usr/bin/flock -n /tmp/jch_wc_order_sync.lock /usr/bin/php think time_task weiCheng retryOrderSync >> runtime/log/wc_order_sync_retry.log 2>&1
```

替换规则：

- `/home/wwwroot/kiosk` 替换为生产项目根目录。
- `/usr/bin/php` 替换为 `which php` 返回路径。
- 运行用户 `www` 替换为实际 PHP-FPM 网站用户。
- 保留 `flock`，防止上一次任务未完成时下一分钟并发执行。

### 8.3 宝塔计划任务

- 任务类型：Shell 脚本。
- 执行周期：每分钟。
- 执行用户：与网站 PHP-FPM 用户一致。
- 脚本内容：

```bash
cd /home/wwwroot/kiosk
/usr/bin/flock -n /tmp/jch_wc_order_sync.lock /usr/bin/php think time_task weiCheng retryOrderSync >> runtime/log/wc_order_sync_retry.log 2>&1
```

### 8.4 定时任务检查

```bash
sudo -u www crontab -l
tail -f /home/wwwroot/kiosk/runtime/log/wc_order_sync_retry.log
```

任务必须具备以下权限：

- 读取项目代码和 `.env`。
- 写入 `runtime/log`。
- 连接业务数据库。
- 访问微程接口。
- 访问公众号消息接口。

## 九、后台接口联调清单

- [ ] 优惠券新增时能选择普通商品和微程线上商品。
- [ ] 优惠券修改时能正确回显和替换线上商品。
- [ ] 修改优惠券保留原优惠码不会提示重复。
- [ ] 满减活动能关联微程线上商品。
- [ ] 微程商品命中优惠券后金额正确。
- [ ] 微程商品命中满减后金额正确。
- [ ] 订单和明细金额不会小于 0。
- [ ] 销售订单列表显示手动推送按钮。
- [ ] 无权限角色不能调用手动推送接口。
- [ ] 非本组织订单不能手动推送。
- [ ] 未支付订单不能手动推送。
- [ ] 普通订单调用手动推送时提示不包含微程商品。
- [ ] 已同步成功的微程子商品被跳过。
- [ ] 手动推送失败后 `retry_queued=true`。

## 十、设备端联调清单

- [ ] 普通有价订单正常支付和出货。
- [ ] 普通优惠券订单正常支付和出货。
- [ ] 优惠券抵扣为 0 后不再调用支付接口。
- [ ] 0 元订单能正常收到并执行出货通知。
- [ ] 0 元订单出货失败后后台优惠券记录作废。
- [ ] 积分兑换订单现金为 0 时仍调用支付接口。
- [ ] 积分扣减成功后进入等待出货。
- [ ] 微程 `Z10` 商品提交完整 `out_no` 和 `no`。
- [ ] 微程线上商品参与优惠券计算。
- [ ] 普通商品和微程商品混合购物车正常下单。
- [ ] `subCar` 成功响应包含 `order.details`。
- [ ] 设备按 `pay_required` 和 `next_action` 决定下一步。
- [ ] 取货码订单正常出货，且不新增工厂、库位处理。
- [ ] 测试手机号和验证码能完成虚拟登录。
- [ ] 所有失败响应的 `data.order` 能按对象兼容解析。

## 十一、微程重试联调清单

- [ ] 临时阻断微程接口后支付订单，订单支付和设备出货不受影响。
- [ ] `wc_order_sync_task` 生成一条对应 `sod_id` 的任务。
- [ ] 恢复微程接口后定时任务能同步成功。
- [ ] 同一 `sod_id` 不会生成多条创建订单任务。
- [ ] 组合商品成功子项不会重复推送。
- [ ] 连续三次失败后任务转 `status=4`。
- [ ] 最终失败发送公众号消息。
- [ ] 后台手动推送成功后任务更新为成功。
- [ ] 后台手动推送和自动任务不会同时执行同一明细。

## 十二、上线后监控

重点监控：

```sql
SELECT `status`, COUNT(*) AS `task_count`
FROM `wc_order_sync_task`
GROUP BY `status`;

SELECT `wcst_id`, `order_id`, `sod_id`, `retry_count`, `last_error`, `notice_status`, `update_time`
FROM `wc_order_sync_task`
WHERE `status` IN (3, 4)
ORDER BY `update_time` DESC
LIMIT 100;
```

同时检查：

- PHP 运行日志是否出现微程连接异常。
- 定时任务日志是否每分钟持续输出。
- `status=1` 的任务是否长期不释放；超过 10 分钟会由程序自动重新入队。
- `notice_status=2` 的最终失败通知是否需要人工补发。
- 微程侧是否存在相同 `out_order_no` 的重复订单。

## 十三、回滚注意事项

代码回滚时先停用微程重试定时任务，再回滚应用代码。不要立即删除 `wc_order_sync_task`，应先保留失败任务和第三方响应用于排查。

营销活动已经配置微程线上商品后，不应直接删除 `goods_source`、`source_no` 字段，否则线上商品活动关联会丢失。数据库结构回滚必须在确认相关营销配置已清理后单独执行。

## 十四、交付文档

```text
文档说明/营销活动关联微程线上商品接口.apifox.openapi.json
文档说明/设备分账优惠券接口.apifox.openapi.json
文档说明/微程测试会员虚拟登录接口.apifox.openapi.json
文档说明/后台手动推送订单到微程接口.apifox.openapi.json
文档说明/pay_bugfix合并main上线操作文档.md
```
