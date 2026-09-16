-- 设备理货记录表。
-- 说明：本文件只负责建表，请由运维/开发人员确认后手动执行。

CREATE TABLE IF NOT EXISTS `machine_tally_batch` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `m_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '设备主键',
  `machine_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '设备编号',
  `batch_id` VARCHAR(64) NOT NULL COMMENT '设备端理货批次ID',
  `mode` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '理货模式',
  `batch_started_at` DATETIME(6) DEFAULT NULL COMMENT '设备端批次开始时间',
  `batch_ended_at` DATETIME(6) DEFAULT NULL COMMENT '设备端批次结束时间',
  `summary_success` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '批次汇总是否成功：0否，1是',
  `success_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '设备汇总成功数',
  `failure_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '设备汇总失败数',
  `actual_success_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '后台按明细计算的成功数',
  `actual_failure_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '后台按明细计算的失败数',
  `item_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '本批次明细数',
  `interrupted` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否中断：0否，1是',
  `data_status` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '数据状态：1接收中，2完整，3汇总数量不一致，4已中断',
  `payload_hash` CHAR(64) NOT NULL COMMENT '整批数据幂等哈希',
  `raw_summary` JSON DEFAULT NULL COMMENT '设备端原始汇总记录',
  `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `updated_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_machine_batch` (`machine_id`, `batch_id`),
  KEY `idx_m_id_started_at` (`m_id`, `batch_started_at`),
  KEY `idx_data_status` (`data_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='设备理货批次';

CREATE TABLE IF NOT EXISTS `machine_tally_record` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `batch_log_id` BIGINT UNSIGNED NOT NULL COMMENT '理货批次主表ID',
  `m_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '设备主键',
  `machine_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '设备编号',
  `batch_id` VARCHAR(64) NOT NULL COMMENT '设备端理货批次ID',
  `device_item_id` VARCHAR(64) DEFAULT NULL COMMENT '设备端明细唯一ID',
  `event_key` CHAR(64) NOT NULL COMMENT '明细幂等哈希',
  `sequence_no` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '批次内顺序',
  `slot_id` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '设备端槽位编号',
  `mc_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '匹配到的后台货道ID',
  `channel_position` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '货道位置',
  `success` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '执行结果：0失败，1成功',
  `error_code` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '失败代码',
  `error_message` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '失败原因',
  `event_at` DATETIME(6) DEFAULT NULL COMMENT '设备端明细时间',
  `raw_record` JSON DEFAULT NULL COMMENT '设备端原始明细记录',
  `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_event_key` (`event_key`),
  KEY `idx_batch_sequence` (`batch_log_id`, `sequence_no`),
  KEY `idx_m_id_event_at` (`m_id`, `event_at`),
  KEY `idx_mc_id` (`mc_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='设备理货批次明细';
