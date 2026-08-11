-- revenue_pay_channel 历史配置安全迁移
-- 当前已核实的历史意图：微信支付/扫码启用，微信反扫、支付宝、京东保持停用。
-- 本脚本只迁移分账触发配置，不修改 strategy_payee、strategy_machine、sale_orders。

SET @db := DATABASE();
SET @backup_table := CONCAT('revenue_pay_channel_bak_', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'));

-- 1. 执行前检查：目标支付类型必须存在且启用。
SELECT required.pay_type, pt.pay_type_name, pt.status
FROM (
  SELECT 1 AS pay_type UNION ALL SELECT 11 UNION ALL SELECT 12
  UNION ALL SELECT 2 UNION ALL SELECT 21 UNION ALL SELECT 22 UNION ALL SELECT 4
) required
LEFT JOIN pay_type pt ON pt.pay_type = required.pay_type
WHERE pt.pt_id IS NULL OR pt.status <> 1;

-- 上述查询有结果时停止执行，先修复 pay_type 字典。

-- 2. 创建带时间戳的完整备份表。
SET @sql := CONCAT('CREATE TABLE `', @backup_table, '` LIKE `revenue_pay_channel`');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := CONCAT('INSERT INTO `', @backup_table, '` SELECT * FROM `revenue_pay_channel`');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SELECT @backup_table AS backup_table;

-- 3. 目标配置暂存；特定的微信反扫停用记录优先于旧“微信扫码”大类。
DROP TEMPORARY TABLE IF EXISTS tmp_revenue_pay_channel_target;
CREATE TEMPORARY TABLE tmp_revenue_pay_channel_target (
  pay_type int NOT NULL,
  channel_name varchar(50) NOT NULL,
  status tinyint NOT NULL,
  PRIMARY KEY (pay_type)
);
INSERT INTO tmp_revenue_pay_channel_target (pay_type, channel_name, status) VALUES
  (1,  '微信支付',       1),
  (11, '微信扫码支付',   1),
  (12, '微信反扫支付',   2),
  (2,  '支付宝支付',     2),
  (21, '支付宝扫码支付', 2),
  (22, '支付宝反扫支付', 2),
  (4,  '京东收银',       2);

-- 4. 替换触发配置。DDL 清理由独立结构脚本完成，便于数据回滚。
START TRANSACTION;
DELETE FROM revenue_pay_channel;
INSERT INTO revenue_pay_channel
  (pay_type, pay_channel, channel_name, status, creator, create_time, update_time)
SELECT
  target.pay_type,
  target.pay_type,
  target.channel_name,
  target.status,
  0,
  UNIX_TIMESTAMP(),
  UNIX_TIMESTAMP()
FROM tmp_revenue_pay_channel_target target
ORDER BY target.pay_type;
COMMIT;

-- 5. 迁移后必须全部返回 0 行。
SELECT rpc_id, pay_type, channel_name
FROM revenue_pay_channel
WHERE pay_type IS NULL OR pay_type <= 0;

SELECT pay_type, COUNT(*) count
FROM revenue_pay_channel
GROUP BY pay_type
HAVING COUNT(*) > 1;

SELECT rpc.pay_type
FROM revenue_pay_channel rpc
LEFT JOIN pay_type pt ON pt.pay_type = rpc.pay_type AND pt.status = 1
WHERE pt.pt_id IS NULL;

-- 6. 确认数据和分账测试通过后，再执行 revenue_pay_channel新增pay_type.sql 清理旧字段。
