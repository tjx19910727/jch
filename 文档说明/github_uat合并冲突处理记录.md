# github_uat 合并冲突处理记录

## 合并信息

- 当前分支：`github_uat`
- 合入分支：`mix_goods_revenue_add_cost_pay_bugfix`
- 合并原则：保留 `github_uat` 已有业务能力，并补入合入分支的独立能力；同一功能只保留一套实现。
- 数据库操作：未执行。
- 外部接口调用：未执行。

## 冲突处理明细

### 1. `app/AppFactory/Kernel/Traits/SaleOrders/SaleOrdersTrait.php`

- 冲突位置：`addSaleOrders()` 创建订单前的数据处理。
- 当前分支：补充订单运行模式。
- 合入分支：归一化订单非负金额字段。
- 处理方式：两项处理按顺序全部保留，先补充运行模式，再归一化金额，最后补充优惠券分账编码并创建订单。

### 2. `app/AppFactory/Machine/Receive/ApiClient.php`

- 冲突位置：`remoteStatus()` 与 `submitRefundGoodsLog()` 之间。
- 当前分支：新增设备远程出货步骤上报接口。
- 合入分支：该位置直接进入客户退货日志接口。
- 处理方式：保留完整 `remoteStatus()`，并继续保留后续 `submitRefundGoodsLog()`；删除重复的注释起始符。

### 3. `app/AppFactory/Management/Activity/ActivityCouponClient.php`

- 冲突位置：`addAc()`、`updateAc()` 和文件尾部。
- 当前分支：保护 `ticket`，补充链接领取默认配置，并提供 `getCouponUrl()`。
- 合入分支：初始化和识别 `online_goods_list`。
- 处理方式：新增和编辑同时保留链接领取字段保护与线上商品范围处理；保留 `getCouponUrl()`。

### 4. `app/AppFactory/Management/Machine/MachineChannelStockReportClient.php`

- 冲突位置：列表、导出签名、在营状态过滤方法。
- 当前分支：过滤方法返回新 `$where`，避免直接查询报表视图不存在的 `is_operating` 字段。
- 合入分支：增加参数范围校验并尝试支持状态 `1/2/3`。
- 处理方式：保留返回式过滤方法并补入参数校验；依据现有接口文档和专项 guard，维持状态枚举 `1/2`，不扩大为 `3`；列表和两种导出统一接收返回的 `$where`；只保留一套过滤方法。

### 5. `app/AppFactory/Management/Sale/SaleOrdersClient.php`

- 冲突位置：异常订单处理方法之前。
- 当前分支：提供远程出货步骤详情和历史支付渠道回填。
- 合入分支：该位置直接进入异常订单处理。
- 处理方式：保留当前分支两个独立方法，并保留后续异常订单处理；修复冲突造成的注释边界。

### 6. `app/machine/controller/Receive.php`

- 冲突位置：运行模式与支付类型接口，以及文件尾部 OTA 接口。
- 当前分支：`reportMachineRunMode()` 和四个 OTA 接口。
- 合入分支：`getPayTypeList()`。
- 处理方式：接口取并集；分别保留运行模式、支付类型和 OTA 接口，并为各自方法保留独立异常处理。

### 7. `app/machine/validate/VReceive.php`

- 冲突位置：设备接口验证场景。
- 当前分支：`reportMachineRunMode`。
- 合入分支：`getPayTypeList`。
- 处理方式：两个场景全部保留，各自维持原请求字段。

### 8. `app/management/controller/machine/MachineChannelStockReport.php`

- 冲突位置：列表和两种导出的 `is_operating` 默认值。
- 当前分支：未传参数时使用 `null`。
- 合入分支：未传参数时使用空字符串。
- 处理方式：统一使用 `null`，明确区分未传条件，并交由 Client 做合法值校验。

### 9. `app/management/controller/sale/SaleOrders.php`

- 冲突位置：订单列表条件、列表字段、详情字段、导出条件和文件尾部管理接口。
- 当前分支：`pay_channel`、`run_mode`、`receipt` 查询/返回及 `printReceipt()`。
- 合入分支：首页 `create_date` 兼容及 `manualPushToWeiCheng()`。
- 处理方式：保留当前分支全部查询字段和返回字段；补入 `create_date` 到 `pay_time` 的兼容转换；同时保留打印小票和手动推送微程两个接口。

### 10. `app/management/lang/en.php`

- 冲突位置：设备配置验证提示。
- 当前分支：运行模式和线上支付成功提示。
- 合入分支：混合下单及收款策略提示。
- 处理方式：合并全部不同语言键，不覆盖、不删除。

### 11. `app/management/lang/zh-cn.php`

- 冲突位置：设备配置验证提示。
- 当前分支：运行模式和线上支付成功提示。
- 合入分支：混合下单及收款策略提示。
- 处理方式：合并全部不同语言键，并与英文文件保持键一致。

### 12. `app/management/validate/Machine/VMachineConfig.php`

- 冲突位置：规则、提示、场景及 `sceneMcList()`。
- 当前分支：`online_pay_success_tip`、`run_mode`。
- 合入分支：`subcar_mix` 和线上/线下收款策略 ID。
- 处理方式：规则和提示全部保留；`add`、`update`、`mcList` 场景合并双方字段；`sceneMcList()` 合并字段；保留 `checkPayeeIds()`。

## 验证记录

- 冲突标记扫描：通过，`app` 下无 Git 冲突标记。
- PHP 7 语法检查：12 个冲突 PHP 文件全部通过 `php -l`。
- 重复方法检查：通过，12 个冲突文件未发现重复方法定义。
- 专项 guard：排除 1 个已弃用的混合履约脚本后，21 个有效脚本中有 20 个达到通过条件。
  - 库存状态、优惠券、收据、订单非负字段、订单运行模式、支付类型、微程手动推送、设备运行模式上报、零元支付、积分排除、混合下单相关静态约束等已通过。
  - `machine_run_mode_guard.php` 仍报告两项：一项按旧版精确字符串断言 `update` 场景，合并后该场景还包含混合下单字段，但 `run_mode` 规则实际保留；另一项位于非冲突文件 `Machine.php`。
  - `wc_mixed_fulfillment_guard.php` 所需的 `app/AppFactory/Kernel/Service/WeiCheng/WcFulfillmentService.php` 不存在于当前 Git 树。经业务确认，该服务及“设备出货 + 线下寄送混合履约”方案已弃用，不再补齐，也不作为本次合并阻塞项。
- 本次 12 个冲突文件的差异检查：清理一处行尾空白后通过。
- 全暂存区 `git diff --cached --check`：仍被既有暂存文件 `文档说明/pay_bugfix合并main上线操作文档.md` 第 3-5 行的 Markdown 行尾空格阻塞；该文件不属于本次冲突范围，未擅自修改。
- 合并提交：未创建。

## 剩余阻塞项

1. 决定是否同步更新 `machine_run_mode_guard.php` 的旧版精确字符串断言；当前合并结果同时验证运行模式和混合下单字段。
2. 对非冲突文件 `app/management/controller/machine/Machine.php` 的“未传 run_mode”行为单独核查，该项不在本次 12 个冲突文件范围内。

## 已弃用范围

- 不新增 `app/AppFactory/Kernel/Service/WeiCheng/WcFulfillmentService.php`。
- 不启用设备出货与线下寄送拆分的微程混合履约状态机。
- 不执行 `文档说明/微程订单混合履约数据库变更.sql`。
- `tests/wc_mixed_fulfillment_guard.php` 属于未跟踪的弃用方案验证脚本，不纳入本次合并验收。
- 保留当前普通微程订单同步、同步失败重试及后台手动推送能力。
