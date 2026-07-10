-- 新分账配置自检脚本
-- 说明：本脚本只读查询，不修改数据。

-- 1. 已启用的分账支付类型
SELECT
  rpc_id,
  pay_type,
  channel_name,
  status
FROM revenue_pay_channel
WHERE status = 1
ORDER BY rpc_id DESC;

-- 1.1 分账时间配置不合法的分账规则
SELECT *
FROM revenue_rule
WHERE settlement_type NOT IN (1, 2)
   OR settlement_days < 0
   OR (settlement_type = 1 AND settlement_days <> 0)
   OR (settlement_type = 2 AND settlement_days < 1);

-- 2. 未绑定普通分账规则的设备
SELECT
  m.m_id,
  m.machine_id,
  m.machine_name,
  m.ao_id
FROM machine m
LEFT JOIN revenue_rule_machine rrm
  ON rrm.m_id = m.m_id
 AND rrm.status = 1
LEFT JOIN revenue_rule rr
  ON rr.rr_id = rrm.rr_id
 AND rr.status = 1
 AND rr.rule_mode = 1
WHERE rr.rr_id IS NULL
ORDER BY m.m_id DESC;

-- 3. 启用但没有有效明细的分账规则
SELECT
  rr.rr_id,
  rr.rule_name,
  rr.rule_mode
FROM revenue_rule rr
LEFT JOIN revenue_rule_item rri
  ON rri.rr_id = rr.rr_id
 AND rri.status = 1
WHERE rr.status = 1
GROUP BY rr.rr_id, rr.rule_name, rr.rule_mode
HAVING COUNT(rri.rri_id) = 0;

-- 4. 普通分账规则无法完整覆盖订单剩余金额的配置
SELECT
  rr.rr_id,
  rr.rule_name,
  GROUP_CONCAT(CONCAT(rri.rri_id, ':', rri.calc_type, ':', rri.calc_value) ORDER BY rri.sort, rri.rri_id) items
FROM revenue_rule rr
JOIN revenue_rule_item rri ON rri.rr_id = rr.rr_id AND rri.status = 1
WHERE rr.status = 1
  AND rr.rule_mode = 1
GROUP BY rr.rr_id, rr.rule_name
HAVING NOT (
  (COUNT(*) = 1 AND SUM(CASE WHEN rri.calc_type = 3 THEN 1 ELSE 0 END) = 1)
  OR
  (
    SUM(CASE WHEN rri.calc_type = 1 THEN 1 ELSE 0 END) = COUNT(*)
    AND SUM(rri.calc_value) = 100
  )
);

-- 5. 启用策略明细中账户不存在、停用，或账户组织与接收组织不一致
SELECT
  rr.rr_id,
  rr.rule_name,
  rr.rule_mode,
  rri.rri_id,
  rri.receiver_ao_id,
  rri.ra_id,
  ra.ao_id account_ao_id,
  ra.status account_status
FROM revenue_rule rr
JOIN revenue_rule_item rri ON rri.rr_id = rr.rr_id AND rri.status = 1
LEFT JOIN revenue_account ra ON ra.ra_id = rri.ra_id
WHERE rr.status = 1
  AND (
    ra.ra_id IS NULL
    OR ra.status <> 1
    OR ra.ao_id <> rri.receiver_ao_id
  );

-- 6. 设备出租策略中比例不合法的明细
SELECT
  rr.rr_id,
  rr.rule_name,
  rri.rri_id,
  rri.receiver_ao_id,
  rri.calc_type,
  rri.calc_value
FROM revenue_rule rr
JOIN revenue_rule_item rri ON rri.rr_id = rr.rr_id AND rri.status = 1
WHERE rr.status = 1
  AND rr.rule_mode = 2
  AND (
    rri.calc_type NOT IN (1, 2, 3)
    OR (rri.calc_type IN (1, 2) AND rri.calc_value <= 0)
    OR (rri.calc_type = 1 AND rri.calc_value > 100)
  );

-- 7. 设备分账策略中固定百分比合计超过 100% 的策略
SELECT
  rr.rr_id,
  rr.rule_name,
  SUM(CASE WHEN rri.calc_type = 1 THEN rri.calc_value ELSE 0 END) total_percent
FROM revenue_rule rr
JOIN revenue_rule_item rri ON rri.rr_id = rr.rr_id AND rri.status = 1
WHERE rr.status = 1
  AND rr.rule_mode = 3
GROUP BY rr.rr_id, rr.rule_name
HAVING total_percent > 100;

-- 8. 阶梯区间基础合法性检查
SELECT
  rrit.rrit_id,
  rrit.rri_id,
  rrit.threshold_min,
  rrit.threshold_max,
  rrit.calc_value
FROM revenue_rule_item_tier rrit
WHERE rrit.status = 1
  AND (
    rrit.threshold_min < 0
    OR (rrit.threshold_max IS NOT NULL AND rrit.threshold_max <= rrit.threshold_min)
    OR rrit.calc_value < 0
    OR rrit.calc_value > 100
  );

-- 9. 设备商品分账明细基础合法性检查
SELECT
  rr.rr_id,
  rr.rule_name,
  rri.rri_id,
  rri.g_id,
  rri.receiver_ao_id,
  rri.calc_type,
  rri.calc_value
FROM revenue_rule rr
JOIN revenue_rule_item rri ON rri.rr_id = rr.rr_id AND rri.status = 1
LEFT JOIN goods g ON g.g_id = rri.g_id
WHERE rr.status = 1
  AND rr.rule_mode = 4
  AND (
    rri.g_id IS NULL
    OR g.g_id IS NULL
    OR rri.calc_type NOT IN (1, 2)
    OR rri.calc_value <= 0
    OR (rri.calc_type = 1 AND rri.calc_value > 100)
  );

-- 10. 同一设备商品策略中，同一商品固定比例合计超过 100%
SELECT
  rr.rr_id,
  rr.rule_name,
  rri.g_id,
  SUM(rri.calc_value) total_percent
FROM revenue_rule rr
JOIN revenue_rule_item rri
  ON rri.rr_id = rr.rr_id
 AND rri.status = 1
 AND rri.calc_type = 1
WHERE rr.status = 1
  AND rr.rule_mode = 4
GROUP BY rr.rr_id, rr.rule_name, rri.g_id
HAVING total_percent > 100;
