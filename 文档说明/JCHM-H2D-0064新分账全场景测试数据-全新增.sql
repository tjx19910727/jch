-- JCHM-H2D-0064 新分账全场景测试数据（除设备外全部新增）
-- 仅用于测试库手工导入。目标：只复用设备 JCHM-H2D-0064，其它组织、管理员、收款策略、设备收款绑定、
-- 分账账户、分账触发渠道、分账规则均使用 REVNEW_JCHM_H2D_0064 前缀新增。
--
-- 覆盖场景：
-- 1. 普通分账：未启用设备规则时，订单剩余金额 100% 分给 A 默认账户。
-- 2. 设备出租：订单明细 sod_ao_id = B 组织时，B 按商品金额 100% 分账。
-- 3. 设备固定比例：整单金额 B 20%、C 30%。
-- 4. 设备阶梯分账：A [0,5000) 20% / [5000,+∞) 25%，B [0,8000) 25% / [8000,+∞) 30%。
-- 5. T+1 延期结算：同任意计算场景组合，支付成功后 revenue_order 进入待结算。
-- 6. 渠道停用/收款策略关闭：支付继续但不生成 revenue_order。
-- 7. 设备出租非 100%：B 组织商品金额按 80% 给 B，剩余金额走普通分账。
--
-- 使用方式：
-- A. 先整段执行 0-8 节初始化。
-- B. 每个场景测试前，从第 9 节复制对应“场景切换 SQL”执行，然后新建订单支付。
-- C. 使用第 10 节设置 @test_order_id 后查询 revenue_order 验收。

SET NAMES utf8mb4;
START TRANSACTION;

-- ============================================================
-- 0. 固定变量与目标设备
-- ============================================================
SET @test_prefix := 'REVNEW_JCHM_H2D_0064';
SET @machine_code := 'JCHM-H2D-0064';
SET @now := UNIX_TIMESTAMP();
SET @pay_type := 20; -- 余额支付，避免依赖第三方支付证书

SET @m_id := (SELECT m_id FROM machine WHERE machine_id = @machine_code LIMIT 1);
SET @machine_name := (SELECT machine_name FROM machine WHERE machine_id = @machine_code LIMIT 1);
SET @payer_ao_id := (SELECT ao_id FROM machine WHERE machine_id = @machine_code LIMIT 1);
SET @payer_org_level := COALESCE((SELECT level FROM auth_organization WHERE ao_id = @payer_ao_id LIMIT 1), 0);

-- 任一检查为 FAIL 时，请停止导入并先修复设备基础数据。
SELECT
  IF(@m_id IS NULL, 'FAIL: 未找到目标设备', 'PASS') AS machine_check,
  IF(@payer_ao_id IS NULL, 'FAIL: 目标设备缺少 ao_id', 'PASS') AS machine_ao_check,
  @m_id AS m_id,
  @machine_code AS machine_id,
  @machine_name AS machine_name,
  @payer_ao_id AS payer_ao_id;

-- ============================================================
-- 1. 新增测试组织 B、C（A 使用设备所属组织）
-- ============================================================
INSERT INTO auth_organization
  (pid, level, organization_name, is_top, sort, creator, create_time, update_time)
SELECT @payer_ao_id, @payer_org_level + 1, CONCAT(@test_prefix, '_ORG_B'), 2, 9101, 0, @now, @now
WHERE @payer_ao_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM auth_organization WHERE organization_name = CONCAT(@test_prefix, '_ORG_B'));

INSERT INTO auth_organization
  (pid, level, organization_name, is_top, sort, creator, create_time, update_time)
SELECT @payer_ao_id, @payer_org_level + 1, CONCAT(@test_prefix, '_ORG_C'), 2, 9102, 0, @now, @now
WHERE @payer_ao_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM auth_organization WHERE organization_name = CONCAT(@test_prefix, '_ORG_C'));

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
-- 2. 新增 A/B/C 测试管理员（仅作为余额型分账账户管理人）
-- ============================================================
INSERT INTO auth_manager
  (ao_id, nickname, account, password, pid, level, balance, frozen, withdrawal,
   bill_account, real_name, status, audit_status, creator, create_time, update_time)
SELECT @payer_ao_id, CONCAT(@test_prefix, '_MANAGER_A'), CONCAT(@test_prefix, '_MANAGER_A'),
       MD5(CONCAT(@test_prefix, '_NO_LOGIN')), 0, 1, 0, 0, 0,
       CONCAT(@test_prefix, '_BILL_A'), '新分账全场景测试A', 1, 2, 0, @now, @now
WHERE @payer_ao_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM auth_manager WHERE account = CONCAT(@test_prefix, '_MANAGER_A'));

INSERT INTO auth_manager
  (ao_id, nickname, account, password, pid, level, balance, frozen, withdrawal,
   bill_account, real_name, status, audit_status, creator, create_time, update_time)
SELECT @org_b_id, CONCAT(@test_prefix, '_MANAGER_B'), CONCAT(@test_prefix, '_MANAGER_B'),
       MD5(CONCAT(@test_prefix, '_NO_LOGIN')), 0, 1, 0, 0, 0,
       CONCAT(@test_prefix, '_BILL_B'), '新分账全场景测试B', 1, 2, 0, @now, @now
WHERE @org_b_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM auth_manager WHERE account = CONCAT(@test_prefix, '_MANAGER_B'));

INSERT INTO auth_manager
  (ao_id, nickname, account, password, pid, level, balance, frozen, withdrawal,
   bill_account, real_name, status, audit_status, creator, create_time, update_time)
SELECT @org_c_id, CONCAT(@test_prefix, '_MANAGER_C'), CONCAT(@test_prefix, '_MANAGER_C'),
       MD5(CONCAT(@test_prefix, '_NO_LOGIN')), 0, 1, 0, 0, 0,
       CONCAT(@test_prefix, '_BILL_C'), '新分账全场景测试C', 1, 2, 0, @now, @now
WHERE @org_c_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM auth_manager WHERE account = CONCAT(@test_prefix, '_MANAGER_C'));

SET @manager_a_id := (
  SELECT manager_id FROM auth_manager WHERE account = CONCAT(@test_prefix, '_MANAGER_A') ORDER BY manager_id DESC LIMIT 1
);
SET @manager_b_id := (
  SELECT manager_id FROM auth_manager WHERE account = CONCAT(@test_prefix, '_MANAGER_B') ORDER BY manager_id DESC LIMIT 1
);
SET @manager_c_id := (
  SELECT manager_id FROM auth_manager WHERE account = CONCAT(@test_prefix, '_MANAGER_C') ORDER BY manager_id DESC LIMIT 1
);

-- ============================================================
-- 3. 新增余额型分账账户 A/B/C
-- ============================================================
INSERT INTO revenue_account
  (ao_id, manager_id, account_name, account, account_type, bill_account, status, creator, create_time, update_time)
SELECT @payer_ao_id, @manager_a_id, CONCAT(@test_prefix, '_RA_A'), CONCAT(@test_prefix, '_BALANCE_A'),
       'balance', CONCAT(@test_prefix, '_BALANCE_A'), 1, 0, @now, @now
WHERE @manager_a_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM revenue_account WHERE account = CONCAT(@test_prefix, '_BALANCE_A'));

INSERT INTO revenue_account
  (ao_id, manager_id, account_name, account, account_type, bill_account, status, creator, create_time, update_time)
SELECT @org_b_id, @manager_b_id, CONCAT(@test_prefix, '_RA_B'), CONCAT(@test_prefix, '_BALANCE_B'),
       'balance', CONCAT(@test_prefix, '_BALANCE_B'), 1, 0, @now, @now
WHERE @manager_b_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM revenue_account WHERE account = CONCAT(@test_prefix, '_BALANCE_B'));

INSERT INTO revenue_account
  (ao_id, manager_id, account_name, account, account_type, bill_account, status, creator, create_time, update_time)
SELECT @org_c_id, @manager_c_id, CONCAT(@test_prefix, '_RA_C'), CONCAT(@test_prefix, '_BALANCE_C'),
       'balance', CONCAT(@test_prefix, '_BALANCE_C'), 1, 0, @now, @now
WHERE @manager_c_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM revenue_account WHERE account = CONCAT(@test_prefix, '_BALANCE_C'));

SET @ra_a_id := (SELECT ra_id FROM revenue_account WHERE account = CONCAT(@test_prefix, '_BALANCE_A') ORDER BY ra_id DESC LIMIT 1);
SET @ra_b_id := (SELECT ra_id FROM revenue_account WHERE account = CONCAT(@test_prefix, '_BALANCE_B') ORDER BY ra_id DESC LIMIT 1);
SET @ra_c_id := (SELECT ra_id FROM revenue_account WHERE account = CONCAT(@test_prefix, '_BALANCE_C') ORDER BY ra_id DESC LIMIT 1);

-- ============================================================
-- 4. 新增余额支付收款策略，并高优先级绑定到目标设备
-- ============================================================
INSERT INTO strategy_payee
  (sp_name, title, payee_type, app_id, mch_id, content, ico, status, ao_id, creator, create_time, update_id, update_time)
SELECT CONCAT(@test_prefix, '_余额支付收款策略'), CONCAT(@test_prefix, '_余额支付'),
       @pay_type, '', '', '{"appid":"REVNEW_BALANCE_PAY"}', '', 1, @payer_ao_id, 0, @now, 0, @now
WHERE @payer_ao_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM strategy_payee
    WHERE sp_name = CONCAT(@test_prefix, '_余额支付收款策略')
  );

SET @sp_id := (
  SELECT sp_id FROM strategy_payee
  WHERE sp_name = CONCAT(@test_prefix, '_余额支付收款策略')
  ORDER BY sp_id DESC LIMIT 1
);

INSERT INTO strategy_machine
  (s_id, m_id, s_type, sort, ao_id)
SELECT @sp_id, @m_id, 1, -999, @payer_ao_id
WHERE @sp_id IS NOT NULL
  AND @m_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM strategy_machine
    WHERE s_id = @sp_id AND m_id = @m_id AND s_type = 1
  );

-- ============================================================
-- 5. 新增新分账触发渠道与收款策略新分账配置
-- ============================================================
INSERT INTO revenue_pay_channel
  (pay_type, payee_type, channel_name, settlement_type, settlement_days, status, creator, create_time, update_time)
SELECT @pay_type, @pay_type, CONCAT(@test_prefix, '_余额支付即时分账'), 1, 0, 1, 0, @now, @now
WHERE NOT EXISTS (
  SELECT 1 FROM revenue_pay_channel WHERE pay_type = @pay_type
);

-- 如果测试库已存在 pay_type=20 的渠道，使用当前测试渠道配置覆盖成启用即时分账。
UPDATE revenue_pay_channel
SET payee_type = @pay_type,
    channel_name = CONCAT(@test_prefix, '_余额支付即时分账'),
    settlement_type = 1,
    settlement_days = 0,
    status = 1,
    update_time = @now
WHERE pay_type = @pay_type;

INSERT INTO revenue_payee_config
  (sp_id, payee_type, ao_id, default_ra_id, default_manager_id, enable_revenue, status, creator, create_time, update_time)
SELECT @sp_id, @pay_type, @payer_ao_id, @ra_a_id, @manager_a_id, 1, 1, 0, @now, @now
WHERE @sp_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM revenue_payee_config WHERE sp_id = @sp_id);

UPDATE revenue_payee_config
SET payee_type = @pay_type,
    ao_id = @payer_ao_id,
    default_ra_id = @ra_a_id,
    default_manager_id = @manager_a_id,
    enable_revenue = 1,
    status = 1,
    update_time = @now
WHERE sp_id = @sp_id;

-- ============================================================
-- 6. 新增设备出租规则：B 组织商品金额 100% 给 B
-- ============================================================
INSERT INTO revenue_rule
  (rule_name, rule_mode, payer_ao_id, base_type, turnover_type, tier_calc_mode, status, creator, create_time, update_time)
SELECT CONCAT(@test_prefix, '_设备出租_B100'), 2, @payer_ao_id, 1, 1, 1, 1, 0, @now, @now
WHERE NOT EXISTS (SELECT 1 FROM revenue_rule WHERE rule_name = CONCAT(@test_prefix, '_设备出租_B100'));

SET @rr_rental_id := (
  SELECT rr_id FROM revenue_rule WHERE rule_name = CONCAT(@test_prefix, '_设备出租_B100') ORDER BY rr_id DESC LIMIT 1
);

INSERT INTO revenue_rule_item
  (rr_id, receiver_ao_id, ra_id, manager_id, calc_type, calc_value, sort, status, create_time, update_time)
SELECT @rr_rental_id, @org_b_id, @ra_b_id, @manager_b_id, 1, 100.000, 1, 1, @now, @now
WHERE NOT EXISTS (
  SELECT 1 FROM revenue_rule_item WHERE rr_id = @rr_rental_id AND receiver_ao_id = @org_b_id
);

INSERT INTO revenue_rule_machine
  (rr_id, m_id, ao_id, sort, status, create_time, update_time)
SELECT @rr_rental_id, @m_id, @payer_ao_id, 10, 2, @now, @now
WHERE @m_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM revenue_rule_machine WHERE rr_id = @rr_rental_id AND m_id = @m_id);

-- ============================================================
-- 7. 新增设备固定比例规则：B 20%，C 30%
-- ============================================================
INSERT INTO revenue_rule
  (rule_name, rule_mode, payer_ao_id, base_type, turnover_type, tier_calc_mode, status, creator, create_time, update_time)
SELECT CONCAT(@test_prefix, '_设备固定比例_B20_C30'), 3, @payer_ao_id, 1, 1, 1, 1, 0, @now, @now
WHERE NOT EXISTS (SELECT 1 FROM revenue_rule WHERE rule_name = CONCAT(@test_prefix, '_设备固定比例_B20_C30'));

SET @rr_fixed_id := (
  SELECT rr_id FROM revenue_rule WHERE rule_name = CONCAT(@test_prefix, '_设备固定比例_B20_C30') ORDER BY rr_id DESC LIMIT 1
);

INSERT INTO revenue_rule_item
  (rr_id, receiver_ao_id, ra_id, manager_id, calc_type, calc_value, sort, status, create_time, update_time)
SELECT @rr_fixed_id, @org_b_id, @ra_b_id, @manager_b_id, 1, 20.000, 1, 1, @now, @now
WHERE NOT EXISTS (SELECT 1 FROM revenue_rule_item WHERE rr_id = @rr_fixed_id AND receiver_ao_id = @org_b_id);

INSERT INTO revenue_rule_item
  (rr_id, receiver_ao_id, ra_id, manager_id, calc_type, calc_value, sort, status, create_time, update_time)
SELECT @rr_fixed_id, @org_c_id, @ra_c_id, @manager_c_id, 1, 30.000, 2, 1, @now, @now
WHERE NOT EXISTS (SELECT 1 FROM revenue_rule_item WHERE rr_id = @rr_fixed_id AND receiver_ao_id = @org_c_id);

INSERT INTO revenue_rule_machine
  (rr_id, m_id, ao_id, sort, status, create_time, update_time)
SELECT @rr_fixed_id, @m_id, @payer_ao_id, 20, 2, @now, @now
WHERE @m_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM revenue_rule_machine WHERE rr_id = @rr_fixed_id AND m_id = @m_id);

-- ============================================================
-- 8. 新增设备阶梯规则：A/B 各自独立阶梯
-- ============================================================
INSERT INTO revenue_rule
  (rule_name, rule_mode, payer_ao_id, base_type, turnover_type, tier_calc_mode, status, creator, create_time, update_time)
SELECT CONCAT(@test_prefix, '_设备阶梯_A_B'), 3, @payer_ao_id, 1, 1, 1, 1, 0, @now, @now
WHERE NOT EXISTS (SELECT 1 FROM revenue_rule WHERE rule_name = CONCAT(@test_prefix, '_设备阶梯_A_B'));

SET @rr_tier_id := (
  SELECT rr_id FROM revenue_rule WHERE rule_name = CONCAT(@test_prefix, '_设备阶梯_A_B') ORDER BY rr_id DESC LIMIT 1
);

INSERT INTO revenue_rule_item
  (rr_id, receiver_ao_id, ra_id, manager_id, calc_type, calc_value, sort, status, create_time, update_time)
SELECT @rr_tier_id, @payer_ao_id, @ra_a_id, @manager_a_id, 4, 0.000, 1, 1, @now, @now
WHERE NOT EXISTS (SELECT 1 FROM revenue_rule_item WHERE rr_id = @rr_tier_id AND receiver_ao_id = @payer_ao_id);

INSERT INTO revenue_rule_item
  (rr_id, receiver_ao_id, ra_id, manager_id, calc_type, calc_value, sort, status, create_time, update_time)
SELECT @rr_tier_id, @org_b_id, @ra_b_id, @manager_b_id, 4, 0.000, 2, 1, @now, @now
WHERE NOT EXISTS (SELECT 1 FROM revenue_rule_item WHERE rr_id = @rr_tier_id AND receiver_ao_id = @org_b_id);

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
  AND NOT EXISTS (SELECT 1 FROM revenue_rule_machine WHERE rr_id = @rr_tier_id AND m_id = @m_id);

COMMIT;

-- ============================================================
-- 初始化检查
-- ============================================================
SELECT
  @m_id AS m_id,
  @machine_code AS machine_id,
  @payer_ao_id AS org_a_id,
  @org_b_id AS org_b_id,
  @org_c_id AS org_c_id,
  @sp_id AS test_sp_id,
  @pay_type AS test_pay_type,
  @ra_a_id AS ra_a_id,
  @ra_b_id AS ra_b_id,
  @ra_c_id AS ra_c_id,
  @rr_rental_id AS rr_rental_id,
  @rr_fixed_id AS rr_fixed_id,
  @rr_tier_id AS rr_tier_id;

SELECT sm.*, sp.sp_name, sp.payee_type, sp.status AS sp_status
FROM strategy_machine sm
JOIN strategy_payee sp ON sp.sp_id = sm.s_id
WHERE sm.m_id = @m_id AND sm.s_type = 1
ORDER BY sm.sort ASC, sm.sm_id DESC;

SELECT rr.*, rrm.rrm_id, rrm.m_id, rrm.status AS bind_status
FROM revenue_rule rr
LEFT JOIN revenue_rule_machine rrm ON rrm.rr_id = rr.rr_id AND rrm.m_id = @m_id
WHERE rr.rule_name LIKE CONCAT(@test_prefix, '%')
ORDER BY rr.rr_id;

-- ============================================================
-- 9. 场景切换 SQL（按需复制执行）
-- 新会话先执行变量恢复段。
-- ============================================================

-- 变量恢复段：
-- SET @test_prefix := 'REVNEW_JCHM_H2D_0064';
-- SET @machine_code := 'JCHM-H2D-0064';
-- SET @pay_type := 20;
-- SET @m_id := (SELECT m_id FROM machine WHERE machine_id = @machine_code LIMIT 1);
-- SET @sp_id := (SELECT sp_id FROM strategy_payee WHERE sp_name = CONCAT(@test_prefix, '_余额支付收款策略') ORDER BY sp_id DESC LIMIT 1);
-- SET @rr_rental_id := (SELECT rr_id FROM revenue_rule WHERE rule_name = CONCAT(@test_prefix, '_设备出租_B100') ORDER BY rr_id DESC LIMIT 1);
-- SET @rr_fixed_id := (SELECT rr_id FROM revenue_rule WHERE rule_name = CONCAT(@test_prefix, '_设备固定比例_B20_C30') ORDER BY rr_id DESC LIMIT 1);
-- SET @rr_tier_id := (SELECT rr_id FROM revenue_rule WHERE rule_name = CONCAT(@test_prefix, '_设备阶梯_A_B') ORDER BY rr_id DESC LIMIT 1);

-- 场景一：普通分账。预期 source=normal，A 默认账户 100%。
-- UPDATE revenue_rule_machine SET status = 2, update_time = UNIX_TIMESTAMP() WHERE m_id = @m_id;
-- UPDATE revenue_pay_channel SET settlement_type = 1, settlement_days = 0, status = 1, update_time = UNIX_TIMESTAMP() WHERE pay_type = @pay_type;

-- 场景二：设备出租。预期 B 组织商品明细生成 source=rental。
-- 注意：订单明细 sod_ao_id 必须等于本脚本创建的 B 组织 ao_id。
-- UPDATE revenue_rule_machine SET status = 2, update_time = UNIX_TIMESTAMP() WHERE m_id = @m_id;
-- UPDATE revenue_rule_machine SET status = 1, update_time = UNIX_TIMESTAMP() WHERE m_id = @m_id AND rr_id = @rr_rental_id;
-- UPDATE revenue_rule_item SET calc_type = 1, calc_value = 100.000, update_time = UNIX_TIMESTAMP() WHERE rr_id = @rr_rental_id;
-- UPDATE revenue_pay_channel SET settlement_type = 1, settlement_days = 0, status = 1, update_time = UNIX_TIMESTAMP() WHERE pay_type = @pay_type;

-- 场景二扩展：设备出租 80%。预期 B 商品 source=rental 按 80%，剩余订单金额 source=normal 给 A。
-- UPDATE revenue_rule_machine SET status = 2, update_time = UNIX_TIMESTAMP() WHERE m_id = @m_id;
-- UPDATE revenue_rule_machine SET status = 1, update_time = UNIX_TIMESTAMP() WHERE m_id = @m_id AND rr_id = @rr_rental_id;
-- UPDATE revenue_rule_item SET calc_type = 1, calc_value = 80.000, update_time = UNIX_TIMESTAMP() WHERE rr_id = @rr_rental_id;
-- UPDATE revenue_pay_channel SET settlement_type = 1, settlement_days = 0, status = 1, update_time = UNIX_TIMESTAMP() WHERE pay_type = @pay_type;

-- 场景三：设备固定比例。预期 source=device_rule，B 20%、C 30%。
-- UPDATE revenue_rule_machine SET status = 2, update_time = UNIX_TIMESTAMP() WHERE m_id = @m_id;
-- UPDATE revenue_rule_machine SET status = 1, update_time = UNIX_TIMESTAMP() WHERE m_id = @m_id AND rr_id = @rr_fixed_id;
-- UPDATE revenue_pay_channel SET settlement_type = 1, settlement_days = 0, status = 1, update_time = UNIX_TIMESTAMP() WHERE pay_type = @pay_type;

-- 场景四：设备阶梯。预期 source=tier，按设备当月营业额命中阶梯。
-- UPDATE revenue_rule_machine SET status = 2, update_time = UNIX_TIMESTAMP() WHERE m_id = @m_id;
-- UPDATE revenue_rule_machine SET status = 1, update_time = UNIX_TIMESTAMP() WHERE m_id = @m_id AND rr_id = @rr_tier_id;
-- UPDATE revenue_pay_channel SET settlement_type = 1, settlement_days = 0, status = 1, update_time = UNIX_TIMESTAMP() WHERE pay_type = @pay_type;

-- 场景五：T+1 延期结算。可与普通/出租/固定/阶梯任一场景组合。
-- UPDATE revenue_pay_channel SET settlement_type = 2, settlement_days = 1, status = 1, update_time = UNIX_TIMESTAMP() WHERE pay_type = @pay_type;

-- 场景六：渠道停用。预期支付继续，但不生成 revenue_order。
-- UPDATE revenue_rule_machine SET status = 2, update_time = UNIX_TIMESTAMP() WHERE m_id = @m_id;
-- UPDATE revenue_pay_channel SET status = 2, update_time = UNIX_TIMESTAMP() WHERE pay_type = @pay_type;

-- 场景七：收款策略关闭新分账。预期支付继续，但不生成 revenue_order。
-- UPDATE revenue_rule_machine SET status = 2, update_time = UNIX_TIMESTAMP() WHERE m_id = @m_id;
-- UPDATE revenue_pay_channel SET settlement_type = 1, settlement_days = 0, status = 1, update_time = UNIX_TIMESTAMP() WHERE pay_type = @pay_type;
-- UPDATE revenue_payee_config SET enable_revenue = 2, update_time = UNIX_TIMESTAMP() WHERE sp_id = @sp_id;
-- 恢复收款策略启用新分账：
-- UPDATE revenue_payee_config SET enable_revenue = 1, status = 1, update_time = UNIX_TIMESTAMP() WHERE sp_id = @sp_id;

-- 支付取消/失败状态流转：
-- - 支付取消预期待支付 revenue_order.status 从 0 更新为 4。
-- - 支付失败预期待支付或待结算 revenue_order.status 更新为 3。
-- - 管理端测试环境可使用 /management/revenue.revenue_order/mockPaySuccess 模拟支付成功。
-- - 取消/失败通常走业务接口或回调逻辑触发；下方只提供验收查询，不建议直接 UPDATE 伪造结果。

-- ============================================================
-- 10. 验收查询
-- ============================================================
-- SET @test_order_id := 0;
-- SELECT order_id, trade_no, m_id, machine_id, ao_id, total_price, pay_type, sp_id, pay_status
-- FROM sale_orders WHERE order_id = @test_order_id;
-- SELECT sod_id, order_id, g_id, g_name, sod_ao_id, quantity, total_sod_price
-- FROM sale_orders_details WHERE order_id = @test_order_id ORDER BY sod_id;
-- SELECT ro_id, order_id, sod_id, source, rule_mode, rr_id, rri_id, rrit_id,
--        payer_ao_id, receiver_ao_id, ra_id, manager_id, account_type,
--        calc_type, income_value, income_amount, refund_amount,
--        period_key, period_amount_before, period_amount_after,
--        settlement_type, settlement_days, status,
--        FROM_UNIXTIME(planned_revenue_time) AS planned_revenue_time,
--        FROM_UNIXTIME(revenue_time) AS revenue_time
-- FROM revenue_order
-- WHERE order_id = @test_order_id
-- ORDER BY ro_id;
-- SELECT source, receiver_ao_id, manager_id, income_value, SUM(income_amount) AS amount
-- FROM revenue_order
-- WHERE order_id = @test_order_id
-- GROUP BY source, receiver_ao_id, manager_id, income_value
-- ORDER BY source, receiver_ao_id;

-- ============================================================
-- 11. 可选清理 SQL（确认测试订单不再需要后手工执行）
-- ============================================================
-- SET @test_prefix := 'REVNEW_JCHM_H2D_0064';
-- SET @machine_code := 'JCHM-H2D-0064';
-- SET @m_id := (SELECT m_id FROM machine WHERE machine_id = @machine_code LIMIT 1);
-- SET @sp_id := (SELECT sp_id FROM strategy_payee WHERE sp_name = CONCAT(@test_prefix, '_余额支付收款策略') ORDER BY sp_id DESC LIMIT 1);
-- SET @manager_a_id := (SELECT manager_id FROM auth_manager WHERE account = CONCAT(@test_prefix, '_MANAGER_A') ORDER BY manager_id DESC LIMIT 1);
-- SET @manager_b_id := (SELECT manager_id FROM auth_manager WHERE account = CONCAT(@test_prefix, '_MANAGER_B') ORDER BY manager_id DESC LIMIT 1);
-- SET @manager_c_id := (SELECT manager_id FROM auth_manager WHERE account = CONCAT(@test_prefix, '_MANAGER_C') ORDER BY manager_id DESC LIMIT 1);
-- SET @org_b_id := (SELECT ao_id FROM auth_organization WHERE organization_name = CONCAT(@test_prefix, '_ORG_B') ORDER BY ao_id DESC LIMIT 1);
-- SET @org_c_id := (SELECT ao_id FROM auth_organization WHERE organization_name = CONCAT(@test_prefix, '_ORG_C') ORDER BY ao_id DESC LIMIT 1);
-- START TRANSACTION;
-- DELETE FROM revenue_order WHERE sp_id = @sp_id OR machine_id = @machine_code AND source IN ('normal','rental','device_rule','tier');
-- DELETE rrit FROM revenue_rule_item_tier rrit
-- JOIN revenue_rule_item rri ON rri.rri_id = rrit.rri_id
-- JOIN revenue_rule rr ON rr.rr_id = rri.rr_id
-- WHERE rr.rule_name LIKE CONCAT(@test_prefix, '%');
-- DELETE rri FROM revenue_rule_item rri JOIN revenue_rule rr ON rr.rr_id = rri.rr_id WHERE rr.rule_name LIKE CONCAT(@test_prefix, '%');
-- DELETE rrm FROM revenue_rule_machine rrm JOIN revenue_rule rr ON rr.rr_id = rrm.rr_id WHERE rr.rule_name LIKE CONCAT(@test_prefix, '%');
-- DELETE FROM revenue_rule WHERE rule_name LIKE CONCAT(@test_prefix, '%');
-- DELETE FROM revenue_payee_config WHERE sp_id = @sp_id;
-- DELETE FROM strategy_machine WHERE s_id = @sp_id AND m_id = @m_id AND s_type = 1;
-- DELETE FROM strategy_payee WHERE sp_id = @sp_id;
-- DELETE FROM revenue_account WHERE account LIKE CONCAT(@test_prefix, '%');
-- DELETE FROM auth_manager WHERE manager_id IN (@manager_a_id, @manager_b_id, @manager_c_id);
-- DELETE FROM auth_organization WHERE ao_id IN (@org_b_id, @org_c_id);
-- COMMIT;
