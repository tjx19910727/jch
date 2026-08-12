-- 本脚本只读，用于确认用户已执行的数据库变更是否完整。
-- 请使用项目 .env 指向的目标数据库执行，不修改任何数据。

SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND (
    (TABLE_NAME = 'sale_orders' AND COLUMN_NAME = 'sp_id')
    OR (
      TABLE_NAME = 'sale_orders_details'
      AND COLUMN_NAME IN ('source_sp_id', 'effective_sp_id', 'payee_source')
    )
  )
ORDER BY TABLE_NAME, ORDINAL_POSITION;

SELECT
  SUM(CASE WHEN TABLE_NAME = 'sale_orders' AND COLUMN_NAME = 'sp_id' THEN 1 ELSE 0 END) AS sale_orders_sp_id,
  SUM(CASE WHEN TABLE_NAME = 'sale_orders_details' AND COLUMN_NAME = 'source_sp_id' THEN 1 ELSE 0 END) AS details_source_sp_id,
  SUM(CASE WHEN TABLE_NAME = 'sale_orders_details' AND COLUMN_NAME = 'effective_sp_id' THEN 1 ELSE 0 END) AS details_effective_sp_id,
  SUM(CASE WHEN TABLE_NAME = 'sale_orders_details' AND COLUMN_NAME = 'payee_source' THEN 1 ELSE 0 END) AS details_payee_source
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE();

SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'machine_goods_payee_strategy'
ORDER BY ORDINAL_POSITION;
