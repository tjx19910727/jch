-- pay_type 支付类型配置表
-- 目的：把 config/payment.php 中 pay_type_map 的当前配置落表，并提供后台增删改查。

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
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'pay_type'
    AND COLUMN_NAME = 'pay_scene'
);

SET @pay_type_add_pay_scene_sql := IF(
  @pay_type_has_pay_scene = 0,
  'ALTER TABLE `pay_type` ADD COLUMN `pay_scene` tinyint NOT NULL DEFAULT 1 COMMENT ''线上线下支付标记：1线上支付，2线下支付'' AFTER `pay_type_name`',
  'SELECT ''pay_type.pay_scene already exists'' AS message'
);
PREPARE stmt FROM @pay_type_add_pay_scene_sql;
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

-- 后台权限节点：挂到系统设置/配置类菜单下。如果当前库无该父节点，需要上线前按实际菜单调整 @parent_node_id。
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
