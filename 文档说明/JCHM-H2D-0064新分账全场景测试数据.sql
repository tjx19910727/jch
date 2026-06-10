-- JCHM-H2D-0064 新分账全场景真实测试数据
-- 仅用于手工导入，Codex 未执行本脚本。
-- 适用：MySQL 5.7+/8.0，数据库中已存在新分账 revenue_* 表。
--
-- 场景：
-- 1. 普通分账：A组织获得订单剩余金额100%
-- 2. 设备出租：A设备销售B组织商品，B获得商品金额100%
-- 3. 设备固定比例：B获得20%，C获得30%
-- 4. 设备阶梯分账：
--    A测试账户：月营业额 [0,5000) 20%，[5000,+∞) 25%
--    B测试账户：月营业额 [0,8000) 25%，[8000,+∞) 30%
-- 5. T+1延期结算：通过修改分账触发渠道结算方式进行测试
--
-- 重要：
-- - 本脚本不修改 strategy_payee 和 strategy_machine。
-- - 本脚本会创建两个测试组织、三个测试管理员、三个余额型分账账户。
-- - 本脚本会为目标设备预置出租、固定比例、阶梯策略，但初始化后全部设备策略绑定为停用。
-- - 普通分账无需绑定 revenue_rule，初始化完成后默认处于普通分账测试状态。
-- - 出租场景要求订单中实际存在 B 测试组织的商品，即 sale_orders_details.sod_ao_id = @org_b_id。
-- - 每次切换场景前，应使用新订单；已存在非待支付分账记录的订单不能重算。

SET NAMES utf8mb4;
START TRANSACTION;

-- ============================================================
-- 0. 固定标识与目标设备/收款策略读取
-- ============================================================
SET @test_prefix := 'REVTEST_JCHM_H2D_0064';
SET @machine_code := 'JCHM-H2D-0064';
SET @now := UNIX_TIMESTAMP();

SET @m_id := (
  SELECT m_id
  FROM machine
  WHERE machine_id = @machine_code
  LIMIT 1
);

SET @payer_ao_id := (
  SELECT ao_id
  FROM machine
  WHERE machine_id = @machine_code
  LIMIT 1
);

SET @payer_org_level := COALESCE((
  SELECT level
  FROM auth_organization
  WHERE ao_id = @payer_ao_id
  LIMIT 1
), 0);

-- 优先读取目标设备当前有效的收款策略。
SET @sp_id := (
  SELECT sp.sp_id
  FROM strategy_machine sm
  JOIN strategy_payee sp ON sp.sp_id = sm.s_id
  WHERE sm.m_id = @m_id
    AND sm.s_type = 1
    AND sp.status = 1
  ORDER BY sm.sm_id DESC
  LIMIT 1
);

SET @pay_type := (
  SELECT payee_type
  FROM strategy_payee
  WHERE sp_id = @sp_id
  LIMIT 1
);

-- 导入后必须检查：m_id、payer_ao_id、sp_id、pay_type 均不可为空。
SELECT
  @machine_code AS machine_id,
  @m_id AS m_id,
  @payer_ao_id AS payer_ao_id,
  @sp_id AS sp_id,
  @pay_type AS pay_type;

-- 前置校验：任一结果为 FAIL 时不要继续导入，应先修复设备或收款策略配置。
SELECT
  IF(@m_id IS NULL, 'FAIL: 未找到目标设备', 'PASS') AS machine_check,
  IF(@payer_ao_id IS NULL, 'FAIL: 目标设备未配置所属组织', 'PASS') AS payer_org_check,
  IF(@sp_id IS NULL, 'FAIL: 目标设备未绑定有效业务收款策略', 'PASS') AS payee_strategy_check,
  IF(@pay_type IS NULL, 'FAIL: 业务收款策略未配置支付类型', 'PASS') AS pay_type_check;

-- ============================================================
-- 1. 新建测试组织 B、C
-- ============================================================
INSERT INTO auth_organization
  (pid, level, organization_name, is_top, sort, creator, create_time, update_time)
SELECT
  @payer_ao_id,
  @payer_org_level + 1,
  CONCAT(@test_prefix, '_ORG_B'),
  2,
  9001,
  0,
  @now,
  @now
WHERE @payer_ao_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM auth_organization
    WHERE organization_name = CONCAT(@test_prefix, '_ORG_B')
  );

INSERT INTO auth_organization
  (pid, level, organization_name, is_top, sort, creator, create_time, update_time)
SELECT
  @payer_ao_id,
  @payer_org_level + 1,
  CONCAT(@test_prefix, '_ORG_C'),
  2,
  9002,
  0,
  @now,
  @now
WHERE @payer_ao_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM auth_organization
    WHERE organization_name = CONCAT(@test_prefix, '_ORG_C')
  );

SET @org_b_id := (
  SELECT ao_id FROM auth_organization
  WHERE organization_name = CONCAT(@test_prefix, '_ORG_B')
  ORDER BY ao_id DESC LIMIT 1
);

SET @org_c_id := (
  SELECT ao_id FROM auth_organization
  WHERE organization_name = CONCAT(@test_prefix, '_ORG_C')
  ORDER BY ao_id DESC LIMIT 1
);

-- ============================================================
-- 2. 新建 A/B/C 测试账户管理人
-- 说明：这些管理员仅作为新分账账户管理人，无需登录后台。
-- password 为固定不可逆测试哈希；account 使用唯一测试标识。
-- ============================================================
INSERT INTO auth_manager
  (ao_id, nickname, account, password, pid, level, balance, frozen, withdrawal,
   bill_account, real_name, status, audit_status, creator, create_time, update_time)
SELECT
  @payer_ao_id,
  CONCAT(@test_prefix, '_MANAGER_A'),
  CONCAT(@test_prefix, '_MANAGER_A'),
  MD5(CONCAT(@test_prefix, '_NO_LOGIN')),
  0,
  1,
  0,
  0,
  0,
  CONCAT(@test_prefix, '_ACCOUNT_A'),
  '新分账测试账户A',
  1,
  2,
  0,
  @now,
  @now
WHERE @payer_ao_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM auth_manager
    WHERE account = CONCAT(@test_prefix, '_MANAGER_A')
  );

INSERT INTO auth_manager
  (ao_id, nickname, account, password, pid, level, balance, frozen, withdrawal,
   bill_account, real_name, status, audit_status, creator, create_time, update_time)
SELECT
  @org_b_id,
  CONCAT(@test_prefix, '_MANAGER_B'),
  CONCAT(@test_prefix, '_MANAGER_B'),
  MD5(CONCAT(@test_prefix, '_NO_LOGIN')),
  0,
  1,
  0,
  0,
  0,
  CONCAT(@test_prefix, '_ACCOUNT_B'),
  '新分账测试账户B',
  1,
  2,
  0,
  @now,
  @now
WHERE @org_b_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM auth_manager
    WHERE account = CONCAT(@test_prefix, '_MANAGER_B')
  );

INSERT INTO auth_manager
  (ao_id, nickname, account, password, pid, level, balance, frozen, withdrawal,
   bill_account, real_name, status, audit_status, creator, create_time, update_time)
SELECT
  @org_c_id,
  CONCAT(@test_prefix, '_MANAGER_C'),
  CONCAT(@test_prefix, '_MANAGER_C'),
  MD5(CONCAT(@test_prefix, '_NO_LOGIN')),
  0,
  1,
  0,
  0,
  0,
  CONCAT(@test_prefix, '_ACCOUNT_C'),
  '新分账测试账户C',
  1,
  2,
  0,
  @now,
  @now
WHERE @org_c_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM auth_manager
    WHERE account = CONCAT(@test_prefix, '_MANAGER_C')
  );

SET @manager_a_id := (
  SELECT manager_id FROM auth_manager
  WHERE account = CONCAT(@test_prefix, '_MANAGER_A')
  ORDER BY manager_id DESC LIMIT 1
);
SET @manager_b_id := (
  SELECT manager_id FROM auth_manager
  WHERE account = CONCAT(@test_prefix, '_MANAGER_B')
  ORDER BY manager_id DESC LIMIT 1
);
SET @manager_c_id := (
  SELECT manager_id FROM auth_manager
  WHERE account = CONCAT(@test_prefix, '_MANAGER_C')
  ORDER BY manager_id DESC LIMIT 1
);

-- ============================================================
-- 3. 新建 A/B/C 余额型分账账户
-- ============================================================
INSERT INTO revenue_account
  (ao_id, manager_id, account_name, account, account_type, bill_account,
   status, creator, create_time, update_time)
SELECT
  @payer_ao_id,
  @manager_a_id,
  CONCAT(@test_prefix, '_RA_A'),
  CONCAT(@test_prefix, '_BALANCE_A'),
  'balance',
  CONCAT(@test_prefix, '_BALANCE_A'),
  1,
  0,
  @now,
  @now
WHERE @manager_a_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM revenue_account
    WHERE account = CONCAT(@test_prefix, '_BALANCE_A')
  );

INSERT INTO revenue_account
  (ao_id, manager_id, account_name, account, account_type, bill_account,
   status, creator, create_time, update_time)
SELECT
  @org_b_id,
  @manager_b_id,
  CONCAT(@test_prefix, '_RA_B'),
  CONCAT(@test_prefix, '_BALANCE_B'),
  'balance',
  CONCAT(@test_prefix, '_BALANCE_B'),
  1,
  0,
  @now,
  @now
WHERE @manager_b_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM revenue_account
    WHERE account = CONCAT(@test_prefix, '_BALANCE_B')
  );

INSERT INTO revenue_account
  (ao_id, manager_id, account_name, account, account_type, bill_account,
   status, creator, create_time, update_time)
SELECT
  @org_c_id,
  @manager_c_id,
  CONCAT(@test_prefix, '_RA_C'),
  CONCAT(@test_prefix, '_BALANCE_C'),
  'balance',
  CONCAT(@test_prefix, '_BALANCE_C'),
  1,
  0,
  @now,
  @now
WHERE @manager_c_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM revenue_account
    WHERE account = CONCAT(@test_prefix, '_BALANCE_C')
  );

SET @ra_a_id := (
  SELECT ra_id FROM revenue_account
  WHERE account = CONCAT(@test_prefix, '_BALANCE_A')
  ORDER BY ra_id DESC LIMIT 1
);
SET @ra_b_id := (
  SELECT ra_id FROM revenue_account
  WHERE account = CONCAT(@test_prefix, '_BALANCE_B')
  ORDER BY ra_id DESC LIMIT 1
);
SET @ra_c_id := (
  SELECT ra_id FROM revenue_account
  WHERE account = CONCAT(@test_prefix, '_BALANCE_C')
  ORDER BY ra_id DESC LIMIT 1
);

-- ============================================================
-- 4. 场景一：普通分账配置，并启用目标设备实际收款类型的新分账触发配置
-- 注意：如果该 pay_type 已存在，本语句会启用并改为即时分账。
-- 注意：如果该 sp_id 已存在新分账配置，会切换到本脚本创建的A测试账户。
-- 普通分账由 revenue_payee_config 的默认账户表示，不创建 revenue_rule。
-- 当设备没有启用的设备分账策略，且订单没有命中出租商品策略时，
-- 订单剩余金额将以 source=normal、income_value=100 分给A默认账户。
-- ============================================================
-- 导入前请保存以下查询结果，测试完成后可据此恢复原新分账配置：
SELECT * FROM revenue_pay_channel WHERE pay_type = @pay_type;
SELECT * FROM revenue_payee_config WHERE sp_id = @sp_id;

INSERT INTO revenue_pay_channel
  (pay_type, payee_type, channel_name, status, creator, create_time, update_time)
SELECT
  @pay_type,
  NULL,
  CONCAT(@test_prefix, '_分账触发'),
  1,
  0,
  @now,
  @now
WHERE @pay_type IS NOT NULL
ON DUPLICATE KEY UPDATE
  channel_name = VALUES(channel_name),
  status = 1,
  update_time = VALUES(update_time);

-- 普通分账配置检查：default_ra_id/default_manager_id 应为本脚本创建的A账户。
SELECT
  rpcfg.*,
  ra.account_name,
  ra.account,
  am.nickname AS manager_name
FROM revenue_payee_config rpcfg
LEFT JOIN revenue_account ra ON ra.ra_id = rpcfg.default_ra_id
LEFT JOIN auth_manager am ON am.manager_id = rpcfg.default_manager_id
WHERE rpcfg.sp_id = @sp_id;

-- 原收款策略只作为关联标识读取；新配置写入独立 revenue_payee_config。
INSERT INTO revenue_payee_config
  (sp_id, payee_type, ao_id, default_ra_id, default_manager_id,
   enable_revenue, settlement_type, settlement_days, status, creator, create_time, update_time)
SELECT
  @sp_id,
  @pay_type,
  @payer_ao_id,
  @ra_a_id,
  @manager_a_id,
  1,
  1,
  0,
  1,
  0,
  @now,
  @now
WHERE @sp_id IS NOT NULL
ON DUPLICATE KEY UPDATE
  payee_type = VALUES(payee_type),
  ao_id = VALUES(ao_id),
  default_ra_id = VALUES(default_ra_id),
  default_manager_id = VALUES(default_manager_id),
  enable_revenue = 1,
  settlement_type = 1,
  settlement_days = 0,
  status = 1,
  update_time = VALUES(update_time);

-- ============================================================
-- 5. 场景二：设备出租策略，B组织获得其商品金额100%
-- ============================================================
INSERT INTO revenue_rule
  (rule_name, rule_mode, payer_ao_id, base_type, turnover_type,
   tier_calc_mode, status, creator, create_time, update_time)
SELECT
  CONCAT(@test_prefix, '_设备出租_B100'),
  2,
  @payer_ao_id,
  1,
  1,
  1,
  1,
  0,
  @now,
  @now
WHERE NOT EXISTS (
  SELECT 1 FROM revenue_rule
  WHERE rule_name = CONCAT(@test_prefix, '_设备出租_B100')
);

SET @rr_rental_id := (
  SELECT rr_id FROM revenue_rule
  WHERE rule_name = CONCAT(@test_prefix, '_设备出租_B100')
  ORDER BY rr_id DESC LIMIT 1
);

INSERT INTO revenue_rule_item
  (rr_id, receiver_ao_id, ra_id, manager_id, calc_type, calc_value,
   sort, status, create_time, update_time)
SELECT
  @rr_rental_id,
  @org_b_id,
  @ra_b_id,
  @manager_b_id,
  1,
  100.000,
  1,
  1,
  @now,
  @now
WHERE NOT EXISTS (
  SELECT 1 FROM revenue_rule_item
  WHERE rr_id = @rr_rental_id AND receiver_ao_id = @org_b_id
);

INSERT INTO revenue_rule_machine
  (rr_id, m_id, ao_id, sort, status, create_time, update_time)
SELECT
  @rr_rental_id,
  @m_id,
  @payer_ao_id,
  10,
  2,
  @now,
  @now
WHERE @m_id IS NOT NULL
ON DUPLICATE KEY UPDATE
  ao_id = VALUES(ao_id),
  sort = VALUES(sort),
  status = 2,
  update_time = VALUES(update_time);

-- ============================================================
-- 6. 场景三：设备固定比例，B 20%，C 30%
-- ============================================================
INSERT INTO revenue_rule
  (rule_name, rule_mode, payer_ao_id, base_type, turnover_type,
   tier_calc_mode, status, creator, create_time, update_time)
SELECT
  CONCAT(@test_prefix, '_设备固定比例_B20_C30'),
  3,
  @payer_ao_id,
  1,
  1,
  1,
  1,
  0,
  @now,
  @now
WHERE NOT EXISTS (
  SELECT 1 FROM revenue_rule
  WHERE rule_name = CONCAT(@test_prefix, '_设备固定比例_B20_C30')
);

SET @rr_fixed_id := (
  SELECT rr_id FROM revenue_rule
  WHERE rule_name = CONCAT(@test_prefix, '_设备固定比例_B20_C30')
  ORDER BY rr_id DESC LIMIT 1
);

INSERT INTO revenue_rule_item
  (rr_id, receiver_ao_id, ra_id, manager_id, calc_type, calc_value,
   sort, status, create_time, update_time)
SELECT @rr_fixed_id, @org_b_id, @ra_b_id, @manager_b_id, 1, 20.000, 1, 1, @now, @now
WHERE NOT EXISTS (
  SELECT 1 FROM revenue_rule_item
  WHERE rr_id = @rr_fixed_id AND receiver_ao_id = @org_b_id
);

INSERT INTO revenue_rule_item
  (rr_id, receiver_ao_id, ra_id, manager_id, calc_type, calc_value,
   sort, status, create_time, update_time)
SELECT @rr_fixed_id, @org_c_id, @ra_c_id, @manager_c_id, 1, 30.000, 2, 1, @now, @now
WHERE NOT EXISTS (
  SELECT 1 FROM revenue_rule_item
  WHERE rr_id = @rr_fixed_id AND receiver_ao_id = @org_c_id
);

INSERT INTO revenue_rule_machine
  (rr_id, m_id, ao_id, sort, status, create_time, update_time)
SELECT @rr_fixed_id, @m_id, @payer_ao_id, 20, 2, @now, @now
WHERE @m_id IS NOT NULL
ON DUPLICATE KEY UPDATE
  ao_id = VALUES(ao_id),
  sort = VALUES(sort),
  status = 2,
  update_time = VALUES(update_time);

-- ============================================================
-- 7. 场景四：设备阶梯分账
-- A：[0,5000) 20%，[5000,+∞) 25%
-- B：[0,8000) 25%，[8000,+∞) 30%
-- ============================================================
INSERT INTO revenue_rule
  (rule_name, rule_mode, payer_ao_id, base_type, turnover_type,
   tier_calc_mode, status, creator, create_time, update_time)
SELECT
  CONCAT(@test_prefix, '_设备阶梯_A_B'),
  3,
  @payer_ao_id,
  1,
  1,
  1,
  1,
  0,
  @now,
  @now
WHERE NOT EXISTS (
  SELECT 1 FROM revenue_rule
  WHERE rule_name = CONCAT(@test_prefix, '_设备阶梯_A_B')
);

SET @rr_tier_id := (
  SELECT rr_id FROM revenue_rule
  WHERE rule_name = CONCAT(@test_prefix, '_设备阶梯_A_B')
  ORDER BY rr_id DESC LIMIT 1
);

INSERT INTO revenue_rule_item
  (rr_id, receiver_ao_id, ra_id, manager_id, calc_type, calc_value,
   sort, status, create_time, update_time)
SELECT @rr_tier_id, @payer_ao_id, @ra_a_id, @manager_a_id, 4, 0.000, 1, 1, @now, @now
WHERE NOT EXISTS (
  SELECT 1 FROM revenue_rule_item
  WHERE rr_id = @rr_tier_id AND receiver_ao_id = @payer_ao_id
);

INSERT INTO revenue_rule_item
  (rr_id, receiver_ao_id, ra_id, manager_id, calc_type, calc_value,
   sort, status, create_time, update_time)
SELECT @rr_tier_id, @org_b_id, @ra_b_id, @manager_b_id, 4, 0.000, 2, 1, @now, @now
WHERE NOT EXISTS (
  SELECT 1 FROM revenue_rule_item
  WHERE rr_id = @rr_tier_id AND receiver_ao_id = @org_b_id
);

SET @rri_tier_a_id := (
  SELECT rri_id FROM revenue_rule_item
  WHERE rr_id = @rr_tier_id AND receiver_ao_id = @payer_ao_id
  ORDER BY rri_id DESC LIMIT 1
);
SET @rri_tier_b_id := (
  SELECT rri_id FROM revenue_rule_item
  WHERE rr_id = @rr_tier_id AND receiver_ao_id = @org_b_id
  ORDER BY rri_id DESC LIMIT 1
);

INSERT INTO revenue_rule_item_tier
  (rri_id, threshold_min, threshold_max, calc_value, sort, status, create_time, update_time)
SELECT @rri_tier_a_id, 0.00, 5000.00, 20.000, 1, 1, @now, @now
WHERE NOT EXISTS (
  SELECT 1 FROM revenue_rule_item_tier
  WHERE rri_id = @rri_tier_a_id AND threshold_min = 0.00 AND threshold_max = 5000.00
);

INSERT INTO revenue_rule_item_tier
  (rri_id, threshold_min, threshold_max, calc_value, sort, status, create_time, update_time)
SELECT @rri_tier_a_id, 5000.00, NULL, 25.000, 2, 1, @now, @now
WHERE NOT EXISTS (
  SELECT 1 FROM revenue_rule_item_tier
  WHERE rri_id = @rri_tier_a_id AND threshold_min = 5000.00 AND threshold_max IS NULL
);

INSERT INTO revenue_rule_item_tier
  (rri_id, threshold_min, threshold_max, calc_value, sort, status, create_time, update_time)
SELECT @rri_tier_b_id, 0.00, 8000.00, 25.000, 1, 1, @now, @now
WHERE NOT EXISTS (
  SELECT 1 FROM revenue_rule_item_tier
  WHERE rri_id = @rri_tier_b_id AND threshold_min = 0.00 AND threshold_max = 8000.00
);

INSERT INTO revenue_rule_item_tier
  (rri_id, threshold_min, threshold_max, calc_value, sort, status, create_time, update_time)
SELECT @rri_tier_b_id, 8000.00, NULL, 30.000, 2, 1, @now, @now
WHERE NOT EXISTS (
  SELECT 1 FROM revenue_rule_item_tier
  WHERE rri_id = @rri_tier_b_id AND threshold_min = 8000.00 AND threshold_max IS NULL
);

INSERT INTO revenue_rule_machine
  (rr_id, m_id, ao_id, sort, status, create_time, update_time)
SELECT @rr_tier_id, @m_id, @payer_ao_id, 30, 2, @now, @now
WHERE @m_id IS NOT NULL
ON DUPLICATE KEY UPDATE
  ao_id = VALUES(ao_id),
  sort = VALUES(sort),
  status = 2,
  update_time = VALUES(update_time);

COMMIT;

-- ============================================================
-- 8. 出租场景商品准备
-- 说明：
-- 1. 出租分账按订单明细 sale_orders_details.sod_ao_id 判断商品所属组织。
-- 2. 正常设备下单时，sod_ao_id 来源于 machine_goods.ao_id。
-- 3. 本段默认只列出候选商品，不会修改真实设备商品。
-- 4. 请选定一条可售测试商品，将其 mg_id 填入 @rental_test_mg_id，
--    再手工执行下方已注释的备份、修改与恢复语句。
-- ============================================================
SET @rental_test_mg_id := NULL;
SET @rental_original_ao_id := NULL;

SELECT
  mg.mg_id,
  mg.m_id,
  mg.machine_id,
  mg.g_id,
  mg.g_name,
  mg.ao_id AS current_goods_ao_id,
  mg.is_shelf,
  mg.available_stock
FROM machine_goods mg
WHERE mg.m_id = @m_id
ORDER BY mg.is_shelf DESC, mg.available_stock DESC, mg.mg_id
LIMIT 50;

-- 选择出租测试商品后，将下方示例ID替换成实际 mg_id，再依次执行。
-- SET @rental_test_mg_id := 123;
-- SET @rental_original_ao_id := (
--   SELECT ao_id FROM machine_goods
--   WHERE mg_id = @rental_test_mg_id AND m_id = @m_id
--   LIMIT 1
-- );
-- SELECT mg_id, m_id, machine_id, g_id, g_name, ao_id AS original_goods_ao_id
-- FROM machine_goods
-- WHERE mg_id = @rental_test_mg_id AND m_id = @m_id;
--
-- 将选定商品临时标记为B组织商品：
-- UPDATE machine_goods
-- SET ao_id = @org_b_id, update_time = UNIX_TIMESTAMP()
-- WHERE mg_id = @rental_test_mg_id AND m_id = @m_id;
--
-- 出租测试结束后，使用上方记录的 original_goods_ao_id 恢复：
-- UPDATE machine_goods
-- SET ao_id = @rental_original_ao_id, update_time = UNIX_TIMESTAMP()
-- WHERE mg_id = @rental_test_mg_id
--   AND m_id = @m_id
--   AND @rental_original_ao_id IS NOT NULL;

-- ============================================================
-- 9. 初始化结果检查
-- ============================================================
SELECT
  @m_id AS m_id,
  @payer_ao_id AS payer_ao_id,
  @org_b_id AS org_b_id,
  @org_c_id AS org_c_id,
  @manager_a_id AS manager_a_id,
  @manager_b_id AS manager_b_id,
  @manager_c_id AS manager_c_id,
  @ra_a_id AS ra_a_id,
  @ra_b_id AS ra_b_id,
  @ra_c_id AS ra_c_id,
  @sp_id AS sp_id,
  @pay_type AS pay_type,
  @rr_rental_id AS rr_rental_id,
  @rr_fixed_id AS rr_fixed_id,
  @rr_tier_id AS rr_tier_id;

SELECT * FROM revenue_account
WHERE account LIKE CONCAT(@test_prefix, '%')
ORDER BY ra_id;

SELECT rr.*, rrm.rrm_id, rrm.m_id, rrm.status AS bind_status
FROM revenue_rule rr
LEFT JOIN revenue_rule_machine rrm ON rrm.rr_id = rr.rr_id AND rrm.m_id = @m_id
WHERE rr.rule_name LIKE CONCAT(@test_prefix, '%')
ORDER BY rr.rr_id;

SELECT rri.*, rrit.threshold_min, rrit.threshold_max, rrit.calc_value AS tier_calc_value
FROM revenue_rule_item rri
LEFT JOIN revenue_rule_item_tier rrit ON rrit.rri_id = rri.rri_id
WHERE rri.rr_id IN (@rr_rental_id, @rr_fixed_id, @rr_tier_id)
ORDER BY rri.rr_id, rri.sort, rrit.sort;

-- ============================================================
-- 10. 场景切换 SQL
-- 下列切换语句默认全部注释，不会随初始化脚本执行。
-- 每次复制并执行其中一个场景，并使用新订单测试。
-- 为确保只命中目标场景，切换时会停用该设备当前全部新分账策略绑定；
-- 正式测试完成后请按测试前记录恢复原有绑定状态。
-- ============================================================

-- 新会话执行场景切换前，先执行本段重新加载变量：
-- SET @test_prefix := 'REVTEST_JCHM_H2D_0064';
-- SET @machine_code := 'JCHM-H2D-0064';
-- SET @m_id := (SELECT m_id FROM machine WHERE machine_id = @machine_code LIMIT 1);
-- SET @rr_rental_id := (
--   SELECT rr_id FROM revenue_rule
--   WHERE rule_name = CONCAT(@test_prefix, '_设备出租_B100')
--   ORDER BY rr_id DESC LIMIT 1
-- );
-- SET @rr_fixed_id := (
--   SELECT rr_id FROM revenue_rule
--   WHERE rule_name = CONCAT(@test_prefix, '_设备固定比例_B20_C30')
--   ORDER BY rr_id DESC LIMIT 1
-- );
-- SET @rr_tier_id := (
--   SELECT rr_id FROM revenue_rule
--   WHERE rule_name = CONCAT(@test_prefix, '_设备阶梯_A_B')
--   ORDER BY rr_id DESC LIMIT 1
-- );
-- SET @sp_id := (
--   SELECT sp.sp_id
--   FROM strategy_machine sm
--   JOIN strategy_payee sp ON sp.sp_id = sm.s_id
--   WHERE sm.m_id = @m_id AND sm.s_type = 1 AND sp.status = 1
--   ORDER BY sm.sm_id DESC LIMIT 1
-- );
-- SET @pay_type := (
--   SELECT payee_type FROM strategy_payee WHERE sp_id = @sp_id LIMIT 1
-- );
-- SET @payer_ao_id := (SELECT ao_id FROM machine WHERE m_id = @m_id LIMIT 1);
-- SET @org_b_id := (
--   SELECT ao_id FROM auth_organization
--   WHERE organization_name = CONCAT(@test_prefix, '_ORG_B')
--   ORDER BY ao_id DESC LIMIT 1
-- );
-- SET @org_c_id := (
--   SELECT ao_id FROM auth_organization
--   WHERE organization_name = CONCAT(@test_prefix, '_ORG_C')
--   ORDER BY ao_id DESC LIMIT 1
-- );
-- SET @manager_a_id := (
--   SELECT manager_id FROM auth_manager
--   WHERE account = CONCAT(@test_prefix, '_MANAGER_A')
--   ORDER BY manager_id DESC LIMIT 1
-- );
-- SET @manager_b_id := (
--   SELECT manager_id FROM auth_manager
--   WHERE account = CONCAT(@test_prefix, '_MANAGER_B')
--   ORDER BY manager_id DESC LIMIT 1
-- );
-- SET @manager_c_id := (
--   SELECT manager_id FROM auth_manager
--   WHERE account = CONCAT(@test_prefix, '_MANAGER_C')
--   ORDER BY manager_id DESC LIMIT 1
-- );
-- SET @ra_a_id := (
--   SELECT ra_id FROM revenue_account
--   WHERE account = CONCAT(@test_prefix, '_BALANCE_A')
--   ORDER BY ra_id DESC LIMIT 1
-- );
-- SET @ra_b_id := (
--   SELECT ra_id FROM revenue_account
--   WHERE account = CONCAT(@test_prefix, '_BALANCE_B')
--   ORDER BY ra_id DESC LIMIT 1
-- );
-- SET @ra_c_id := (
--   SELECT ra_id FROM revenue_account
--   WHERE account = CONCAT(@test_prefix, '_BALANCE_C')
--   ORDER BY ra_id DESC LIMIT 1
-- );

-- 测试前先记录该设备当前绑定状态：
SELECT rrm.*, rr.rule_name, rr.rule_mode
FROM revenue_rule_machine rrm
JOIN revenue_rule rr ON rr.rr_id = rrm.rr_id
WHERE rrm.m_id = @m_id
ORDER BY rrm.rrm_id;

-- ------------------------------------------------------------
-- 场景一：普通分账
-- 前置：订单商品组织均为设备所属A组织。
-- 预期：不命中设备策略，A默认账户获得订单金额100%。
-- ------------------------------------------------------------
-- UPDATE revenue_rule_machine
-- SET status = 2, update_time = UNIX_TIMESTAMP()
-- WHERE m_id = @m_id;
-- SELECT '普通分账' AS active_scene, COUNT(*) AS active_device_rule_count
-- FROM revenue_rule_machine
-- WHERE m_id = @m_id AND status = 1;

-- ------------------------------------------------------------
-- 场景二：设备出租，B商品金额100%分给B
-- 前置：目标设备上准备一件 sod_ao_id=@org_b_id 的B组织商品。
-- 预期：B商品对应记录 source=rental、income_value=100。
-- ------------------------------------------------------------
-- UPDATE revenue_rule_machine
-- SET status = 2, update_time = UNIX_TIMESTAMP()
-- WHERE m_id = @m_id;
-- UPDATE revenue_rule_machine
-- SET status = 1, update_time = UNIX_TIMESTAMP()
-- WHERE m_id = @m_id AND rr_id = @rr_rental_id;
-- SELECT '设备出租' AS active_scene, rr.rule_name, rr.rule_mode, rrm.status
-- FROM revenue_rule_machine rrm
-- JOIN revenue_rule rr ON rr.rr_id = rrm.rr_id
-- WHERE rrm.m_id = @m_id AND rrm.status = 1;
-- SELECT mg_id, g_id, g_name, ao_id
-- FROM machine_goods
-- WHERE mg_id = @rental_test_mg_id AND m_id = @m_id;

-- ------------------------------------------------------------
-- 场景三：设备固定比例，B 20%，C 30%
-- 建议使用A组织商品测试，避免同时触发出租分账。
-- 预期：生成B 20%、C 30%两条 device_rule 分账记录。
-- ------------------------------------------------------------
-- UPDATE revenue_rule_machine
-- SET status = 2, update_time = UNIX_TIMESTAMP()
-- WHERE m_id = @m_id;
-- UPDATE revenue_rule_machine
-- SET status = 1, update_time = UNIX_TIMESTAMP()
-- WHERE m_id = @m_id AND rr_id = @rr_fixed_id;
-- SELECT '设备固定比例' AS active_scene, rr.rule_name, rr.rule_mode, rrm.status
-- FROM revenue_rule_machine rrm
-- JOIN revenue_rule rr ON rr.rr_id = rrm.rr_id
-- WHERE rrm.m_id = @m_id AND rrm.status = 1;

-- ------------------------------------------------------------
-- 场景四：设备阶梯分账
-- 建议使用A组织商品测试。
-- 预期：A、B分别按照设备当月营业额命中各自阶梯。
-- ------------------------------------------------------------
-- UPDATE revenue_rule_machine
-- SET status = 2, update_time = UNIX_TIMESTAMP()
-- WHERE m_id = @m_id;
-- UPDATE revenue_rule_machine
-- SET status = 1, update_time = UNIX_TIMESTAMP()
-- WHERE m_id = @m_id AND rr_id = @rr_tier_id;
-- SELECT '设备阶梯分账' AS active_scene, rr.rule_name, rr.rule_mode, rrm.status
-- FROM revenue_rule_machine rrm
-- JOIN revenue_rule rr ON rr.rr_id = rrm.rr_id
-- WHERE rrm.m_id = @m_id AND rrm.status = 1;

-- ------------------------------------------------------------
-- 场景五：T+1延期结算
-- 可与普通/出租/固定/阶梯任一计算场景组合。
-- 预期：支付成功后 revenue_order.status=2，planned_revenue_time有值。
-- ------------------------------------------------------------
-- UPDATE revenue_payee_config
-- SET settlement_type = 2,
--     settlement_days = 1,
--     status = 1,
--     update_time = UNIX_TIMESTAMP()
-- WHERE sp_id = @sp_id;

-- 恢复即时结算：
-- UPDATE revenue_payee_config
-- SET settlement_type = 1,
--     settlement_days = 0,
--     status = 1,
--     update_time = UNIX_TIMESTAMP()
-- WHERE sp_id = @sp_id;

-- ============================================================
-- 11. 测试订单结果查询与场景验收
-- 设置真实测试订单ID后执行查询。
-- ============================================================
SET @test_order_id := NULL;

SELECT
  so.order_id,
  so.trade_no,
  so.m_id,
  so.machine_id,
  so.ao_id AS order_payer_ao_id,
  so.total_price,
  so.pay_type,
  so.pay_status
FROM sale_orders so
WHERE so.order_id = @test_order_id;

SELECT
  sod.sod_id,
  sod.order_id,
  sod.mg_id,
  sod.g_id,
  sod.g_name,
  sod.sod_ao_id,
  sod.quantity,
  sod.total_sod_price
FROM sale_orders_details sod
WHERE sod.order_id = @test_order_id
ORDER BY sod.sod_id;

SELECT
  ro.ro_id,
  ro.order_id,
  ro.sod_id,
  ro.source,
  ro.rule_mode,
  ro.rr_id,
  ro.rri_id,
  ro.rrit_id,
  ro.payer_ao_id,
  ro.receiver_ao_id,
  ro.ra_id,
  ro.manager_id,
  ro.calc_type,
  ro.income_value,
  ro.income_amount,
  ro.period_amount_before,
  ro.period_amount_after,
  ro.settlement_type,
  ro.settlement_days,
  ro.status,
  FROM_UNIXTIME(ro.planned_revenue_time) AS planned_revenue_time,
  FROM_UNIXTIME(ro.revenue_time) AS revenue_time
FROM revenue_order
WHERE order_id = @test_order_id
ORDER BY ro_id;

-- 汇总验收：
-- 普通分账应只有 normal，且A账户合计为订单剩余金额；
-- 出租分账应包含 rental，B账户金额为B商品交易金额乘策略比例；
-- 固定比例应包含 device_rule，B/C分别为20%/30%；
-- 阶梯分账应包含 tier，且A/B比例与命中阶梯一致。
SELECT
  source,
  receiver_ao_id,
  manager_id,
  income_value,
  COUNT(*) AS revenue_row_count,
  SUM(income_amount) AS total_income_amount,
  MIN(status) AS min_status,
  MAX(status) AS max_status
FROM revenue_order
WHERE order_id = @test_order_id
GROUP BY source, receiver_ao_id, manager_id, income_value
ORDER BY source, receiver_ao_id;

SELECT manager_id, ao_id, nickname, account, balance
FROM auth_manager
WHERE manager_id IN (@manager_a_id, @manager_b_id, @manager_c_id)
ORDER BY manager_id;

-- ============================================================
-- 12. 可选清理与恢复 SQL
-- 默认注释，确认测试订单及分账记录不再需要后再手工执行。
-- 清理顺序必须从依赖表到主表。
-- 注意：
-- 1. revenue_pay_channel、revenue_payee_config 可能在测试前已存在，不应直接删除。
-- 2. 请使用第4节导入前保存的查询结果恢复原值。
-- 3. 如果测试前确认两表均无对应记录，才可执行下方标注的可选删除语句。
-- 4. 还需恢复出租测试商品原始 ao_id，以及设备原有 revenue_rule_machine 绑定状态。
-- ============================================================
-- START TRANSACTION;
-- DELETE FROM revenue_order
-- WHERE machine_id = @machine_code
--   AND (
--     rr_id IN (@rr_rental_id, @rr_fixed_id, @rr_tier_id)
--     OR ra_id IN (@ra_a_id, @ra_b_id, @ra_c_id)
--   );
-- DELETE FROM revenue_rule_machine WHERE rr_id IN (@rr_rental_id, @rr_fixed_id, @rr_tier_id);
-- DELETE FROM revenue_rule_item_tier WHERE rri_id IN (
--   SELECT rri_id FROM revenue_rule_item WHERE rr_id IN (@rr_rental_id, @rr_fixed_id, @rr_tier_id)
-- );
-- DELETE FROM revenue_rule_item WHERE rr_id IN (@rr_rental_id, @rr_fixed_id, @rr_tier_id);
-- DELETE FROM revenue_rule WHERE rr_id IN (@rr_rental_id, @rr_fixed_id, @rr_tier_id);
-- 仅当测试前 revenue_payee_config 不存在对应 sp_id 时执行：
-- DELETE FROM revenue_payee_config WHERE sp_id = @sp_id;
-- 仅当测试前 revenue_pay_channel 不存在对应 pay_type 时执行：
-- DELETE FROM revenue_pay_channel WHERE pay_type = @pay_type;
-- DELETE FROM revenue_account WHERE ra_id IN (@ra_a_id, @ra_b_id, @ra_c_id);
-- DELETE FROM auth_manager WHERE manager_id IN (@manager_a_id, @manager_b_id, @manager_c_id);
-- DELETE FROM auth_organization WHERE ao_id IN (@org_b_id, @org_c_id);
-- COMMIT;
