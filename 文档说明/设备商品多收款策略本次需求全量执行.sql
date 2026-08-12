-- ============================================================================
-- 设备商品多收款策略：本次需求全量执行 SQL
-- 适用范围：JCH 当前库，MySQL 5.6+
-- 编制日期：2026-08-12
--
-- 执行说明：
-- 1. 请先备份 machine_goods、machine_config、sale_orders、sale_orders_details。
-- 2. MySQL DDL 会隐式提交，请在业务低峰期执行。
-- 3. 本脚本使用 information_schema + PREPARE 判断字段是否存在，可重复执行。
-- 4. 不修改已有设备混合下单配置值；新增字段使用兼容旧逻辑的默认值。
-- 5. 不删除旧 machine_goods.sp_id，该字段用于旧接口及历史单策略兼容。
-- ============================================================================

SET @schema_name = DATABASE();

-- ----------------------------------------------------------------------------
-- 一、设备混合下单策略范围字段（当前解析器的既有前置依赖）
-- ----------------------------------------------------------------------------

SET @ddl = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = 'machine_config'
          AND COLUMN_NAME = 'subcar_mix'
    ),
    'SELECT ''machine_config.subcar_mix already exists'' AS migration_info',
    'ALTER TABLE `machine_config` ADD COLUMN `subcar_mix` tinyint(1) NOT NULL DEFAULT 1 COMMENT ''是否允许线上线下商品混合下单：1允许2禁止'' AFTER `pay_type`'
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @ddl = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = 'machine_config'
          AND COLUMN_NAME = 'subcar_offline_sp_ids'
    ),
    'SELECT ''machine_config.subcar_offline_sp_ids already exists'' AS migration_info',
    'ALTER TABLE `machine_config` ADD COLUMN `subcar_offline_sp_ids` varchar(255) NOT NULL DEFAULT '''' COMMENT ''禁止混合下单时线下商品可用收款策略sp_id，逗号分隔'' AFTER `subcar_mix`'
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @ddl = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = 'machine_config'
          AND COLUMN_NAME = 'subcar_online_sp_ids'
    ),
    'SELECT ''machine_config.subcar_online_sp_ids already exists'' AS migration_info',
    'ALTER TABLE `machine_config` ADD COLUMN `subcar_online_sp_ids` varchar(255) NOT NULL DEFAULT '''' COMMENT ''禁止混合下单时线上商品可用收款策略sp_id，逗号分隔'' AFTER `subcar_offline_sp_ids`'
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ----------------------------------------------------------------------------
-- 二、设备商品旧版单策略兼容字段
-- 新接口以关联表为准；保存多策略时，此字段同步保存第一优先级策略。
-- ----------------------------------------------------------------------------

SET @ddl = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = 'machine_goods'
          AND COLUMN_NAME = 'sp_id'
    ),
    'SELECT ''machine_goods.sp_id already exists'' AS migration_info',
    'ALTER TABLE `machine_goods` ADD COLUMN `sp_id` int NOT NULL DEFAULT 0 COMMENT ''设备商品首选收款策略ID，0表示未配置并沿用原收款逻辑'' AFTER `auto_refund`'
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ----------------------------------------------------------------------------
-- 三、设备商品多收款策略关联表
-- 一个 mg_id 可以绑定多个 sp_id；sort 越小，策略优先级越高。
-- 不增加物理外键，保持项目现有数据库风格并避免历史脏数据阻塞DDL。
-- ----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `machine_goods_payee_strategy` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `mg_id` int NOT NULL COMMENT '设备商品ID：machine_goods.mg_id',
  `sp_id` int NOT NULL COMMENT '收款策略ID：strategy_payee.sp_id',
  `sort` int NOT NULL DEFAULT 1 COMMENT '策略优先级，越小越优先',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '状态：1启用，2停用',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_mg_sp` (`mg_id`,`sp_id`),
  KEY `idx_mg_status_sort` (`mg_id`,`status`,`sort`),
  KEY `idx_sp_id` (`sp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='设备商品收款策略关联表';

-- ----------------------------------------------------------------------------
-- 四、订单最终收款策略字段
-- 当前多数环境已存在；缺失时补齐。历史订单默认0，继续使用原支付解析逻辑。
-- ----------------------------------------------------------------------------

SET @ddl = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = 'sale_orders'
          AND COLUMN_NAME = 'sp_id'
    ),
    'SELECT ''sale_orders.sp_id already exists'' AS migration_info',
    'ALTER TABLE `sale_orders` ADD COLUMN `sp_id` int NOT NULL DEFAULT 0 COMMENT ''订单最终收款策略ID，0表示历史订单未固化'''
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ----------------------------------------------------------------------------
-- 五、订单明细收款策略快照
-- source_sp_id：商品显式策略最终命中的ID；旧逻辑来源时为0。
-- effective_sp_id：整单最终固化的收款策略ID。
-- payee_source：goods_explicit 或 legacy。
-- ----------------------------------------------------------------------------

SET @ddl = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = 'sale_orders_details'
          AND COLUMN_NAME = 'source_sp_id'
    ),
    'SELECT ''sale_orders_details.source_sp_id already exists'' AS migration_info',
    'ALTER TABLE `sale_orders_details` ADD COLUMN `source_sp_id` int NOT NULL DEFAULT 0 COMMENT ''商品显式收款策略最终命中ID，0表示来自旧逻辑'''
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @ddl = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = 'sale_orders_details'
          AND COLUMN_NAME = 'effective_sp_id'
    ),
    'SELECT ''sale_orders_details.effective_sp_id already exists'' AS migration_info',
    'ALTER TABLE `sale_orders_details` ADD COLUMN `effective_sp_id` int NOT NULL DEFAULT 0 COMMENT ''订单明细最终收款策略ID'' AFTER `source_sp_id`'
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @ddl = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = 'sale_orders_details'
          AND COLUMN_NAME = 'payee_source'
    ),
    'SELECT ''sale_orders_details.payee_source already exists'' AS migration_info',
    'ALTER TABLE `sale_orders_details` ADD COLUMN `payee_source` varchar(32) NOT NULL DEFAULT '''' COMMENT ''收款策略来源：goods_explicit或legacy'' AFTER `effective_sp_id`'
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ----------------------------------------------------------------------------
-- 六、历史单策略数据迁移到多策略关联表
-- 可重复执行；不会覆盖已配置的其他策略，只保证旧 sp_id 作为第一优先级存在。
-- ----------------------------------------------------------------------------

INSERT INTO `machine_goods_payee_strategy`
  (`mg_id`,`sp_id`,`sort`,`status`,`create_time`,`update_time`)
SELECT `mg_id`,`sp_id`,1,1,NOW(),NOW()
FROM `machine_goods`
WHERE `sp_id` > 0
ON DUPLICATE KEY UPDATE
  `status` = VALUES(`status`),
  `sort` = VALUES(`sort`),
  `update_time` = VALUES(`update_time`);

-- ----------------------------------------------------------------------------
-- 七、执行后结构核验
-- 预期：required_column_count = 9。
-- ----------------------------------------------------------------------------

SELECT
  SUM(CASE WHEN TABLE_NAME = 'machine_config' AND COLUMN_NAME = 'subcar_mix' THEN 1 ELSE 0 END) AS machine_config_subcar_mix,
  SUM(CASE WHEN TABLE_NAME = 'machine_config' AND COLUMN_NAME = 'subcar_offline_sp_ids' THEN 1 ELSE 0 END) AS machine_config_offline_sp_ids,
  SUM(CASE WHEN TABLE_NAME = 'machine_config' AND COLUMN_NAME = 'subcar_online_sp_ids' THEN 1 ELSE 0 END) AS machine_config_online_sp_ids,
  SUM(CASE WHEN TABLE_NAME = 'machine_goods' AND COLUMN_NAME = 'sp_id' THEN 1 ELSE 0 END) AS machine_goods_sp_id,
  SUM(CASE WHEN TABLE_NAME = 'sale_orders' AND COLUMN_NAME = 'sp_id' THEN 1 ELSE 0 END) AS sale_orders_sp_id,
  SUM(CASE WHEN TABLE_NAME = 'sale_orders_details' AND COLUMN_NAME = 'source_sp_id' THEN 1 ELSE 0 END) AS details_source_sp_id,
  SUM(CASE WHEN TABLE_NAME = 'sale_orders_details' AND COLUMN_NAME = 'effective_sp_id' THEN 1 ELSE 0 END) AS details_effective_sp_id,
  SUM(CASE WHEN TABLE_NAME = 'sale_orders_details' AND COLUMN_NAME = 'payee_source' THEN 1 ELSE 0 END) AS details_payee_source,
  SUM(CASE WHEN TABLE_NAME = 'machine_goods_payee_strategy' AND COLUMN_NAME = 'id' THEN 1 ELSE 0 END) AS relation_table_exists,
  COUNT(*) AS required_column_count
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @schema_name
  AND (
    (TABLE_NAME = 'machine_config' AND COLUMN_NAME IN ('subcar_mix','subcar_offline_sp_ids','subcar_online_sp_ids'))
    OR (TABLE_NAME = 'machine_goods' AND COLUMN_NAME = 'sp_id')
    OR (TABLE_NAME = 'sale_orders' AND COLUMN_NAME = 'sp_id')
    OR (TABLE_NAME = 'sale_orders_details' AND COLUMN_NAME IN ('source_sp_id','effective_sp_id','payee_source'))
    OR (TABLE_NAME = 'machine_goods_payee_strategy' AND COLUMN_NAME = 'id')
  );

SELECT TABLE_NAME,COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,COLUMN_DEFAULT,COLUMN_COMMENT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @schema_name
  AND TABLE_NAME IN (
    'machine_config',
    'machine_goods',
    'machine_goods_payee_strategy',
    'sale_orders',
    'sale_orders_details'
  )
  AND COLUMN_NAME IN (
    'id','subcar_mix','subcar_offline_sp_ids','subcar_online_sp_ids',
    'mg_id','sp_id','sort','status','create_time','update_time',
    'source_sp_id','effective_sp_id','payee_source'
  )
ORDER BY TABLE_NAME,ORDINAL_POSITION;

-- ----------------------------------------------------------------------------
-- 八、执行后数据核验
-- ----------------------------------------------------------------------------

-- 8.1 每个设备商品当前启用的策略及优先级。
SELECT mgps.mg_id,
       COUNT(*) AS strategy_count,
       GROUP_CONCAT(mgps.sp_id ORDER BY mgps.sort,mgps.id) AS sp_ids
FROM machine_goods_payee_strategy mgps
WHERE mgps.status = 1
GROUP BY mgps.mg_id
ORDER BY strategy_count DESC,mgps.mg_id;

-- 8.2 关联表中不存在的设备商品。预期返回0行。
SELECT mgps.id,mgps.mg_id,mgps.sp_id
FROM machine_goods_payee_strategy mgps
LEFT JOIN machine_goods mg ON mg.mg_id = mgps.mg_id
WHERE mg.mg_id IS NULL;

-- 8.3 关联表中不存在或已删除的收款策略。预期返回0行。
SELECT mgps.id,mgps.mg_id,mgps.sp_id
FROM machine_goods_payee_strategy mgps
LEFT JOIN strategy_payee sp ON sp.sp_id = mgps.sp_id
WHERE sp.sp_id IS NULL;

-- 8.4 已启用关联指向已停用策略。上线前应确认是否清理或重新配置。
SELECT mgps.id,mgps.mg_id,mgps.sp_id,sp.sp_name,sp.status AS strategy_status
FROM machine_goods_payee_strategy mgps
INNER JOIN strategy_payee sp ON sp.sp_id = mgps.sp_id
WHERE mgps.status = 1 AND sp.status <> 1;

-- 8.5 检查旧单策略是否全部迁移。预期返回0行。
SELECT mg.mg_id,mg.sp_id
FROM machine_goods mg
LEFT JOIN machine_goods_payee_strategy mgps
  ON mgps.mg_id = mg.mg_id
 AND mgps.sp_id = mg.sp_id
WHERE mg.sp_id > 0
  AND mgps.id IS NULL;

-- ----------------------------------------------------------------------------
-- 九、回退参考
-- 注意：以下语句默认注释，不能与升级SQL一起执行。
-- 必须先回滚应用代码，并确认新配置及订单快照无需保留后再人工执行。
-- 历史订单 sale_orders.sp_id 通常已被支付、退款和回调使用，不建议删除。
-- ----------------------------------------------------------------------------

-- 将每个商品第一优先级策略回写到旧兼容字段：
-- UPDATE machine_goods mg
-- INNER JOIN (
--   SELECT x.mg_id,x.sp_id
--   FROM machine_goods_payee_strategy x
--   INNER JOIN (
--     SELECT mg_id,MIN(sort) AS min_sort
--     FROM machine_goods_payee_strategy
--     WHERE status = 1
--     GROUP BY mg_id
--   ) y ON y.mg_id = x.mg_id AND y.min_sort = x.sort
--   WHERE x.status = 1
--   GROUP BY x.mg_id
-- ) first_strategy ON first_strategy.mg_id = mg.mg_id
-- SET mg.sp_id = first_strategy.sp_id;

-- 删除多策略关联表（仅在代码已回滚后执行）：
-- DROP TABLE `machine_goods_payee_strategy`;

-- 删除本次新增明细快照字段（通常建议保留历史审计数据）：
-- ALTER TABLE `sale_orders_details`
--   DROP COLUMN `payee_source`,
--   DROP COLUMN `effective_sp_id`,
--   DROP COLUMN `source_sp_id`;

-- machine_goods.sp_id、sale_orders.sp_id 以及 machine_config.subcar_* 为兼容字段，
-- 默认不纳入回退删除范围。

-- ----------------------------------------------------------------------------
-- 十、后台批量配置接口权限节点
-- ----------------------------------------------------------------------------

SET @parent_node_id := (
    SELECT node_id FROM auth_node
    WHERE url = '/management/machine.machine_goods/getList'
    ORDER BY node_id ASC LIMIT 1
);
SET @node_url := '/management/machine.machine_goods/updatePayeeStrategiesBatch';
SET @node_name := '批量配置设备商品收款策略';
SET @now_time := UNIX_TIMESTAMP();

UPDATE auth_node
SET pid = @parent_node_id,
    name = @node_name,
    `desc` = '将多个设备商品的独立收款策略整体替换为同一有序策略集合',
    type = 2,
    is_auth = 1,
    is_button = 1,
    data_auth = 1,
    permission_action = 'manage',
    status = 1,
    update_time = @now_time
WHERE url = @node_url;

INSERT INTO auth_node (
    pid,name,icon,url,`desc`,sort,type,is_auth,is_button,
    data_auth,permission_action,status,create_time,update_time
)
SELECT @parent_node_id,@node_name,'',@node_url,
       '将多个设备商品的独立收款策略整体替换为同一有序策略集合',
       102,2,1,1,1,'manage',1,@now_time,@now_time
FROM DUAL
WHERE @parent_node_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM auth_node WHERE url = @node_url);

SELECT node_id,pid,name,url,is_button,data_auth,permission_action,status
FROM auth_node
WHERE url = @node_url;
