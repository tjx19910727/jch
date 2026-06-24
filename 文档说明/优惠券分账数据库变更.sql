-- 优惠券分账数据库变更
-- rule_mode=5：优惠券分账
-- 优惠券编码全局唯一；固定金额按每笔订单计算；支付成功时扣减次数。

CREATE TABLE IF NOT EXISTS `revenue_rule_coupon` (
  `rrc_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '优惠券分账配置ID',
  `rr_id` int(11) NOT NULL COMMENT '分账策略ID',
  `coupon_code` varchar(6) NOT NULL COMMENT '优惠券编码：非0开头6位数字',
  `use_limit` int(11) NOT NULL DEFAULT '0' COMMENT '可使用次数',
  `used_count` int(11) NOT NULL DEFAULT '0' COMMENT '已使用次数',
  `remain_count` int(11) NOT NULL DEFAULT '0' COMMENT '剩余次数',
  `expire_time` int(11) DEFAULT NULL COMMENT '过期时间，为空或0表示不过期',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：1启用，2停用',
  `creator` int(11) DEFAULT NULL COMMENT '创建人',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`rrc_id`),
  UNIQUE KEY `uk_coupon_code` (`coupon_code`),
  KEY `idx_rr_id` (`rr_id`),
  KEY `idx_status_expire` (`status`,`expire_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='优惠券分账配置';

CREATE TABLE IF NOT EXISTS `revenue_rule_coupon_scope` (
  `rrcs_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '优惠券分账适用范围ID',
  `rrc_id` int(11) NOT NULL COMMENT '优惠券分账配置ID',
  `m_id` int(11) NOT NULL DEFAULT '0' COMMENT '设备ID，0表示不限设备',
  `machine_id` varchar(64) DEFAULT '' COMMENT '设备编号快照',
  `g_id` int(11) NOT NULL DEFAULT '0' COMMENT '商品ID，0表示不限商品',
  `mg_id` int(11) NOT NULL DEFAULT '0' COMMENT '设备商品ID，0表示不限设备商品',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：1启用，2停用',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`rrcs_id`),
  KEY `idx_rrc_id` (`rrc_id`),
  KEY `idx_scope` (`m_id`,`g_id`,`mg_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='优惠券分账适用范围';

SET @db := DATABASE();

SET @sql := (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `sale_orders` ADD COLUMN `revenue_coupon_code` varchar(6) DEFAULT '''' COMMENT ''分账优惠券编码'' AFTER `coupon_id`',
    'SELECT ''sale_orders.revenue_coupon_code already exists'' AS message'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sale_orders' AND COLUMN_NAME = 'revenue_coupon_code'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `revenue_order` ADD COLUMN `rrc_id` int(11) DEFAULT 0 COMMENT ''优惠券分账配置ID'' AFTER `rrit_id`',
    'SELECT ''revenue_order.rrc_id already exists'' AS message'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'revenue_order' AND COLUMN_NAME = 'rrc_id'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `revenue_order` ADD COLUMN `coupon_code` varchar(6) DEFAULT '''' COMMENT ''分账优惠券编码'' AFTER `rrc_id`',
    'SELECT ''revenue_order.coupon_code already exists'' AS message'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'revenue_order' AND COLUMN_NAME = 'coupon_code'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `revenue_order` ADD COLUMN `coupon_scope_type` varchar(32) DEFAULT '''' COMMENT ''优惠券适用范围类型'' AFTER `coupon_code`',
    'SELECT ''revenue_order.coupon_scope_type already exists'' AS message'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'revenue_order' AND COLUMN_NAME = 'coupon_scope_type'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `revenue_order` ADD COLUMN `coupon_use_count_before` int(11) DEFAULT 0 COMMENT ''扣减前剩余次数'' AFTER `coupon_scope_type`',
    'SELECT ''revenue_order.coupon_use_count_before already exists'' AS message'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'revenue_order' AND COLUMN_NAME = 'coupon_use_count_before'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `revenue_order` ADD COLUMN `coupon_use_count_after` int(11) DEFAULT 0 COMMENT ''扣减后剩余次数'' AFTER `coupon_use_count_before`',
    'SELECT ''revenue_order.coupon_use_count_after already exists'' AS message'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'revenue_order' AND COLUMN_NAME = 'coupon_use_count_after'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `revenue_order` ADD COLUMN `coupon_use_deducted` tinyint(1) NOT NULL DEFAULT 0 COMMENT ''优惠券次数是否已扣减'' AFTER `coupon_use_count_after`',
    'SELECT ''revenue_order.coupon_use_deducted already exists'' AS message'
  )
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'revenue_order' AND COLUMN_NAME = 'coupon_use_deducted'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE `revenue_rule`
SET `rule_mode` = `rule_mode`
WHERE `rule_mode` IN (1,2,3,4,5);

INSERT INTO auth_node (pid, name, url, is_button, status, create_time, update_time)
SELECT pid, '保存优惠券分账配置', '/management/revenue.revenue_rule/saveCouponConfig', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM auth_node
WHERE url = '/management/revenue.revenue_rule/add'
  AND NOT EXISTS (
    SELECT 1 FROM auth_node WHERE url = '/management/revenue.revenue_rule/saveCouponConfig'
  )
LIMIT 1;

INSERT INTO auth_node (pid, name, url, is_button, status, create_time, update_time)
SELECT pid, '查询优惠券分账配置', '/management/revenue.revenue_rule/getCouponConfig', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM auth_node
WHERE url = '/management/revenue.revenue_rule/getFind'
  AND NOT EXISTS (
    SELECT 1 FROM auth_node WHERE url = '/management/revenue.revenue_rule/getCouponConfig'
  )
LIMIT 1;

