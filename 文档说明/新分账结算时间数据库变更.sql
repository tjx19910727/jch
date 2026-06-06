-- 新分账结算时间数据库变更
-- settlement_type: 1即时分账，2 T+N分账
-- settlement_days: T+N中的N，即时分账固定为0

ALTER TABLE `revenue_pay_channel`
  ADD COLUMN `settlement_type` tinyint(1) DEFAULT 1 COMMENT '分账时间类型：1即时分账，2 T+N分账' AFTER `channel_name`,
  ADD COLUMN `settlement_days` int DEFAULT 0 COMMENT 'T+N分账天数，即时分账为0' AFTER `settlement_type`;

ALTER TABLE `revenue_order`
  ADD COLUMN `settlement_type` tinyint(1) DEFAULT 1 COMMENT '分账时间类型快照：1即时分账，2 T+N分账' AFTER `source`,
  ADD COLUMN `settlement_days` int DEFAULT 0 COMMENT 'T+N分账天数快照' AFTER `settlement_type`,
  ADD COLUMN `planned_revenue_time` int DEFAULT NULL COMMENT '计划结算时间' AFTER `settlement_days`,
  ADD KEY `idx_planned_revenue` (`status`, `planned_revenue_time`);

-- 历史配置和历史待支付分账单默认按即时分账处理。
UPDATE `revenue_pay_channel`
SET `settlement_type` = 1, `settlement_days` = 0
WHERE `settlement_type` IS NULL;

UPDATE `revenue_order`
SET `settlement_type` = 1, `settlement_days` = 0
WHERE `settlement_type` IS NULL;
