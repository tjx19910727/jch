-- revenue_pay_channel 切换为 pay_type 触发配置
-- 用途：删除 pay_channel 后，后台新增/修改分账触发配置统一使用 sale_orders.pay_type。

SET @db = DATABASE();

SET @sql = IF(
  NOT EXISTS(
    SELECT 1
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'revenue_pay_channel'
      AND COLUMN_NAME = 'pay_type'
  ),
  'ALTER TABLE `revenue_pay_channel` ADD COLUMN `pay_type` int DEFAULT NULL COMMENT ''订单支付类型，对应 sale_orders.pay_type'' AFTER `rpc_id`',
  'SELECT ''revenue_pay_channel.pay_type already exists'' AS message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS(
    SELECT 1
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'revenue_pay_channel'
      AND INDEX_NAME = 'uk_pay_channel'
  ),
  'ALTER TABLE `revenue_pay_channel` DROP INDEX `uk_pay_channel`',
  'SELECT ''revenue_pay_channel.uk_pay_channel already removed'' AS message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS(
    SELECT 1
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'revenue_pay_channel'
      AND COLUMN_NAME = 'pay_channel'
  ),
  'ALTER TABLE `revenue_pay_channel` DROP COLUMN `pay_channel`',
  'SELECT ''revenue_pay_channel.pay_channel already removed'' AS message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  NOT EXISTS(
    SELECT 1
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'revenue_pay_channel'
      AND INDEX_NAME = 'uk_pay_type'
  ),
  'ALTER TABLE `revenue_pay_channel` ADD UNIQUE KEY `uk_pay_type` (`pay_type`)',
  'SELECT ''revenue_pay_channel.uk_pay_type already exists'' AS message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE `revenue_pay_channel`
SET `pay_type` = 0
WHERE `pay_type` IS NULL;

ALTER TABLE `revenue_pay_channel`
  MODIFY COLUMN `pay_type` int NOT NULL COMMENT '订单支付类型，对应 sale_orders.pay_type';
