-- pay_bugfix 分支合并 main 数据库升级脚本
-- 适用范围：优惠券、满减活动关联微程线上商品。
-- 兼容说明：使用 information_schema + PREPARE 实现幂等执行，兼容 MySQL 5.5。
-- 注意：MySQL DDL 会隐式提交，请先备份并在业务低峰期执行。

SET @schema_name = DATABASE();

-- 1. activity_goods：增加商品来源字段。
SET @ddl = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = 'activity_goods'
          AND COLUMN_NAME = 'goods_source'
    ),
    'SELECT ''activity_goods.goods_source already exists'' AS migration_info',
    'ALTER TABLE `activity_goods` ADD COLUMN `goods_source` tinyint NOT NULL DEFAULT 1 COMMENT ''商品来源：1普通商品2微程线上商品'' AFTER `g_id`'
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. activity_goods：增加微程父商品编码字段。
SET @ddl = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = 'activity_goods'
          AND COLUMN_NAME = 'source_no'
    ),
    'SELECT ''activity_goods.source_no already exists'' AS migration_info',
    'ALTER TABLE `activity_goods` ADD COLUMN `source_no` varchar(64) NOT NULL DEFAULT '''' COMMENT ''来源商品编码，微程商品保存out_no'' AFTER `goods_source`'
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. activity_goods：增加活动线上商品匹配索引。
SET @ddl = IF(
    EXISTS(
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = 'activity_goods'
          AND INDEX_NAME = 'idx_activity_online_goods'
    ),
    'SELECT ''activity_goods.idx_activity_online_goods already exists'' AS migration_info',
    'ALTER TABLE `activity_goods` ADD INDEX `idx_activity_online_goods` (`a_id`, `a_type`, `goods_source`, `source_no`)'
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. activity_fd_content：增加商品来源字段。
SET @ddl = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = 'activity_fd_content'
          AND COLUMN_NAME = 'goods_source'
    ),
    'SELECT ''activity_fd_content.goods_source already exists'' AS migration_info',
    'ALTER TABLE `activity_fd_content` ADD COLUMN `goods_source` tinyint NOT NULL DEFAULT 1 COMMENT ''商品来源：1普通商品2微程线上商品'' AFTER `g_id`'
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5. activity_fd_content：增加微程父商品编码字段。
SET @ddl = IF(
    EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = 'activity_fd_content'
          AND COLUMN_NAME = 'source_no'
    ),
    'SELECT ''activity_fd_content.source_no already exists'' AS migration_info',
    'ALTER TABLE `activity_fd_content` ADD COLUMN `source_no` varchar(64) NOT NULL DEFAULT '''' COMMENT ''来源商品编码，微程商品保存out_no'' AFTER `goods_source`'
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 6. activity_fd_content：增加满减线上商品匹配索引。
SET @ddl = IF(
    EXISTS(
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = @schema_name
          AND TABLE_NAME = 'activity_fd_content'
          AND INDEX_NAME = 'idx_fd_online_goods'
    ),
    'SELECT ''activity_fd_content.idx_fd_online_goods already exists'' AS migration_info',
    'ALTER TABLE `activity_fd_content` ADD INDEX `idx_fd_online_goods` (`fd_id`, `goods_source`, `source_no`)'
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 7. 执行后校验：应返回 4 条字段记录和 7 条索引列记录。
SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, COLUMN_DEFAULT, IS_NULLABLE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @schema_name
  AND (
      (TABLE_NAME = 'activity_goods' AND COLUMN_NAME IN ('goods_source', 'source_no'))
      OR
      (TABLE_NAME = 'activity_fd_content' AND COLUMN_NAME IN ('goods_source', 'source_no'))
  )
ORDER BY TABLE_NAME, ORDINAL_POSITION;

SELECT TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX, COLUMN_NAME
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = @schema_name
  AND (
      (TABLE_NAME = 'activity_goods' AND INDEX_NAME = 'idx_activity_online_goods')
      OR
      (TABLE_NAME = 'activity_fd_content' AND INDEX_NAME = 'idx_fd_online_goods')
  )
ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;

-- 回滚参考（仅在确认线上商品活动数据无需保留、且代码已回滚后手工执行）：
-- ALTER TABLE `activity_goods` DROP INDEX `idx_activity_online_goods`, DROP COLUMN `source_no`, DROP COLUMN `goods_source`;
-- ALTER TABLE `activity_fd_content` DROP INDEX `idx_fd_online_goods`, DROP COLUMN `source_no`, DROP COLUMN `goods_source`;
