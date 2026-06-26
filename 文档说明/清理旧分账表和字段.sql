-- 旧分账表与旧订单快照字段清理脚本
-- 执行前请确认当前代码已切换到统一分账配置：
--   1. 分账配置表：revenue_rule_config
--   2. 生效范围表：revenue_rule_config_scope
--   3. 分账订单表：revenue_order
-- 本脚本不删除仍在使用的 revenue_order、revenue_account、revenue_pay_channel。

DROP TABLE IF EXISTS `revenue_rule_coupon_scope`;
DROP TABLE IF EXISTS `revenue_rule_coupon`;
DROP TABLE IF EXISTS `revenue_rule_machine`;
DROP TABLE IF EXISTS `revenue_rule_item_tier`;
DROP TABLE IF EXISTS `revenue_rule_item`;
DROP TABLE IF EXISTS `revenue_rule`;
DROP TABLE IF EXISTS `revenue_payee_config`;

SET @db = DATABASE();

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'revenue_order' AND COLUMN_NAME = 'rri_id'),
  'ALTER TABLE `revenue_order` DROP COLUMN `rri_id`',
  'SELECT ''revenue_order.rri_id already absent'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'revenue_order' AND COLUMN_NAME = 'rrit_id'),
  'ALTER TABLE `revenue_order` DROP COLUMN `rrit_id`',
  'SELECT ''revenue_order.rrit_id already absent'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'revenue_order' AND COLUMN_NAME = 'rrc_id'),
  'ALTER TABLE `revenue_order` DROP COLUMN `rrc_id`',
  'SELECT ''revenue_order.rrc_id already absent'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'revenue_order' AND COLUMN_NAME = 'coupon_code'),
  'ALTER TABLE `revenue_order` DROP COLUMN `coupon_code`',
  'SELECT ''revenue_order.coupon_code already absent'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'revenue_order' AND COLUMN_NAME = 'coupon_scope_type'),
  'ALTER TABLE `revenue_order` DROP COLUMN `coupon_scope_type`',
  'SELECT ''revenue_order.coupon_scope_type already absent'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'revenue_order' AND COLUMN_NAME = 'coupon_use_count_before'),
  'ALTER TABLE `revenue_order` DROP COLUMN `coupon_use_count_before`',
  'SELECT ''revenue_order.coupon_use_count_before already absent'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'revenue_order' AND COLUMN_NAME = 'coupon_use_count_after'),
  'ALTER TABLE `revenue_order` DROP COLUMN `coupon_use_count_after`',
  'SELECT ''revenue_order.coupon_use_count_after already absent'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'revenue_order' AND COLUMN_NAME = 'coupon_use_deducted'),
  'ALTER TABLE `revenue_order` DROP COLUMN `coupon_use_deducted`',
  'SELECT ''revenue_order.coupon_use_deducted already absent'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
