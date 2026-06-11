-- 新分账渠道旧结算字段清理
-- 前置条件：
-- 1. 已执行《新分账结算配置归属重构.sql》完成数据回填。
-- 2. 新代码已部署，结算配置已改为从 revenue_payee_config 读取和保存。
-- 3. 已验证即时分账和 T+N 分账订单均能生成正确快照。

ALTER TABLE `revenue_pay_channel`
  DROP COLUMN `settlement_type`,
  DROP COLUMN `settlement_days`;
