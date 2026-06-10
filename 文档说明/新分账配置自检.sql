-- 新分账配置自检脚本
-- 说明：本脚本只读查询，不修改数据。

-- 1. 已启用的分账收款渠道
SELECT
  rpc_id,
  pay_type,
  payee_type,
  channel_name,
  status
FROM revenue_pay_channel
WHERE status = 1
ORDER BY rpc_id DESC;

-- 1.1 分账时间配置不合法的收款策略新分账配置
SELECT *
FROM revenue_payee_config
WHERE settlement_type NOT IN (1, 2)
   OR settlement_days < 0
   OR (settlement_type = 1 AND settlement_days <> 0)
   OR (settlement_type = 2 AND settlement_days < 1);

-- 2. 启用渠道对应但缺少新分账配置的收款策略
SELECT
  sp.sp_id,
  sp.sp_name,
  sp.payee_type,
  sp.ao_id
FROM strategy_payee sp
JOIN revenue_pay_channel rpc
  ON rpc.status = 1
 AND (rpc.pay_type = sp.payee_type OR rpc.payee_type = sp.payee_type)
LEFT JOIN revenue_payee_config rpcfg
  ON rpcfg.sp_id = sp.sp_id
 AND rpcfg.status = 1
WHERE sp.status = 1
  AND rpcfg.rpcfg_id IS NULL
ORDER BY sp.sp_id DESC;

-- 3. 启用新分账但缺少默认分账账户的收款策略配置
SELECT
  rpcfg.rpcfg_id,
  rpcfg.sp_id,
  sp.sp_name,
  rpcfg.payee_type,
  rpcfg.ao_id,
  rpcfg.default_ra_id,
  rpcfg.default_manager_id,
  rpcfg.enable_revenue
FROM revenue_payee_config rpcfg
LEFT JOIN strategy_payee sp ON sp.sp_id = rpcfg.sp_id
WHERE rpcfg.status = 1
  AND rpcfg.enable_revenue = 1
  AND (rpcfg.default_ra_id IS NULL OR rpcfg.default_ra_id = 0);

-- 4. 默认分账账户不存在、停用，或组织不一致的收款策略配置
SELECT
  rpcfg.rpcfg_id,
  rpcfg.sp_id,
  sp.sp_name,
  rpcfg.ao_id payee_ao_id,
  rpcfg.default_ra_id,
  ra.ao_id account_ao_id,
  ra.status account_status
FROM revenue_payee_config rpcfg
LEFT JOIN strategy_payee sp ON sp.sp_id = rpcfg.sp_id
LEFT JOIN revenue_account ra ON ra.ra_id = rpcfg.default_ra_id
WHERE rpcfg.status = 1
  AND rpcfg.enable_revenue = 1
  AND (
    ra.ra_id IS NULL
    OR ra.status <> 1
    OR ra.ao_id <> rpcfg.ao_id
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
