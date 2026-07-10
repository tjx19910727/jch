-- 统一分账配置数据库变更
-- 新模型只使用两张配置表：revenue_rule_config + revenue_rule_config_scope。
-- 不做旧配置数据迁移；旧配置表可保留只读一段时间，待确认无调用后再清理。

CREATE TABLE IF NOT EXISTS `revenue_rule_config` (
  `rrcfg_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '分账配置ID',
  `config_name` varchar(100) NOT NULL COMMENT '配置名称',
  `rule_mode` tinyint(1) NOT NULL COMMENT '模式：1基础/设备分账，2设备出租，3设备分账历史兼容，4设备商品分账，5优惠券分账',
  `base_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '分账基数：1订单总额，2扣除出租商品后金额',
  `turnover_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '阶梯营业额口径：1净营业额，2支付成功金额',
  `tier_calc_mode` tinyint(1) NOT NULL DEFAULT '1' COMMENT '阶梯命中方式：1整单命中，2跨阶梯拆分',
  `settlement_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '结算类型：1即时分账，2 T+N分账',
  `settlement_days` int(11) NOT NULL DEFAULT '0' COMMENT 'T+N天数',
  `coupon_id` int(11) NOT NULL DEFAULT '0' COMMENT '关联活动优惠券ID，rule_mode=5使用',
  `cost_assume` tinyint(1) NOT NULL DEFAULT '0' COMMENT '优惠券成本承担方式：0无需承担成本，1分账方承担成本，2各自按分账比例承担成本',
  `receiver_config` mediumtext NOT NULL COMMENT '分账接收方配置JSON：账户、比例、固定金额、阶梯等',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：1启用，2停用',
  `creator` int(11) DEFAULT NULL COMMENT '创建人',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`rrcfg_id`),
  KEY `idx_mode_status` (`rule_mode`,`status`),
  KEY `idx_coupon_id` (`coupon_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='统一分账配置表';

CREATE TABLE IF NOT EXISTS `revenue_rule_config_scope` (
  `rrcs_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '分账配置生效范围ID',
  `rrcfg_id` int(11) NOT NULL COMMENT '分账配置ID',
  `m_id` int(11) NOT NULL DEFAULT '0' COMMENT '设备ID，0表示全部设备',
  `machine_id` varchar(64) DEFAULT '' COMMENT '设备编号快照',
  `ao_id` int(11) DEFAULT NULL COMMENT '设备组织ID快照',
  `g_id` int(11) NOT NULL DEFAULT '0' COMMENT '商品ID，0表示全部商品',
  `mg_id` int(11) NOT NULL DEFAULT '0' COMMENT '设备商品ID，0表示不限定设备商品；指定mg_id时必须有明确m_id',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '匹配优先级，数字越小越优先；同sort按精确范围优先',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：1启用，2停用',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`rrcs_id`),
  UNIQUE KEY `uk_config_scope` (`rrcfg_id`,`m_id`,`g_id`,`mg_id`),
  KEY `idx_machine_status` (`m_id`,`status`),
  KEY `idx_goods_status` (`g_id`,`mg_id`,`status`),
  KEY `idx_config_status` (`rrcfg_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='统一分账配置生效范围表';

-- 范围存储规则：
-- 1. m_id=0,g_id=0,mg_id=0 表示全部设备+全部商品。
-- 2. m_id>0,g_id=0,mg_id=0 表示指定设备+全部商品。
-- 3. m_id=0,g_id>0,mg_id=0 表示全部设备+指定商品。
-- 4. m_id>0,g_id>0,mg_id=0 表示指定设备+指定商品。
-- 5. m_id>0,g_id>0,mg_id>0 表示精确到设备商品库记录，保存时应由 mg_id 反查并校验 m_id/g_id。
-- 6. 匹配优先级：设备商品精确 > 设备+商品 > 设备全商品 > 全设备商品 > 全局。

-- 已存在环境升级：优惠券分账改为关联 activity_coupon.c_id，不再在 revenue_rule_config 重复保存优惠券编码、优惠金额、次数和过期时间。
SET @db = DATABASE();

SET @sql = IF(
  NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'revenue_rule_config' AND COLUMN_NAME = 'coupon_id'),
  'ALTER TABLE `revenue_rule_config` ADD COLUMN `coupon_id` int(11) NOT NULL DEFAULT ''0'' COMMENT ''关联活动优惠券ID，rule_mode=5使用'' AFTER `settlement_days`',
  'SELECT ''revenue_rule_config.coupon_id already exists'' AS message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'revenue_rule_config' AND COLUMN_NAME = 'cost_assume'),
  'ALTER TABLE `revenue_rule_config` ADD COLUMN `cost_assume` tinyint(1) NOT NULL DEFAULT ''0'' COMMENT ''优惠券成本承担方式：0无需承担成本，1分账方承担成本，2各自按分账比例承担成本'' AFTER `coupon_id`',
  'SELECT ''revenue_rule_config.cost_assume already exists'' AS message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'revenue_rule_config' AND COLUMN_NAME = 'coupon_code'),
  'UPDATE `revenue_rule_config` rrc INNER JOIN `activity_coupon` ac ON ac.`code` = rrc.`coupon_code` SET rrc.`coupon_id` = ac.`c_id` WHERE rrc.`rule_mode` = 5 AND rrc.`coupon_id` = 0',
  'SELECT ''revenue_rule_config.coupon_code already absent, skip activity_coupon mapping'' AS message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'revenue_rule_config' AND COLUMN_NAME = 'coupon_code'),
  'UPDATE `revenue_rule_config` rrc INNER JOIN `activity_coupon_used` acu ON acu.`code` = rrc.`coupon_code` SET rrc.`coupon_id` = acu.`c_id` WHERE rrc.`rule_mode` = 5 AND rrc.`coupon_id` = 0',
  'SELECT ''revenue_rule_config.coupon_code already absent, skip activity_coupon_used mapping'' AS message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'revenue_rule_config' AND COLUMN_NAME = 'coupon_code'),
  'SELECT `rrcfg_id`, `config_name`, `coupon_code` FROM `revenue_rule_config` WHERE `rule_mode` = 5 AND `coupon_id` = 0',
  'SELECT ''revenue_rule_config.coupon_code already absent, skip unresolved coupon check'' AS message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'revenue_rule_config' AND INDEX_NAME = 'uk_coupon_code'),
  'ALTER TABLE `revenue_rule_config` DROP INDEX `uk_coupon_code`',
  'SELECT ''revenue_rule_config.uk_coupon_code already absent'' AS message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'revenue_rule_config' AND INDEX_NAME = 'idx_status_expire'),
  'ALTER TABLE `revenue_rule_config` DROP INDEX `idx_status_expire`',
  'SELECT ''revenue_rule_config.idx_status_expire already absent'' AS message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  NOT EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'revenue_rule_config' AND INDEX_NAME = 'idx_coupon_id'),
  'ALTER TABLE `revenue_rule_config` ADD INDEX `idx_coupon_id` (`coupon_id`)',
  'SELECT ''revenue_rule_config.idx_coupon_id already exists'' AS message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @column_name = 'coupon_code';
SET @sql = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'revenue_rule_config' AND COLUMN_NAME = @column_name), 'ALTER TABLE `revenue_rule_config` DROP COLUMN `coupon_code`', 'SELECT ''revenue_rule_config.coupon_code already absent'' AS message');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @column_name = 'discount_type';
SET @sql = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'revenue_rule_config' AND COLUMN_NAME = @column_name), 'ALTER TABLE `revenue_rule_config` DROP COLUMN `discount_type`', 'SELECT ''revenue_rule_config.discount_type already absent'' AS message');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @column_name = 'discount_value';
SET @sql = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'revenue_rule_config' AND COLUMN_NAME = @column_name), 'ALTER TABLE `revenue_rule_config` DROP COLUMN `discount_value`', 'SELECT ''revenue_rule_config.discount_value already absent'' AS message');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @column_name = 'use_limit';
SET @sql = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'revenue_rule_config' AND COLUMN_NAME = @column_name), 'ALTER TABLE `revenue_rule_config` DROP COLUMN `use_limit`', 'SELECT ''revenue_rule_config.use_limit already absent'' AS message');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @column_name = 'used_count';
SET @sql = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'revenue_rule_config' AND COLUMN_NAME = @column_name), 'ALTER TABLE `revenue_rule_config` DROP COLUMN `used_count`', 'SELECT ''revenue_rule_config.used_count already absent'' AS message');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @column_name = 'remain_count';
SET @sql = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'revenue_rule_config' AND COLUMN_NAME = @column_name), 'ALTER TABLE `revenue_rule_config` DROP COLUMN `remain_count`', 'SELECT ''revenue_rule_config.remain_count already absent'' AS message');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @column_name = 'expire_time';
SET @sql = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'revenue_rule_config' AND COLUMN_NAME = @column_name), 'ALTER TABLE `revenue_rule_config` DROP COLUMN `expire_time`', 'SELECT ''revenue_rule_config.expire_time already absent'' AS message');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
