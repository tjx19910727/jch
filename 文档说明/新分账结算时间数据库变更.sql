-- 新分账结算时间数据库变更
-- settlement_type: 1即时分账，2 T+N分账
-- settlement_days: T+N中的N，即时分账固定为0
-- 结算周期归属具体 revenue_rule，revenue_order 保存生成时快照。

ALTER TABLE `revenue_rule`
  ADD COLUMN `settlement_type` tinyint(1) DEFAULT 1 COMMENT '分账时间类型：1即时分账，2 T+N分账' AFTER `tier_calc_mode`,
  ADD COLUMN `settlement_days` int DEFAULT 0 COMMENT 'T+N分账天数，即时分账为0' AFTER `settlement_type`;

ALTER TABLE `revenue_order`
  ADD COLUMN `settlement_type` tinyint(1) DEFAULT 1 COMMENT '分账时间类型快照：1即时分账，2 T+N分账' AFTER `source`,
  ADD COLUMN `settlement_days` int DEFAULT 0 COMMENT 'T+N分账天数快照' AFTER `settlement_type`,
  ADD COLUMN `planned_revenue_time` int DEFAULT NULL COMMENT '计划结算时间' AFTER `settlement_days`,
  ADD KEY `idx_planned_revenue` (`status`, `planned_revenue_time`);

UPDATE `revenue_rule`
SET `settlement_type` = 1, `settlement_days` = 0
WHERE `settlement_type` IS NULL;

UPDATE `revenue_order`
SET `settlement_type` = 1, `settlement_days` = 0
WHERE `settlement_type` IS NULL;
