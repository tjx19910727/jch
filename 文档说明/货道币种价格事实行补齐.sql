-- ============================================================================
-- 货道币种价格事实行补齐
-- 目的：修复「编辑货道返回 {"state":3301,"msg":"缺少价格字段：cost_price"}」的数据缺口。
--
-- 背景：
--   machine_channel_currency_price 是普通货道「当前币种价格」的事实源，
--   machine_channel 上的 cost_price / market_price / retail_price 仅为“活跃币种快照”。
--   历史“缺价阻断”策略（repairMachineChannelCurrencyIdentities）会删除身份不再匹配的事实行，
--   留下“货道快照有价、事实行缺失”的空洞；此时若编辑只提交部分三价，服务层既无入参也无库值可补，
--   就会抛“缺少价格字段：cost_price”。
--
--   代码侧已修复：缺事实行且保存币种=设备当前币种时，用 machine_channel 活跃快照兜底
--   （MachineCurrencyPriceService::mergeSnapshotPriceFallback，守卫 tests/machine_channel_price_missing_field_guard.php）。
--   本脚本负责把历史空洞按快照补齐，让“事实行=快照”，从数据侧消除该状态。
--
-- 特性：
--   * 幂等：依赖唯一键 uk_channel_currency(mc_id, currency_code)，重复执行不会重复插入；
--   * 只新增事实行，不修改任何货道快照 → 设备价格不变，
--     因此【不需要】递增 currency_version，也【不需要】通知设备；
--   * 只处理“已绑定商品”的普通货道（g_id>0 AND mg_id>0）；
--     未绑定商品的空货道（生产 3971 条）不会被 assertOrdinaryChannel 放行，补了也无意义，故跳过。
-- 适用：生产 / UAT（执行前请确认连接的是目标库）
-- ============================================================================

-- ---------------------------------------------------------------------------
-- 第 0 步：执行前留档与备份
-- ---------------------------------------------------------------------------
-- 0.1 事实行总数（留档对比）
SELECT COUNT(*) AS fact_total_before FROM machine_channel_currency_price;

-- 0.2 待补齐清单（预期：生产 8 条，含 mc_id 16998/32317/32318/36745/45265/45269/46247/46248）
SELECT mc.mc_id, mc.m_id, mach.machine_id, mc.channel_code, mc.mg_id, mc.g_id, mc.g_name,
       cfg.currency_code, mc.cost_price, mc.market_price, mc.retail_price
FROM machine_channel mc
JOIN machine_config cfg ON cfg.m_id = mc.m_id
LEFT JOIN machine mach ON mach.m_id = mc.m_id
LEFT JOIN machine_channel_currency_price p
       ON p.mc_id = mc.mc_id AND p.currency_code = cfg.currency_code
WHERE p.mccp_id IS NULL
  AND mc.g_id > 0 AND mc.mg_id > 0
ORDER BY mc.m_id, mc.mc_id;

-- 0.3 备份清单（回滚依据；表名可按日期调整）
DROP TABLE IF EXISTS bak_mccp_missing_20260917;
CREATE TABLE bak_mccp_missing_20260917 (
  mc_id        int unsigned NOT NULL,
  m_id         int unsigned NOT NULL,
  currency_code char(3)     NOT NULL,
  cost_price   decimal(15,3) NOT NULL,
  market_price decimal(15,3) NOT NULL,
  retail_price decimal(15,3) NOT NULL,
  backed_at    timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (mc_id, currency_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='货道币种价事实行补齐-执行前留档';

INSERT INTO bak_mccp_missing_20260917 (mc_id, m_id, currency_code, cost_price, market_price, retail_price)
SELECT mc.mc_id, mc.m_id, cfg.currency_code, mc.cost_price, mc.market_price, mc.retail_price
FROM machine_channel mc
JOIN machine_config cfg ON cfg.m_id = mc.m_id
LEFT JOIN machine_channel_currency_price p
       ON p.mc_id = mc.mc_id AND p.currency_code = cfg.currency_code
WHERE p.mccp_id IS NULL
  AND mc.g_id > 0 AND mc.mg_id > 0;

SELECT COUNT(*) AS to_insert FROM bak_mccp_missing_20260917;

-- ---------------------------------------------------------------------------
-- 第 1 步：按货道活跃快照补齐事实行（幂等，可重复执行）
-- ---------------------------------------------------------------------------
INSERT INTO machine_channel_currency_price
  (mc_id, m_id, mg_id, g_id, currency_code, cost_price, market_price, retail_price, creator, update_id)
SELECT mc.mc_id, mc.m_id, mc.mg_id, mc.g_id, cfg.currency_code,
       mc.cost_price, mc.market_price, mc.retail_price, 0, 0
FROM machine_channel mc
JOIN machine_config cfg ON cfg.m_id = mc.m_id
LEFT JOIN machine_channel_currency_price p
       ON p.mc_id = mc.mc_id AND p.currency_code = cfg.currency_code
WHERE p.mccp_id IS NULL
  AND mc.g_id > 0 AND mc.mg_id > 0
  AND mc.cost_price >= 0 AND mc.market_price >= 0 AND mc.retail_price >= 0;

-- ---------------------------------------------------------------------------
-- 第 2 步：执行后校验
-- ---------------------------------------------------------------------------
-- 2.1 已绑定商品货道在设备当前币种下的缺行数，必须为 0
SELECT COUNT(*) AS miss_rows_after
FROM machine_channel mc
JOIN machine_config cfg ON cfg.m_id = mc.m_id
LEFT JOIN machine_channel_currency_price p
       ON p.mc_id = mc.mc_id AND p.currency_code = cfg.currency_code
WHERE p.mccp_id IS NULL
  AND mc.g_id > 0 AND mc.mg_id > 0;

-- 2.2 事实行总数（应 = 执行前 + 备份条数）
SELECT COUNT(*) AS fact_total_after FROM machine_channel_currency_price;

-- 2.3 事实行与货道快照是否一致，必须为 0
SELECT COUNT(*) AS mismatch_rows
FROM machine_channel mc
JOIN machine_config cfg ON cfg.m_id = mc.m_id
JOIN machine_channel_currency_price p
     ON p.mc_id = mc.mc_id AND p.currency_code = cfg.currency_code
WHERE p.cost_price <> mc.cost_price
   OR p.market_price <> mc.market_price
   OR p.retail_price <> mc.retail_price;

-- 2.4 事实行商品身份与货道是否一致（不一致的行由代码侧身份自愈处理，本脚本不自动改）
SELECT p.mccp_id, p.mc_id, p.m_id, p.mg_id AS fact_mg_id, mc.mg_id AS channel_mg_id,
       p.g_id AS fact_g_id, mc.g_id AS channel_g_id
FROM machine_channel_currency_price p
JOIN machine_channel mc ON mc.mc_id = p.mc_id
WHERE p.mg_id <> mc.mg_id OR p.g_id <> mc.g_id;

-- ---------------------------------------------------------------------------
-- 第 3 步：回滚（仅在需要撤销本次补齐时执行）
-- ---------------------------------------------------------------------------
-- DELETE p FROM machine_channel_currency_price p
-- JOIN bak_mccp_missing_20260917 b
--   ON b.mc_id = p.mc_id AND b.currency_code = p.currency_code;

-- ---------------------------------------------------------------------------
-- 第 4 步：业务侧验证（无需 SQL）
-- ---------------------------------------------------------------------------
-- 1) 后台编辑上述任一货道，只改零售价提交 → 应返回 200（修复前返回 3301 缺少价格字段：cost_price）；
-- 2) 批量改价（batchUpdate）选中上述货道 → 应整批成功；
-- 3) 若仍失败，检查 machine_config.currency_code 与该货道快照三价是否合法（不得为负）。
