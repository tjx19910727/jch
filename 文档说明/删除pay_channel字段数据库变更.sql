-- 删除 pay_channel 字段数据库变更
-- 目的：订单和新分账触发配置不再使用 pay_channel/pay_channel_name，统一按 pay_type 等既有字段判断。
-- 执行前建议先确认新代码已部署，且分账触发逻辑已改为读取 revenue_pay_channel.pay_type。

SET @db = DATABASE();

SET @sql = IF(
  EXISTS (
    SELECT 1
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'sale_orders'
      AND COLUMN_NAME = 'pay_channel'
  ),
  'CREATE TABLE IF NOT EXISTS `sale_orders_pay_channel_backup` AS SELECT `order_id`, `pay_channel`, `pay_channel_name` FROM `sale_orders`',
  'SELECT ''sale_orders.pay_channel not exists, skip backup'' AS message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS (
    SELECT 1
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'sale_orders'
      AND COLUMN_NAME = 'pay_channel_name'
  ),
  'ALTER TABLE `sale_orders` DROP COLUMN `pay_channel_name`',
  'SELECT ''sale_orders.pay_channel_name already removed'' AS message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE `revenue_pay_channel`
SET `pay_type` = 0
WHERE `pay_type` IS NULL;

SET @sql = IF(
  EXISTS (
    SELECT 1
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'sale_orders'
      AND COLUMN_NAME = 'pay_channel'
  ),
  'ALTER TABLE `sale_orders` DROP COLUMN `pay_channel`',
  'SELECT ''sale_orders.pay_channel already removed'' AS message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS (
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
  EXISTS (
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
  NOT EXISTS (
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

DROP TABLE IF EXISTS `payment_pay_type_channel_relation`;
