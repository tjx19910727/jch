-- V2 第三方设备商品、核心商品主动同步
-- 适用 MySQL 5.6；执行前请备份并确认当前账号具备 CREATE TRIGGER 权限。
-- MySQL 5.6 同一张表的同一触发时机/事件只能有一个 Trigger，本脚本每组仅创建一个。
-- 若开启 binary log 且 log_bin_trust_function_creators=0，普通账号创建 Trigger 会报 1419；
-- 推荐由 DBA/SUPER 账号执行 Trigger 部分。不要在本文件中长期修改全局安全变量。
-- 执行前建议由 DBA 检查：
-- SELECT @@version, @@log_bin, @@log_bin_trust_function_creators, @@binlog_format;
-- SHOW GRANTS FOR CURRENT_USER();

CREATE TABLE IF NOT EXISTS `third_party_sync_dirty` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `sync_type` varchar(32) NOT NULL COMMENT 'machine_inventory/core_goods',
  `aggregate_id` varchar(64) NOT NULL COMMENT '设备编号或核心商品ID',
  `operation` varchar(16) NOT NULL DEFAULT 'upsert' COMMENT 'snapshot/upsert/delete',
  `version` bigint unsigned NOT NULL DEFAULT 1 COMMENT '聚合对象变更版本',
  `dispatched_version` bigint unsigned NOT NULL DEFAULT 0 COMMENT '已生成回调的版本',
  `changed_at` int unsigned NOT NULL DEFAULT 0 COMMENT '最近变更时间戳',
  `create_time` int unsigned NOT NULL DEFAULT 0,
  `update_time` int unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_sync_aggregate` (`sync_type`,`aggregate_id`),
  KEY `idx_changed_at` (`changed_at`,`version`,`dispatched_version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='第三方商品同步聚合待处理表';

-- 以下 Trigger 均为单语句，不依赖 mysql 命令行的 DELIMITER 指令，
-- 可由常见数据库管理工具按分号拆分后直接发送给 MySQL Server。

DROP TRIGGER IF EXISTS `trg_third_party_machine_channel_ai`;
CREATE TRIGGER `trg_third_party_machine_channel_ai`
AFTER INSERT ON `machine_channel`
FOR EACH ROW
INSERT INTO `third_party_sync_dirty`
  (`sync_type`,`aggregate_id`,`operation`,`version`,`changed_at`,`create_time`,`update_time`)
SELECT
  'machine_inventory',NEW.machine_id,'snapshot',1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),UNIX_TIMESTAMP()
FROM DUAL
WHERE NEW.machine_id IS NOT NULL AND NEW.machine_id <> ''
  AND EXISTS (SELECT 1 FROM `machine` m WHERE m.machine_id = NEW.machine_id AND m.ao_id = 17)
ON DUPLICATE KEY UPDATE
  `operation`='snapshot',`version`=`version`+1,`changed_at`=UNIX_TIMESTAMP(),`update_time`=UNIX_TIMESTAMP();

DROP TRIGGER IF EXISTS `trg_third_party_machine_channel_au`;
CREATE TRIGGER `trg_third_party_machine_channel_au`
AFTER UPDATE ON `machine_channel`
FOR EACH ROW
INSERT INTO `third_party_sync_dirty`
  (`sync_type`,`aggregate_id`,`operation`,`version`,`changed_at`,`create_time`,`update_time`)
SELECT
  'machine_inventory',NEW.machine_id,'snapshot',1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),UNIX_TIMESTAMP()
FROM DUAL
WHERE NEW.machine_id IS NOT NULL AND NEW.machine_id <> ''
  AND EXISTS (SELECT 1 FROM `machine` m WHERE m.machine_id = NEW.machine_id AND m.ao_id = 17)
UNION ALL
SELECT
  'machine_inventory',OLD.machine_id,'snapshot',1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),UNIX_TIMESTAMP()
FROM DUAL
WHERE NOT (OLD.machine_id <=> NEW.machine_id)
  AND OLD.machine_id IS NOT NULL AND OLD.machine_id <> ''
  AND EXISTS (SELECT 1 FROM `machine` m WHERE m.machine_id = OLD.machine_id AND m.ao_id = 17)
ON DUPLICATE KEY UPDATE
  `operation`='snapshot',`version`=`version`+1,`changed_at`=UNIX_TIMESTAMP(),`update_time`=UNIX_TIMESTAMP();

DROP TRIGGER IF EXISTS `trg_third_party_machine_channel_ad`;
CREATE TRIGGER `trg_third_party_machine_channel_ad`
AFTER DELETE ON `machine_channel`
FOR EACH ROW
INSERT INTO `third_party_sync_dirty`
  (`sync_type`,`aggregate_id`,`operation`,`version`,`changed_at`,`create_time`,`update_time`)
SELECT
  'machine_inventory',OLD.machine_id,'snapshot',1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),UNIX_TIMESTAMP()
FROM DUAL
WHERE OLD.machine_id IS NOT NULL AND OLD.machine_id <> ''
  AND EXISTS (SELECT 1 FROM `machine` m WHERE m.machine_id = OLD.machine_id AND m.ao_id = 17)
ON DUPLICATE KEY UPDATE
  `operation`='snapshot',`version`=`version`+1,`changed_at`=UNIX_TIMESTAMP(),`update_time`=UNIX_TIMESTAMP();

DROP TRIGGER IF EXISTS `trg_third_party_goods_ai`;
CREATE TRIGGER `trg_third_party_goods_ai`
AFTER INSERT ON `goods`
FOR EACH ROW
INSERT INTO `third_party_sync_dirty`
  (`sync_type`,`aggregate_id`,`operation`,`version`,`changed_at`,`create_time`,`update_time`)
SELECT
  'core_goods',CAST(NEW.g_id AS CHAR),'upsert',1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),UNIX_TIMESTAMP()
FROM DUAL
WHERE NEW.ao_id = 17
ON DUPLICATE KEY UPDATE
  `operation`='upsert',`version`=`version`+1,`changed_at`=UNIX_TIMESTAMP(),`update_time`=UNIX_TIMESTAMP();

DROP TRIGGER IF EXISTS `trg_third_party_goods_au`;
CREATE TRIGGER `trg_third_party_goods_au`
AFTER UPDATE ON `goods`
FOR EACH ROW
INSERT INTO `third_party_sync_dirty`
  (`sync_type`,`aggregate_id`,`operation`,`version`,`changed_at`,`create_time`,`update_time`)
SELECT
  'core_goods',CAST(OLD.g_id AS CHAR),'delete',1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),UNIX_TIMESTAMP()
FROM DUAL
WHERE OLD.ao_id = 17 AND (NEW.ao_id <> 17 OR NOT (OLD.g_id <=> NEW.g_id))
UNION ALL
SELECT
  'core_goods',CAST(NEW.g_id AS CHAR),'upsert',1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),UNIX_TIMESTAMP()
FROM DUAL
WHERE NEW.ao_id = 17
UNION ALL
SELECT
  'machine_inventory',mc.machine_id,'snapshot',1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),UNIX_TIMESTAMP()
FROM `machine_channel` mc
INNER JOIN `machine` m ON m.machine_id = mc.machine_id AND m.ao_id = 17
WHERE (OLD.ao_id = 17 OR NEW.ao_id = 17)
  AND mc.g_id IN (OLD.g_id,NEW.g_id) AND mc.machine_id <> ''
GROUP BY mc.machine_id
ON DUPLICATE KEY UPDATE
  `operation`=VALUES(`operation`),`version`=`version`+1,`changed_at`=UNIX_TIMESTAMP(),`update_time`=UNIX_TIMESTAMP();

DROP TRIGGER IF EXISTS `trg_third_party_goods_bd`;
CREATE TRIGGER `trg_third_party_goods_bd`
BEFORE DELETE ON `goods`
FOR EACH ROW
INSERT INTO `third_party_sync_dirty`
  (`sync_type`,`aggregate_id`,`operation`,`version`,`changed_at`,`create_time`,`update_time`)
SELECT
  'core_goods',CAST(OLD.g_id AS CHAR),'delete',1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),UNIX_TIMESTAMP()
FROM DUAL
WHERE OLD.ao_id = 17
UNION ALL
SELECT
  'machine_inventory',mc.machine_id,'snapshot',1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP(),UNIX_TIMESTAMP()
FROM `machine_channel` mc
INNER JOIN `machine` m ON m.machine_id = mc.machine_id AND m.ao_id = 17
WHERE OLD.ao_id = 17 AND mc.g_id = OLD.g_id AND mc.machine_id <> ''
GROUP BY mc.machine_id
ON DUPLICATE KEY UPDATE
  `operation`=VALUES(`operation`),`version`=`version`+1,`changed_at`=UNIX_TIMESTAMP(),`update_time`=UNIX_TIMESTAMP();

-- 回滚时先关闭 third_party_sync.enabled，再执行：
-- DROP TRIGGER IF EXISTS `trg_third_party_machine_channel_ai`;
-- DROP TRIGGER IF EXISTS `trg_third_party_machine_channel_au`;
-- DROP TRIGGER IF EXISTS `trg_third_party_machine_channel_ad`;
-- DROP TRIGGER IF EXISTS `trg_third_party_goods_ai`;
-- DROP TRIGGER IF EXISTS `trg_third_party_goods_au`;
-- DROP TRIGGER IF EXISTS `trg_third_party_goods_bd`;
-- 待确认无需保留未发送任务后，再执行：DROP TABLE `third_party_sync_dirty`;
