-- ============================================================================
-- 设备商品库存快照（方案 B）数据库变更
-- 适用：MySQL 5.6+ ｜ 库：kiosk ｜ 目标表：machine_channel_stock（复用既有快照表）
--
-- 背景：
--   machine_channel_stock 原由定时任务 TimeTask/Machine/MachineChannelStockClient::countMcStock()
--   写入，后因"改为实时查询"被停用，当前表为空（0 行）。
--   库存变化统计接口需要"某日期的库存"作为【初始库存/剩余库存】的物理锚点，
--   故恢复该表用途：每日 00:10 生成日终快照（source=daily），并支持历史回填（source=backfill）。
--
-- 变更内容（幂等，可重复执行）：
--   1) 新增 source 列：区分 daily（每日任务，库存拆分准确）与 backfill（历史回填，仅 total 近似）
--   2) 新增唯一索引：(create_date, m_id, g_id, source)，避免重复快照
--   3) 新增查询索引：(m_id, g_id, create_date)，供接口按期取最近快照
--
-- 执行前请备份；脚本不删除任何数据。
-- ============================================================================

SET @db := DATABASE();

-- 1) source 列
SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'machine_channel_stock' AND COLUMN_NAME = 'source'),
    'SELECT ''machine_channel_stock.source already exists'' AS message',
    'ALTER TABLE `machine_channel_stock`
       ADD COLUMN `source` varchar(16) NOT NULL DEFAULT '''' COMMENT ''快照来源：daily-每日定时任务 backfill-历史回填'' AFTER `create_time`'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2) 唯一索引（同日同设备同商品同来源只允许一行）
SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.STATISTICS
           WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'machine_channel_stock' AND INDEX_NAME = 'uk_stat_source_device_goods'),
    'SELECT ''uk_stat_source_device_goods already exists'' AS message',
    'ALTER TABLE `machine_channel_stock`
       ADD UNIQUE KEY `uk_stat_source_device_goods` (`create_date`,`m_id`,`g_id`,`source`)'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3) 查询索引（按设备+商品取最近快照）
SET @sql := (
  SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.STATISTICS
           WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'machine_channel_stock' AND INDEX_NAME = 'idx_m_g_date'),
    'SELECT ''idx_m_g_date already exists'' AS message',
    'ALTER TABLE `machine_channel_stock`
       ADD KEY `idx_m_g_date` (`m_id`,`g_id`,`create_date`)'
  )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ----------------------------------------------------------------------------
-- 部署后执行一次历史回填（默认 90 天；可用 --start/--end 指定）
--   php think stock_snapshot backfill --start=2026-06-19 --end=2026-09-16
-- 每日定时任务（00:10，写入昨日日终快照）：
--   php think stock_snapshot daily
--   （亦可用既有入口：php think time_task machineChannelStock countMcStock）
-- ----------------------------------------------------------------------------
