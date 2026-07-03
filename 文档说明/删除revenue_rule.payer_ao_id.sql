-- 删除规则配置层无效的收款组织字段。
-- revenue_order.payer_ao_id 是订单实际收款组织快照，必须保留。

SET @drop_rule_payer_ao_id_sql := (
  SELECT IF(
    EXISTS (
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'revenue_rule'
        AND COLUMN_NAME = 'payer_ao_id'
    ),
    'ALTER TABLE `revenue_rule` DROP COLUMN `payer_ao_id`',
    'SELECT ''revenue_rule.payer_ao_id already removed'' AS message'
  )
);

PREPARE drop_rule_payer_ao_id_stmt FROM @drop_rule_payer_ao_id_sql;
EXECUTE drop_rule_payer_ao_id_stmt;
DEALLOCATE PREPARE drop_rule_payer_ao_id_stmt;
