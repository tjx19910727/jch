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
  ADD COLUMN `currency_version` int(11) unsigned NOT NULL DEFAULT '1' COMMENT '设备当前完整售卖快照版本',
  ADD COLUMN `show_currency_symbol` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否显示货币符号：0不显示 1显示',
  ADD COLUMN `show_amount_decimals` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '金额是否显示小数点：1显示 0不显示';

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

-- ============================================================================
-- 【可重复执行】货道币种价格事实行身份自愈（多货币商品价格体系 v2.1）
--
-- 背景：设备端换货、终端换货上报、后台换货与商品导入只改写 machine_channel 的商品身份
--       （mg_id/g_id），不会重建 machine_channel_currency_price；旧商品价格残留会让切币预检
--       返回 MACHINE_CHANNEL_PRICE_STALE（货道目标币种价格属于旧商品，请重新同步）。
-- 规则与 MachineCurrencyPriceService::repairMachineChannelCurrencyIdentities 完全一致：
--   1) 身份已与货道一致的行不动，保留货道自维护价格；
--   2) 当前币种（machine_config.currency_code）：身份与三价都以货道活跃快照为准，禁止反向覆盖；
--   3) 其余启用币种：身份与三价按货道当前设备商品的该币种事实行重建；
--   4) 新商品没有该币种价格时删除旧行，宁可缺价阻断也不串价（缺行由缺价预检 + 人工同步补齐）。
-- 仅处理普通单商品货道（IFNULL(is_multi_goods,2)<>1）且 mg_id/g_id 有效的货道。
-- 本段为纯数据修复，可重复执行；执行前仍建议先备份。
-- ============================================================================

-- 1) 当前币种：身份与三价都以货道活跃快照为准
UPDATE `machine_channel_currency_price` p
JOIN `machine_channel` mc ON mc.`mc_id` = p.`mc_id`
JOIN `machine_config` mcfg ON mcfg.`m_id` = mc.`m_id`
SET p.`m_id` = mc.`m_id`,
    p.`mg_id` = mc.`mg_id`,
    p.`g_id` = mc.`g_id`,
    p.`cost_price` = mc.`cost_price`,
    p.`market_price` = mc.`market_price`,
    p.`retail_price` = mc.`retail_price`
WHERE p.`currency_code` = mcfg.`currency_code`
  AND IFNULL(mc.`is_multi_goods`, 2) <> 1
  AND mc.`mg_id` > 0
  AND mc.`g_id` > 0
  AND (p.`m_id` <> mc.`m_id` OR p.`mg_id` <> mc.`mg_id` OR p.`g_id` <> mc.`g_id`);

-- 2) 其余启用币种：身份与三价按设备商品该币种事实行重建
UPDATE `machine_channel_currency_price` p
JOIN `machine_channel` mc ON mc.`mc_id` = p.`mc_id`
JOIN `machine_config` mcfg ON mcfg.`m_id` = mc.`m_id`
JOIN `currency_info` ci ON ci.`currency_code` = p.`currency_code` AND ci.`status` = 1
JOIN `machine_goods_currency_price` s ON s.`mg_id` = mc.`mg_id` AND s.`currency_code` = p.`currency_code`
SET p.`m_id` = mc.`m_id`,
    p.`mg_id` = mc.`mg_id`,
    p.`g_id` = mc.`g_id`,
    p.`cost_price` = s.`cost_price`,
    p.`market_price` = s.`market_price`,
    p.`retail_price` = s.`retail_price`
WHERE p.`currency_code` <> mcfg.`currency_code`
  AND s.`m_id` = mc.`m_id`
  AND s.`g_id` = mc.`g_id`
  AND IFNULL(mc.`is_multi_goods`, 2) <> 1
  AND mc.`mg_id` > 0
  AND mc.`g_id` > 0
  AND (p.`m_id` <> mc.`m_id` OR p.`mg_id` <> mc.`mg_id` OR p.`g_id` <> mc.`g_id`);

-- 3) 新商品没有该币种价格的旧行：删除，避免切币时串价
DELETE p
FROM `machine_channel_currency_price` p
JOIN `machine_channel` mc ON mc.`mc_id` = p.`mc_id`
JOIN `machine_config` mcfg ON mcfg.`m_id` = mc.`m_id`
JOIN `currency_info` ci ON ci.`currency_code` = p.`currency_code` AND ci.`status` = 1
LEFT JOIN `machine_goods_currency_price` s ON s.`mg_id` = mc.`mg_id`
  AND s.`currency_code` = p.`currency_code` AND s.`m_id` = mc.`m_id` AND s.`g_id` = mc.`g_id`
WHERE p.`currency_code` <> mcfg.`currency_code`
  AND s.`mgcp_id` IS NULL
  AND IFNULL(mc.`is_multi_goods`, 2) <> 1
  AND mc.`mg_id` > 0
  AND mc.`g_id` > 0
  AND (p.`m_id` <> mc.`m_id` OR p.`mg_id` <> mc.`mg_id` OR p.`g_id` <> mc.`g_id`);

