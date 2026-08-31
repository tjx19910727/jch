-- revenue_pay_channel 切换为 pay_type 触发配置
-- 有历史数据时必须先执行“revenue_pay_channel历史配置安全迁移.sql”完成显式映射。

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

SET @unmapped_count := (
  SELECT COUNT(*)
  FROM `revenue_pay_channel`
  WHERE `pay_type` IS NULL
);

-- 禁止把历史记录统一映射为 pay_type=0；存在未映射数据时停止结构清理。
SET @sql = IF(
  @unmapped_count = 0 AND EXISTS(
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
  @unmapped_count = 0 AND EXISTS(
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
  @unmapped_count = 0 AND NOT EXISTS(
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

SET @sql = IF(
  @unmapped_count = 0,
  'ALTER TABLE `revenue_pay_channel` MODIFY COLUMN `pay_type` int NOT NULL COMMENT ''订单支付类型，对应 sale_orders.pay_type''',
  'SELECT ''STOP: revenue_pay_channel has unmapped rows; run explicit history migration first'' AS message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT @unmapped_count AS unmapped_count;
