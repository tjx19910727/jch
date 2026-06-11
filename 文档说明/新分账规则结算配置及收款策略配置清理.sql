-- 新分账规则结算配置及收款策略配置清理
-- 执行前提：
-- 1. 已为所有需要普通分账的设备绑定 rule_mode=1 规则及有效明细。
-- 2. 已确认原 revenue_payee_config 中按 sp_id 配置的差异不再需要。
-- 3. 已停用仍读取 revenue_payee_config 的旧版本代码。

ALTER TABLE `revenue_rule`
  ADD COLUMN `settlement_type` tinyint(1) DEFAULT 1 COMMENT '分账时间类型：1即时分账，2 T+N分账' AFTER `tier_calc_mode`,
  ADD COLUMN `settlement_days` int DEFAULT 0 COMMENT 'T+N分账天数，即时分账为0' AFTER `settlement_type`;

UPDATE `revenue_rule`
SET `settlement_type` = 1,
    `settlement_days` = 0
WHERE `settlement_type` IS NULL
   OR `settlement_type` NOT IN (1, 2)
   OR `settlement_days` IS NULL
   OR `settlement_days` < 0
   OR (`settlement_type` = 1 AND `settlement_days` <> 0)
   OR (`settlement_type` = 2 AND `settlement_days` < 1);

-- 确认新代码和规则配置已生效后执行。
DROP TABLE IF EXISTS `revenue_payee_config`;
