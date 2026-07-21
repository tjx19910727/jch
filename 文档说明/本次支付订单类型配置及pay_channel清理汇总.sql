-- 本次支付/订单类型配置及 pay_channel 清理汇总 SQL
-- 适用范围：
-- 1. revenue_pay_channel 从 pay_channel 触发切换为 pay_type 触发；
-- 2. sale_orders 删除 pay_channel/pay_channel_name；
-- 3. 删除 payment_pay_type_channel_relation；
-- 4. 新增 pay_type 支付类型配置表，含线上/线下标记 pay_scene；
-- 5. 新增 order_type 订单类型配置表；
-- 6. 补充后台权限节点。
--
-- 执行建议：
-- 1. 先备份数据库，至少备份 sale_orders、revenue_pay_channel、auth_node；
-- 2. 新代码部署后执行；
-- 3. 执行后检查 pay_type、order_type、revenue_pay_channel、sale_orders 字段。

SET @db = DATABASE();

-- =========================================================
-- 1. revenue_pay_channel 切换为 pay_type 触发配置
-- =========================================================

SET @sql = IF(
  NOT EXISTS (
    SELECT 1
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'revenue_pay_channel'
      AND COLUMN_NAME = 'pay_type'
  ),
  'ALTER TABLE `revenue_pay_channel` ADD COLUMN `pay_type` int DEFAULT NULL COMMENT ''订单支付类型，对应 sale_orders.pay_type'' AFTER `rpc_id`',
  'SELECT ''revenue_pay_channel.pay_type already exists'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE `revenue_pay_channel`
SET `pay_type` = 0
WHERE `pay_type` IS NULL;

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
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

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
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE `revenue_pay_channel`
  MODIFY COLUMN `pay_type` int NOT NULL COMMENT '订单支付类型，对应 sale_orders.pay_type';

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
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =========================================================
-- 2. sale_orders 删除 pay_channel/pay_channel_name
-- =========================================================

SET @has_sale_orders_pay_channel := (
  SELECT COUNT(1)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db
    AND TABLE_NAME = 'sale_orders'
    AND COLUMN_NAME = 'pay_channel'
);

SET @has_sale_orders_pay_channel_name := (
  SELECT COUNT(1)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db
    AND TABLE_NAME = 'sale_orders'
    AND COLUMN_NAME = 'pay_channel_name'
);

SET @sql = IF(
  @has_sale_orders_pay_channel > 0 AND @has_sale_orders_pay_channel_name > 0,
  'CREATE TABLE IF NOT EXISTS `sale_orders_pay_channel_backup` AS SELECT `order_id`, `pay_channel`, `pay_channel_name` FROM `sale_orders`',
  IF(
    @has_sale_orders_pay_channel > 0,
    'CREATE TABLE IF NOT EXISTS `sale_orders_pay_channel_backup` AS SELECT `order_id`, `pay_channel` FROM `sale_orders`',
    'SELECT ''sale_orders.pay_channel not exists, skip backup'' AS message'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
  @has_sale_orders_pay_channel_name > 0,
  'ALTER TABLE `sale_orders` DROP COLUMN `pay_channel_name`',
  'SELECT ''sale_orders.pay_channel_name already removed'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = IF(
  @has_sale_orders_pay_channel > 0,
  'ALTER TABLE `sale_orders` DROP COLUMN `pay_channel`',
  'SELECT ''sale_orders.pay_channel already removed'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

DROP TABLE IF EXISTS `payment_pay_type_channel_relation`;

-- =========================================================
-- 3. pay_type 支付类型配置表
-- =========================================================

CREATE TABLE IF NOT EXISTS `pay_type` (
  `pt_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '支付类型配置ID',
  `pay_type` int NOT NULL COMMENT '支付类型值，对应 sale_orders.pay_type',
  `pay_type_name` varchar(50) NOT NULL DEFAULT '' COMMENT '支付类型名称',
  `pay_scene` tinyint NOT NULL DEFAULT 1 COMMENT '线上线下支付标记：1线上支付，2线下支付',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '状态：1启用，2停用',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序，越小越靠前',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `creator` int NOT NULL DEFAULT 0 COMMENT '创建人manager_id',
  `create_time` int NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`pt_id`),
  UNIQUE KEY `uk_pay_type` (`pay_type`),
  KEY `idx_status_sort` (`status`, `sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='支付类型配置表';

SET @pay_type_has_pay_scene := (
  SELECT COUNT(1)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db
    AND TABLE_NAME = 'pay_type'
    AND COLUMN_NAME = 'pay_scene'
);

SET @sql = IF(
  @pay_type_has_pay_scene = 0,
  'ALTER TABLE `pay_type` ADD COLUMN `pay_scene` tinyint NOT NULL DEFAULT 1 COMMENT ''线上线下支付标记：1线上支付，2线下支付'' AFTER `pay_type_name`',
  'SELECT ''pay_type.pay_scene already exists'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO `pay_type` (`pay_type`, `pay_type_name`, `pay_scene`, `status`, `sort`, `remark`, `create_time`, `update_time`)
VALUES
(0, '免支付', 1, 1, 0, '来源：config/payment.php pay_type_map', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(1, '微信支付', 1, 1, 10, '来源：config/payment.php pay_type_map', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(11, '微信扫码支付', 1, 1, 11, '来源：config/payment.php pay_type_map', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(12, '微信反扫支付', 2, 1, 12, '来源：config/payment.php pay_type_map', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(2, '支付宝支付', 1, 1, 20, '来源：config/payment.php pay_type_map', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(21, '支付宝扫码支付', 1, 1, 21, '来源：config/payment.php pay_type_map', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(22, '支付宝反扫支付', 2, 1, 22, '来源：config/payment.php pay_type_map', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(4, '京东收银', 1, 1, 40, '来源：config/payment.php pay_type_map', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(5, '会员支付', 1, 1, 50, '来源：config/payment.php pay_type_map', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(6, '丽呈线上支付', 1, 1, 60, '来源：config/payment.php pay_type_map', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(7, '机器人线上支付', 1, 1, 70, '来源：config/payment.php pay_type_map', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(8, 'COGOLINK', 2, 1, 80, '来源：config/payment.php pay_type_map', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(9, '商场积分支付', 2, 1, 90, '来源：config/payment.php pay_type_map', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(10, '八达通支付', 2, 1, 100, '来源：config/payment.php pay_type_map', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(20, '余额支付', 1, 1, 200, '来源：config/payment.php pay_type_map', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(33, '国际银联支付', 2, 1, 330, '来源：config/payment.php pay_type_map', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(34, '八达通支付', 2, 1, 340, '来源：config/payment.php pay_type_map', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(35, '国际银联卡支付', 2, 1, 350, '来源：config/payment.php pay_type_map', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(36, '纸币支付', 2, 1, 360, '来源：config/payment.php pay_type_map', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(37, '硬币支付', 2, 1, 370, '来源：config/payment.php pay_type_map', UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE
  `pay_type_name` = VALUES(`pay_type_name`),
  `pay_scene` = VALUES(`pay_scene`),
  `status` = VALUES(`status`),
  `sort` = VALUES(`sort`),
  `remark` = VALUES(`remark`),
  `update_time` = VALUES(`update_time`);

-- =========================================================
-- 4. order_type 订单类型配置表
-- =========================================================

CREATE TABLE IF NOT EXISTS `order_type` (
  `ot_id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '订单类型配置ID',
  `order_type` int NOT NULL COMMENT '订单类型值，对应 sale_orders.order_type',
  `order_type_name` varchar(50) NOT NULL DEFAULT '' COMMENT '订单类型名称',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '状态：1启用，2停用',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序，越小越靠前',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `creator` int NOT NULL DEFAULT 0 COMMENT '创建人manager_id',
  `create_time` int NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`ot_id`),
  UNIQUE KEY `uk_order_type` (`order_type`),
  KEY `idx_status_sort` (`status`, `sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='订单类型配置表';

INSERT INTO `order_type` (`order_type`, `order_type_name`, `status`, `sort`, `remark`, `create_time`, `update_time`)
VALUES
(1, '普通订单', 1, 10, '来源：sale_orders.order_type 现有展示逻辑', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(2, '优惠券订单', 1, 20, '来源：sale_orders.order_type 现有展示逻辑', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(3, '取货码订单', 1, 30, '来源：sale_orders.order_type 现有展示逻辑', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(4, '付费抽奖订单', 1, 40, '来源：sale_orders.order_type 现有展示逻辑', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(5, '满减满送订单', 1, 50, '来源：sale_orders.order_type 现有展示逻辑', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(6, '叠加营销活动订单', 1, 60, '来源：sale_orders.order_type 现有展示逻辑', UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(7, '商场积分订单', 1, 70, '来源：商城积分支付/退款逻辑', UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
ON DUPLICATE KEY UPDATE
  `order_type_name` = VALUES(`order_type_name`),
  `status` = VALUES(`status`),
  `sort` = VALUES(`sort`),
  `remark` = VALUES(`remark`),
  `update_time` = VALUES(`update_time`);

INSERT INTO `order_type` (`order_type`, `order_type_name`, `status`, `sort`, `remark`, `create_time`, `update_time`)
SELECT DISTINCT
  so.`order_type`,
  CONCAT('订单类型#', so.`order_type`),
  1,
  so.`order_type` * 10,
  '来源：sale_orders 历史订单自动补齐',
  UNIX_TIMESTAMP(),
  UNIX_TIMESTAMP()
FROM `sale_orders` so
WHERE so.`order_type` IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `order_type` ot WHERE ot.`order_type` = so.`order_type`
  );

-- =========================================================
-- 5. 后台权限节点
-- =========================================================

SET @parent_node_id := (
  SELECT node_id
  FROM auth_node
  WHERE url IN ('/management/config.config/getList', '/management/config.config/getFind')
  ORDER BY node_id ASC
  LIMIT 1
);

INSERT INTO auth_node (pid, name, url, is_button, status, create_time, update_time)
SELECT @parent_node_id, '支付类型配置列表', '/management/config.pay_type/getList', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @parent_node_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM auth_node WHERE url = '/management/config.pay_type/getList');

INSERT INTO auth_node (pid, name, url, is_button, status, create_time, update_time)
SELECT @parent_node_id, '支付类型配置详情', '/management/config.pay_type/getFind', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @parent_node_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM auth_node WHERE url = '/management/config.pay_type/getFind');

INSERT INTO auth_node (pid, name, url, is_button, status, create_time, update_time)
SELECT @parent_node_id, '支付类型配置树', '/management/config.pay_type/getTree', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @parent_node_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM auth_node WHERE url = '/management/config.pay_type/getTree');

INSERT INTO auth_node (pid, name, url, is_button, status, create_time, update_time)
SELECT @parent_node_id, '新增支付类型配置', '/management/config.pay_type/add', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @parent_node_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM auth_node WHERE url = '/management/config.pay_type/add');

INSERT INTO auth_node (pid, name, url, is_button, status, create_time, update_time)
SELECT @parent_node_id, '修改支付类型配置', '/management/config.pay_type/update', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @parent_node_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM auth_node WHERE url = '/management/config.pay_type/update');

INSERT INTO auth_node (pid, name, url, is_button, status, create_time, update_time)
SELECT @parent_node_id, '删除支付类型配置', '/management/config.pay_type/del', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @parent_node_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM auth_node WHERE url = '/management/config.pay_type/del');

INSERT INTO auth_node (pid, name, url, is_button, status, create_time, update_time)
SELECT @parent_node_id, '订单类型配置列表', '/management/config.order_type/getList', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @parent_node_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM auth_node WHERE url = '/management/config.order_type/getList');

INSERT INTO auth_node (pid, name, url, is_button, status, create_time, update_time)
SELECT @parent_node_id, '订单类型配置详情', '/management/config.order_type/getFind', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @parent_node_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM auth_node WHERE url = '/management/config.order_type/getFind');

INSERT INTO auth_node (pid, name, url, is_button, status, create_time, update_time)
SELECT @parent_node_id, '新增订单类型配置', '/management/config.order_type/add', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @parent_node_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM auth_node WHERE url = '/management/config.order_type/add');

INSERT INTO auth_node (pid, name, url, is_button, status, create_time, update_time)
SELECT @parent_node_id, '修改订单类型配置', '/management/config.order_type/update', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @parent_node_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM auth_node WHERE url = '/management/config.order_type/update');

INSERT INTO auth_node (pid, name, url, is_button, status, create_time, update_time)
SELECT @parent_node_id, '删除订单类型配置', '/management/config.order_type/del', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
WHERE @parent_node_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM auth_node WHERE url = '/management/config.order_type/del');

-- =========================================================
-- 6. 执行后检查
-- =========================================================

SHOW COLUMNS FROM `pay_type`;
SHOW COLUMNS FROM `order_type`;
SHOW COLUMNS FROM `revenue_pay_channel`;
SHOW COLUMNS FROM `sale_orders` LIKE 'pay_channel%';
