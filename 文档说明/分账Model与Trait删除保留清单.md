# 分账 Model 与 Trait 删除保留清单

## 结论

当前分账计算、支付成功结算、优惠券命中、后台统一配置已经切到新结构：

- 分账配置主表：`revenue_rule_config`
- 分账生效范围表：`revenue_rule_config_scope`
- 分账订单表：`revenue_order`
- 分账账户表：`revenue_account`
- 分账触发支付类型表：`revenue_pay_channel`

因此旧表 `revenue_rule_item`、`revenue_rule_item_tier`、`revenue_rule_machine`、`revenue_rule_coupon`、`revenue_rule_coupon_scope` 及其旧 Model/Trait 可以删除或保持不存在。删除后不影响 `/management/revenue.revenue_rule/getList` 和 `/management/revenue.revenue_rule/saveConfig`，前提是必须保留新配置相关 Model/Trait。

## 必须保留的 Model

| 文件 | 对应表 | 必须保留原因 |
| --- | --- | --- |
| `app/AppFactory/Kernel/Model/Revenue/RevenueRuleConfigModel.php` | `revenue_rule_config` | 新分账配置主表；`saveConfig/getList/getFind`、下单计算、优惠券分账、结算扣次均依赖。 |
| `app/AppFactory/Kernel/Model/Revenue/RevenueRuleConfigScopeModel.php` | `revenue_rule_config_scope` | 新分账生效设备/商品范围；`saveScope`、下单规则匹配、优惠券适用范围均依赖。 |
| `app/AppFactory/Kernel/Model/Revenue/RevenueOrderModel.php` | `revenue_order` | 下单预生成分账记录、支付成功结算、退款联动、后台分账订单查询依赖。 |
| `app/AppFactory/Kernel/Model/Revenue/RevenueAccountModel.php` | `revenue_account` | 分账接收账户；配置校验、下单计算、后台账户管理依赖。 |
| `app/AppFactory/Kernel/Model/Revenue/RevenuePayChannelModel.php` | `revenue_pay_channel` | 控制哪些支付类型触发分账；下单计算入口 `shouldCalculateRevenue()` 依赖。 |

## 必须保留的 Trait

| 文件 | 必须保留原因 |
| --- | --- |
| `app/AppFactory/Kernel/Traits/Revenue/RevenueRuleTrait.php` | 新配置 CRUD 统一入口；`RevenueRuleClient` 的 `saveConfig/saveScope/getList/getFind` 依赖。 |
| `app/AppFactory/Kernel/Traits/Revenue/RevenueAccountTrait.php` | 后台分账账户管理和分账配置账户校验依赖。 |
| `app/AppFactory/Kernel/Traits/Revenue/RevenueOrderTrait.php` | 后台分账订单查询、导出、详情依赖。 |
| `app/AppFactory/Kernel/Traits/Revenue/RevenuePayChannelTrait.php` | 后台支付类型开关管理依赖。 |

## 可以删除或保持不存在的旧 Model

当前代码目录未发现以下旧 Model 文件；如果其他分支或历史文件中存在，可以删除：

| 文件 | 对应旧表 | 删除原因 |
| --- | --- | --- |
| `app/AppFactory/Kernel/Model/Revenue/RevenueRuleModel.php` | `revenue_rule` | 旧分账策略主表 Model；新逻辑使用 `RevenueRuleConfigModel`。 |
| `app/AppFactory/Kernel/Model/Revenue/RevenueRuleItemModel.php` | `revenue_rule_item` | 旧接收方明细表 Model；新逻辑使用 `revenue_rule_config.receiver_config` JSON。 |
| `app/AppFactory/Kernel/Model/Revenue/RevenueRuleItemTierModel.php` | `revenue_rule_item_tier` | 旧阶梯明细表 Model；新逻辑使用 `receiver_config[].tiers`。 |
| `app/AppFactory/Kernel/Model/Revenue/RevenueRuleMachineModel.php` | `revenue_rule_machine` | 旧设备绑定表 Model；新逻辑使用 `revenue_rule_config_scope`。 |
| `app/AppFactory/Kernel/Model/Revenue/RevenueRuleCouponModel.php` | `revenue_rule_coupon` | 旧优惠券配置表 Model；新逻辑使用 `revenue_rule_config` 的 rule_mode=5 字段。 |
| `app/AppFactory/Kernel/Model/Revenue/RevenueRuleCouponScopeModel.php` | `revenue_rule_coupon_scope` | 旧优惠券范围表 Model；新逻辑使用 `revenue_rule_config_scope`。 |

## 可以删除或保持不存在的旧 Trait

当前代码目录未发现以下旧 Trait 文件；如果其他分支或历史文件中存在，可以删除：

| 文件 | 删除原因 |
| --- | --- |
| `app/AppFactory/Kernel/Traits/Revenue/RevenueRuleItemTrait.php` | 旧 `revenue_rule_item` CRUD，不再需要。 |
| `app/AppFactory/Kernel/Traits/Revenue/RevenueRuleItemTierTrait.php` | 旧 `revenue_rule_item_tier` CRUD，不再需要。 |
| `app/AppFactory/Kernel/Traits/Revenue/RevenueRuleMachineTrait.php` | 旧 `revenue_rule_machine` CRUD，不再需要。 |
| `app/AppFactory/Kernel/Traits/Revenue/RevenueRuleCouponTrait.php` | 旧 `revenue_rule_coupon` / `revenue_rule_coupon_scope` CRUD，不再需要。 |

## 暂不建议删除的管理层文件

这些不是 Model/Trait，但与后台接口直接相关，不能因为删除旧表而删除：

| 文件 | 保留原因 |
| --- | --- |
| `app/AppFactory/Management/Revenue/RevenueRuleClient.php` | 后台分账配置核心 Client；需要继续清理旧兼容方法，但文件本身必须保留。 |
| `app/AppFactory/Management/Revenue/RevenueAccountClient.php` | 后台分账账户管理。 |
| `app/AppFactory/Management/Revenue/RevenueOrderClient.php` | 后台分账订单查询、导出。 |
| `app/AppFactory/Management/Revenue/RevenuePayChannelClient.php` | 后台支付渠道开关管理。 |
| `app/AppFactory/Management/Revenue/RevenueOrganizationNameTrait.php` | 后台展示组织名称。 |
| `app/AppFactory/Management/Revenue/RevenuePayTypeDescTrait.php` | 后台展示支付/分账账户类型说明。 |

## 删除旧表后的接口影响

### 不受影响

- `/management/revenue.revenue_rule/getList`
- `/management/revenue.revenue_rule/getFind`
- `/management/revenue.revenue_rule/saveConfig`
- `/management/revenue.revenue_rule/saveScope`

这些接口依赖 `revenue_rule_config` 和 `revenue_rule_config_scope`。

### 需要同步清理的旧兼容接口

以下接口目前仍使用旧参数名或旧接口形态，建议下线并从 Apifox 删除：

- `/management/revenue.revenue_rule/add`
- `/management/revenue.revenue_rule/update`
- `/management/revenue.revenue_rule/addItem`
- `/management/revenue.revenue_rule/addProductItem`
- `/management/revenue.revenue_rule/updateItem`
- `/management/revenue.revenue_rule/getItemList`
- `/management/revenue.revenue_rule/delItem`
- `/management/revenue.revenue_rule/addTier`
- `/management/revenue.revenue_rule/updateTier`
- `/management/revenue.revenue_rule/getTierList`
- `/management/revenue.revenue_rule/delTier`
- `/management/revenue.revenue_rule/saveCouponConfig`
- `/management/revenue.revenue_rule/getCouponConfig`
- `/management/revenue.revenue_rule/bindMachine`
- `/management/revenue.revenue_rule/getMachineList`
- `/management/revenue.revenue_rule/getBoundMachineList`
- `/management/revenue.revenue_rule/unbindMachine`

统一后的后台配置只保留 `saveConfig` 整体保存配置和 `saveScope` 整体保存生效范围。

## 建议同步删除的旧数据库对象

建议新增或执行独立 SQL 文档，删除以下旧表：

```sql
DROP TABLE IF EXISTS `revenue_rule_coupon_scope`;
DROP TABLE IF EXISTS `revenue_rule_coupon`;
DROP TABLE IF EXISTS `revenue_rule_machine`;
DROP TABLE IF EXISTS `revenue_rule_item_tier`;
DROP TABLE IF EXISTS `revenue_rule_item`;
DROP TABLE IF EXISTS `revenue_rule`;
```

如果数据库 `revenue_order` 仍存在旧快照字段，也建议删除：

```sql
ALTER TABLE `revenue_order` DROP COLUMN `rri_id`;
ALTER TABLE `revenue_order` DROP COLUMN `rrit_id`;
ALTER TABLE `revenue_order` DROP COLUMN `rrc_id`;
ALTER TABLE `revenue_order` DROP COLUMN `coupon_code`;
```

执行前需先用 `information_schema.COLUMNS` 判断字段是否存在，避免重复执行报错。

## 自查结果

- 当前 `app` 目录未发现运行时代码直接访问 `revenue_rule_item`、`revenue_rule_item_tier`、`revenue_rule_machine`、`revenue_rule_coupon`、`revenue_rule_coupon_scope`。
- 当前 `app/AppFactory/Kernel/Model/Revenue` 目录只剩新逻辑必需 Model。
- 当前仍需继续清理的是后台旧兼容接口和 Apifox 文档，而不是删除现存新 Model/Trait。
