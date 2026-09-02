-- 多货币商品价格体系 v2.0
-- 目标版本：MySQL 5.6.5+
-- 执行前必须备份并先核对旧表字段；ALTER TABLE 不可重复执行。

CREATE TABLE IF NOT EXISTS `currency_info` (
  `currency_code` char(3) CHARACTER SET ascii COLLATE ascii_bin NOT NULL COMMENT 'ISO 4217三位大写币种代码',
  `currency_name` varchar(32) NOT NULL COMMENT '后台名称',
  `currency_symbol` varchar(8) NOT NULL DEFAULT '' COMMENT '展示符号',
  `decimal_places` tinyint(2) unsigned NOT NULL DEFAULT '2' COMMENT '展示及支付小数位，0至3',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '1启用 2停用',
  `is_default` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '1默认 0非默认',
  `sort` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '下拉排序',
  `creator` int(11) unsigned NOT NULL DEFAULT '0',
  `update_id` int(11) unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`currency_code`),
  KEY `idx_status_sort` (`status`,`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='币种主数据';

CREATE TABLE IF NOT EXISTS `goods_currency_price` (
  `gcp_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `g_id` int(11) unsigned NOT NULL,
  `currency_code` char(3) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cost_price` decimal(15,3) NOT NULL DEFAULT '0.000',
  `market_price` decimal(15,3) NOT NULL DEFAULT '0.000',
  `retail_price` decimal(15,3) NOT NULL DEFAULT '0.000',
  `creator` int(11) unsigned NOT NULL DEFAULT '0',
  `update_id` int(11) unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`gcp_id`),
  UNIQUE KEY `uk_goods_currency` (`g_id`,`currency_code`),
  KEY `idx_currency_update` (`currency_code`,`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='核心商品多币种价格';

CREATE TABLE IF NOT EXISTS `machine_goods_currency_price` (
  `mgcp_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `mg_id` int(11) unsigned NOT NULL,
  `m_id` int(11) unsigned NOT NULL,
  `g_id` int(11) unsigned NOT NULL,
  `currency_code` char(3) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cost_price` decimal(15,3) NOT NULL DEFAULT '0.000',
  `market_price` decimal(15,3) NOT NULL DEFAULT '0.000',
  `retail_price` decimal(15,3) NOT NULL DEFAULT '0.000',
  `creator` int(11) unsigned NOT NULL DEFAULT '0',
  `update_id` int(11) unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`mgcp_id`),
  UNIQUE KEY `uk_mg_currency` (`mg_id`,`currency_code`),
  KEY `idx_machine_currency` (`m_id`,`currency_code`),
  KEY `idx_goods_currency` (`g_id`,`currency_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='设备商品多币种价格';

CREATE TABLE IF NOT EXISTS `machine_channel_currency_price` (
  `mccp_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `mc_id` int(11) unsigned NOT NULL,
  `m_id` int(11) unsigned NOT NULL,
  `mg_id` int(11) unsigned NOT NULL DEFAULT '0',
  `g_id` int(11) unsigned NOT NULL,
  `currency_code` char(3) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cost_price` decimal(15,3) NOT NULL DEFAULT '0.000',
  `market_price` decimal(15,3) NOT NULL DEFAULT '0.000',
  `retail_price` decimal(15,3) NOT NULL DEFAULT '0.000',
  `creator` int(11) unsigned NOT NULL DEFAULT '0',
  `update_id` int(11) unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`mccp_id`),
  UNIQUE KEY `uk_channel_currency` (`mc_id`,`currency_code`),
  KEY `idx_machine_currency` (`m_id`,`currency_code`),
  KEY `idx_goods_currency` (`g_id`,`currency_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='普通单商品货道多币种价格';

ALTER TABLE `machine_config`
  ADD COLUMN `currency_code` char(3) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'CNY' COMMENT '设备当前展示及交易币种',
  ADD COLUMN `currency_version` int(11) unsigned NOT NULL DEFAULT '1' COMMENT '设备当前完整售卖快照版本';

ALTER TABLE `sale_orders`
  ADD COLUMN `currency_code` char(3) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL COMMENT '下单币种快照',
  ADD COLUMN `currency_version` int(11) unsigned DEFAULT NULL COMMENT '下单时设备售卖快照版本';

ALTER TABLE `sale_orders_details`
  ADD COLUMN `currency_code` char(3) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL COMMENT '订单明细币种快照';

INSERT INTO `currency_info`
(`currency_code`,`currency_name`,`currency_symbol`,`decimal_places`,`status`,`is_default`,`sort`)
VALUES
('CNY','人民币','¥',2,1,1,10),
('HKD','港币','HK$',2,1,0,20)
ON DUPLICATE KEY UPDATE
`currency_name`=VALUES(`currency_name`),`currency_symbol`=VALUES(`currency_symbol`),
`decimal_places`=VALUES(`decimal_places`),`status`=VALUES(`status`),`sort`=VALUES(`sort`);

-- 旧平面三价初始化为 CNY 事实价格。重复执行只补缺失行，不覆盖已维护价格。
INSERT INTO `goods_currency_price`
(`g_id`,`currency_code`,`cost_price`,`market_price`,`retail_price`,`creator`,`update_id`)
SELECT g.`g_id`,'CNY',g.`cost_price`,g.`market_price`,g.`retail_price`,IFNULL(g.`creator`,0),IFNULL(g.`update_id`,0)
FROM `goods` g
LEFT JOIN `goods_currency_price` p ON p.`g_id`=g.`g_id` AND p.`currency_code`='CNY'
WHERE p.`gcp_id` IS NULL;

INSERT INTO `machine_goods_currency_price`
(`mg_id`,`m_id`,`g_id`,`currency_code`,`cost_price`,`market_price`,`retail_price`,`creator`,`update_id`)
SELECT mg.`mg_id`,mg.`m_id`,mg.`g_id`,'CNY',mg.`cost_price`,mg.`market_price`,mg.`retail_price`,IFNULL(mg.`creator`,0),IFNULL(mg.`update_id`,0)
FROM `machine_goods` mg
LEFT JOIN `machine_goods_currency_price` p ON p.`mg_id`=mg.`mg_id` AND p.`currency_code`='CNY'
WHERE p.`mgcp_id` IS NULL;

INSERT INTO `machine_channel_currency_price`
(`mc_id`,`m_id`,`mg_id`,`g_id`,`currency_code`,`cost_price`,`market_price`,`retail_price`,`creator`,`update_id`)
SELECT mc.`mc_id`,mc.`m_id`,IFNULL(mc.`mg_id`,0),mc.`g_id`,'CNY',mc.`cost_price`,mc.`market_price`,mc.`retail_price`,IFNULL(mc.`creator`,0),IFNULL(mc.`update_id`,0)
FROM `machine_channel` mc
LEFT JOIN `machine_channel_currency_price` p ON p.`mc_id`=mc.`mc_id` AND p.`currency_code`='CNY'
WHERE p.`mccp_id` IS NULL AND IFNULL(mc.`is_multi_goods`,2)<>1 AND mc.`g_id`>0;
