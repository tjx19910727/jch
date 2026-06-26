-- 本次分账重构数据库修改 SQL
-- 适用范围：
--   1. 分账配置统一到 revenue_rule_config。
--   2. 生效设备/商品统一到 revenue_rule_config_scope。
--   3. 删除不再启用的旧 revenue_ 分账表。
--   4. 清理 revenue_order 中旧分账明细/优惠券快照字段。
--   5. 将 revenue_order.rr_id 重命名为 rrcfg_id，明确表示命中的统一分账配置ID。
--
-- 执行前置条件：
--   1. 代码已改为使用 revenue_order.rrcfg_id，不再写入 revenue_order.rr_id。
--   2. 不需要迁移旧配置数据；旧 revenue_rule / revenue_rule_item / revenue_rule_machine / revenue_rule_coupon 等表会被删除。
--   3. 建议先备份 revenue_order、revenue_rule_config、revenue_rule_config_scope 及旧 revenue_ 配置表。

SET @db = DATABASE();

CREATE TABLE IF NOT EXISTS `revenue_rule_config` (
  `rrcfg_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '分账配置ID',
  `config_name` varchar(100) NOT NULL COMMENT '配置名称',
  `rule_mode` tinyint(1) NOT NULL COMMENT '模式：1基础/设备分账，2设备出租，3设备分账历史兼容，4设备商品分账，5优惠券分账',
  `base_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '分账基数：1订单总额，2扣除已分账金额后的剩余金额',
  `turnover_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '阶梯营业额口径：1净营业额，2支付成功金额',
  `tier_calc_mode` tinyint(1) NOT NULL DEFAULT '1' COMMENT '阶梯计算模式：1整单命中，2跨阶梯拆分',
  `settlement_type` tinyint(1) NOT NULL DEFAULT '1' COMMENT '结算类型：1即时分账，2 T+N分账',
  `settlement_days` int(11) NOT NULL DEFAULT '0' COMMENT 'T+N天数',
  `coupon_code` varchar(6) DEFAULT NULL COMMENT '优惠券编码，rule_mode=5使用',
  `discount_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '优惠方式：0不调整实付，1固定金额，2优惠比例',
  `discount_value` decimal(10,3) NOT NULL DEFAULT '0.000' COMMENT '优惠金额或比例',
  `use_limit` int(11) NOT NULL DEFAULT '0' COMMENT '可使用次数',
  `used_count` int(11) NOT NULL DEFAULT '0' COMMENT '已使用次数',
  `remain_count` int(11) NOT NULL DEFAULT '0' COMMENT '剩余次数',
  `expire_time` int(11) DEFAULT NULL COMMENT '过期时间，0或空表示不过期',
  `receiver_config` mediumtext NOT NULL COMMENT '分账接收方配置JSON：账户、比例、固定金额、阶梯等',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：1启用，2停用',
  `creator` int(11) DEFAULT NULL COMMENT '创建人',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`rrcfg_id`),
  UNIQUE KEY `uk_coupon_code` (`coupon_code`),
  KEY `idx_mode_status` (`rule_mode`,`status`),
  KEY `idx_status_expire` (`status`,`expire_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='统一分账配置表';

CREATE TABLE IF NOT EXISTS `revenue_rule_config_scope` (
  `rrcs_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '分账配置生效范围ID',
  `rrcfg_id` int(11) NOT NULL COMMENT '分账配置ID',
  `m_id` int(11) NOT NULL DEFAULT '0' COMMENT '设备ID，0表示全部设备',
  `machine_id` varchar(64) DEFAULT '' COMMENT '设备编号快照',
  `ao_id` int(11) DEFAULT NULL COMMENT '设备组织ID快照',
  `g_id` int(11) NOT NULL DEFAULT '0' COMMENT '商品ID，0表示全部商品',
  `mg_id` int(11) NOT NULL DEFAULT '0' COMMENT '设备商品ID，0表示不限定设备商品；指定mg_id时必须有明确m_id',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '匹配优先级，数字越小越优先',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：1启用，2停用',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`rrcs_id`),
  UNIQUE KEY `uk_config_scope` (`rrcfg_id`,`m_id`,`g_id`,`mg_id`),
  KEY `idx_machine_status` (`m_id`,`status`),
  KEY `idx_goods_status` (`g_id`,`mg_id`,`status`),
  KEY `idx_config_status` (`rrcfg_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='统一分账配置生效范围表';

-- revenue_order 配置ID字段修正：rr_id 是旧策略组命名，统一配置后改为 rrcfg_id。
SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'revenue_order' AND COLUMN_NAME = 'rrcfg_id'),
  'SELECT ''revenue_order.rrcfg_id already exists'' AS message',
  IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'revenue_order' AND COLUMN_NAME = 'rr_id'),
    'ALTER TABLE `revenue_order` CHANGE COLUMN `rr_id` `rrcfg_id` int(11) DEFAULT 0 COMMENT ''命中的统一分账配置ID，对应 revenue_rule_config.rrcfg_id''',
    'ALTER TABLE `revenue_order` ADD COLUMN `rrcfg_id` int(11) DEFAULT 0 COMMENT ''命中的统一分账配置ID，对应 revenue_rule_config.rrcfg_id'' AFTER `rule_mode`'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
  EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'revenue_order' AND INDEX_NAME = 'idx_rrcfg_status'),
  'SELECT ''revenue_order.idx_rrcfg_status already exists'' AS message',
  'ALTER TABLE `revenue_order` ADD INDEX `idx_rrcfg_status` (`rrcfg_id`,`status`)'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 删除 revenue_order 旧分账明细与旧优惠券快照字段。
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

-- 删除旧分账配置表。统一配置不再读取这些表。
DROP TABLE IF EXISTS `revenue_rule_coupon_scope`;
DROP TABLE IF EXISTS `revenue_rule_coupon`;
DROP TABLE IF EXISTS `revenue_rule_machine`;
DROP TABLE IF EXISTS `revenue_rule_item_tier`;
DROP TABLE IF EXISTS `revenue_rule_item`;
DROP TABLE IF EXISTS `revenue_rule`;
DROP TABLE IF EXISTS `revenue_payee_config`;

-- 删除旧后台接口权限节点，避免页面继续调用已移除接口。
DELETE FROM `auth_node`
WHERE `url` IN (
  '/management/revenue.revenue_rule/add',
  '/management/revenue.revenue_rule/update',
  '/management/revenue.revenue_rule/del',
  '/management/revenue.revenue_rule/addItem',
  '/management/revenue.revenue_rule/addProductItem',
  '/management/revenue.revenue_rule/updateItem',
  '/management/revenue.revenue_rule/getItemList',
  '/management/revenue.revenue_rule/delItem',
  '/management/revenue.revenue_rule/addTier',
  '/management/revenue.revenue_rule/updateTier',
  '/management/revenue.revenue_rule/getTierList',
  '/management/revenue.revenue_rule/delTier',
  '/management/revenue.revenue_rule/saveCouponConfig',
  '/management/revenue.revenue_rule/getCouponConfig',
  '/management/revenue.revenue_rule/bindMachine',
  '/management/revenue.revenue_rule/getMachineList',
  '/management/revenue.revenue_rule/getBoundMachineList',
  '/management/revenue.revenue_rule/unbindMachine'
);
