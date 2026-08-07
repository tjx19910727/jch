-- order_type 订单类型配置表
-- 目的：把 sale_orders.order_type 当前硬编码展示配置落表，并提供后台增删改查。

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

-- 按 sale_orders 历史订单补齐未覆盖的订单类型值，避免生产库已有扩展 order_type 漏配。
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

-- 后台权限节点：挂到系统设置/配置类菜单下。如果当前库无该父节点，需要上线前按实际菜单调整 @parent_node_id。
SET @parent_node_id := (
  SELECT node_id
  FROM auth_node
  WHERE url IN ('/management/config.config/getList', '/management/config.config/getFind')
  ORDER BY node_id ASC
  LIMIT 1
);

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
