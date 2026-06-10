-- 分账逻辑优化数据库变更
-- 说明：本需求分账逻辑完全独立于旧分账逻辑，不复用旧分账策略表和旧分账结果表。
-- 首次执行前请确认新表名称未被占用。

CREATE TABLE IF NOT EXISTS `revenue_account` (
  `ra_id` int NOT NULL AUTO_INCREMENT COMMENT '分账账户ID',
  `ao_id` int NOT NULL COMMENT '账户所属组织ID',
  `manager_id` int NOT NULL COMMENT '账户管理人ID',
  `account_name` varchar(100) DEFAULT NULL COMMENT '账户名称',
  `account` varchar(255) DEFAULT NULL COMMENT '分账账户',
  `account_type` varchar(50) DEFAULT 'balance' COMMENT '账户类型 balance/bank/alipay/wechat/jd_account',
  `bill_account` varchar(255) DEFAULT NULL COMMENT '账户展示账号',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态：1启用，2停用',
  `creator` int DEFAULT NULL COMMENT '创建人',
  `create_time` int DEFAULT NULL,
  `update_time` int DEFAULT NULL,
  PRIMARY KEY (`ra_id`),
  KEY `idx_ao_manager` (`ao_id`, `manager_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='分账账户表';

CREATE TABLE IF NOT EXISTS `revenue_rule` (
  `rr_id` int NOT NULL AUTO_INCREMENT COMMENT '分账策略组ID',
  `rule_name` varchar(100) NOT NULL COMMENT '策略名称',
  `rule_mode` tinyint(1) NOT NULL COMMENT '模式：1普通分账，2设备出租，3设备分账，4设备商品分账',
  `payer_ao_id` int DEFAULT NULL COMMENT '收款/代收组织ID',
  `base_type` tinyint(1) DEFAULT 1 COMMENT '分账基数：1订单总额，2扣除出租商品后金额',
  `turnover_type` tinyint(1) DEFAULT 1 COMMENT '阶梯营业额口径：1净营业额，2支付成功金额',
  `tier_calc_mode` tinyint(1) DEFAULT 1 COMMENT '阶梯命中：1本单后累计整单命中，2跨阶梯拆分',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态：1启用，2停用',
  `creator` int DEFAULT NULL,
  `create_time` int DEFAULT NULL,
  `update_time` int DEFAULT NULL,
  PRIMARY KEY (`rr_id`),
  KEY `idx_mode_status` (`rule_mode`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='分账策略组表';

CREATE TABLE IF NOT EXISTS `revenue_rule_item` (
  `rri_id` int NOT NULL AUTO_INCREMENT COMMENT '分账策略明细ID',
  `rr_id` int NOT NULL COMMENT '分账策略组ID',
  `g_id` int DEFAULT NULL COMMENT '商品ID，设备商品分账模式必填',
  `receiver_ao_id` int NOT NULL COMMENT '分账接收组织ID',
  `ra_id` int NOT NULL COMMENT '分账账户ID',
  `manager_id` int NOT NULL COMMENT '账户管理人ID',
  `calc_type` tinyint(1) DEFAULT 1 COMMENT '计算方式：1百分比，2固定金额，3全额，4阶梯百分比',
  `calc_value` decimal(10,3) DEFAULT 0.000 COMMENT '百分比或固定金额；阶梯模式下可为空',
  `sort` int DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `create_time` int DEFAULT NULL,
  `update_time` int DEFAULT NULL,
  PRIMARY KEY (`rri_id`),
  KEY `idx_rr_status` (`rr_id`, `status`),
  KEY `idx_rule_goods_status` (`rr_id`, `g_id`, `status`),
  KEY `idx_receiver` (`receiver_ao_id`),
  KEY `idx_manager` (`manager_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='分账策略明细表';

CREATE TABLE IF NOT EXISTS `revenue_rule_item_tier` (
  `rrit_id` int NOT NULL AUTO_INCREMENT COMMENT '阶梯分账明细ID',
  `rri_id` int NOT NULL COMMENT '分账策略明细ID',
  `threshold_min` decimal(12,2) DEFAULT 0.00 COMMENT '营业额下限，包含',
  `threshold_max` decimal(12,2) DEFAULT NULL COMMENT '营业额上限，不包含；为空表示无上限',
  `calc_value` decimal(10,3) NOT NULL COMMENT '当前阶梯分账比例',
  `sort` int DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `create_time` int DEFAULT NULL,
  `update_time` int DEFAULT NULL,
  PRIMARY KEY (`rrit_id`),
  KEY `idx_rri_status` (`rri_id`, `status`),
  KEY `idx_threshold` (`threshold_min`, `threshold_max`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='阶梯分账明细表';

CREATE TABLE IF NOT EXISTS `revenue_rule_machine` (
  `rrm_id` int NOT NULL AUTO_INCREMENT COMMENT '设备分账策略绑定ID',
  `rr_id` int NOT NULL COMMENT '分账策略组ID',
  `m_id` int NOT NULL COMMENT '设备ID',
  `ao_id` int DEFAULT NULL COMMENT '设备组织ID',
  `sort` int DEFAULT 0,
  `status` tinyint(1) DEFAULT 1,
  `create_time` int DEFAULT NULL,
  `update_time` int DEFAULT NULL,
  PRIMARY KEY (`rrm_id`),
  UNIQUE KEY `uk_machine_rule` (`m_id`, `rr_id`),
  KEY `idx_machine_status` (`m_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='设备分账策略绑定表';

CREATE TABLE IF NOT EXISTS `revenue_pay_channel` (
  `rpc_id` int NOT NULL AUTO_INCREMENT COMMENT '分账收款渠道配置ID',
  `pay_type` int NOT NULL COMMENT '订单支付类型',
  `payee_type` int DEFAULT NULL COMMENT '收款策略支付类型',
  `channel_name` varchar(50) DEFAULT NULL COMMENT '渠道名称',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态：1启用分账，2停用分账',
  `creator` int DEFAULT NULL,
  `create_time` int DEFAULT NULL,
  `update_time` int DEFAULT NULL,
  PRIMARY KEY (`rpc_id`),
  UNIQUE KEY `uk_pay_type` (`pay_type`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='需要分账的收款渠道配置表';

CREATE TABLE IF NOT EXISTS `revenue_payee_config` (
  `rpcfg_id` int NOT NULL AUTO_INCREMENT COMMENT '收款策略新分账配置ID',
  `sp_id` int NOT NULL COMMENT '收款策略ID',
  `payee_type` int DEFAULT NULL COMMENT '收款策略支付类型快照',
  `ao_id` int DEFAULT NULL COMMENT '收款策略所属组织快照',
  `default_ra_id` int DEFAULT NULL COMMENT '默认分账账户ID',
  `default_manager_id` int DEFAULT NULL COMMENT '默认账户管理人ID',
  `enable_revenue` tinyint(1) DEFAULT 1 COMMENT '是否启用新分账：1启用，2停用',
  `settlement_type` tinyint(1) DEFAULT 1 COMMENT '分账时间类型：1即时分账，2 T+N分账',
  `settlement_days` int DEFAULT 0 COMMENT 'T+N分账天数，即时分账为0',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态：1启用，2停用',
  `creator` int DEFAULT NULL,
  `create_time` int DEFAULT NULL,
  `update_time` int DEFAULT NULL,
  PRIMARY KEY (`rpcfg_id`),
  UNIQUE KEY `uk_sp_id` (`sp_id`),
  KEY `idx_ra_manager` (`default_ra_id`, `default_manager_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='收款策略新分账配置表';

CREATE TABLE IF NOT EXISTS `revenue_order` (
  `ro_id` int NOT NULL AUTO_INCREMENT COMMENT '新分账订单ID',
  `order_id` int NOT NULL COMMENT '订单ID',
  `sod_id` int DEFAULT NULL COMMENT '子订单ID',
  `g_id` int DEFAULT NULL COMMENT '商品ID快照',
  `mg_id` int DEFAULT NULL COMMENT '设备商品ID快照',
  `trade_no` varchar(64) DEFAULT NULL COMMENT '订单交易号',
  `sp_id` int DEFAULT NULL COMMENT '收款策略ID',
  `m_id` int DEFAULT NULL COMMENT '设备ID',
  `machine_id` varchar(50) DEFAULT NULL COMMENT '设备编号',
  `machine_name` varchar(200) DEFAULT NULL COMMENT '设备名称',
  `order_amount` decimal(12,2) DEFAULT 0.00 COMMENT '订单金额',
  `sod_amount` decimal(12,2) DEFAULT 0.00 COMMENT '子订单单价',
  `sod_quantity` int DEFAULT 0 COMMENT '子订单数量',
  `sod_total_price` decimal(12,2) DEFAULT 0.00 COMMENT '子订单金额',
  `rule_mode` tinyint(1) DEFAULT 0 COMMENT '分账模式：1普通，2设备出租，3设备分账，4设备商品分账',
  `rr_id` int DEFAULT NULL COMMENT '分账策略组ID',
  `rri_id` int DEFAULT NULL COMMENT '分账策略明细ID',
  `rrit_id` int DEFAULT NULL COMMENT '阶梯分账明细ID',
  `payer_ao_id` int DEFAULT NULL COMMENT '收款/代收组织ID',
  `receiver_ao_id` int DEFAULT NULL COMMENT '分账接收组织ID',
  `ra_id` int DEFAULT NULL COMMENT '分账账户ID',
  `manager_id` int DEFAULT NULL COMMENT '账户管理人ID',
  `manager_name` varchar(100) DEFAULT NULL COMMENT '账户管理人名称快照',
  `account_type` varchar(50) DEFAULT NULL COMMENT '账户类型快照',
  `account` varchar(255) DEFAULT NULL COMMENT '分账账户快照',
  `calc_type` tinyint(1) DEFAULT 1 COMMENT '计算方式：1百分比，2固定金额，3全额，4阶梯百分比',
  `income_value` decimal(10,3) DEFAULT 0.000 COMMENT '分账比例或固定值',
  `income_amount` decimal(12,2) DEFAULT 0.00 COMMENT '应分账金额',
  `refund_amount` decimal(12,2) DEFAULT 0.00 COMMENT '已退分账金额',
  `period_key` varchar(20) DEFAULT NULL COMMENT '统计周期，如2026-06',
  `period_amount_before` decimal(12,2) DEFAULT 0.00 COMMENT '本单前周期累计营业额',
  `period_amount_after` decimal(12,2) DEFAULT 0.00 COMMENT '本单后周期累计营业额',
  `source` varchar(50) DEFAULT NULL COMMENT '来源：normal/rental/device_rule/tier/product_rule',
  `settlement_type` tinyint(1) DEFAULT 1 COMMENT '分账时间类型快照：1即时分账，2 T+N分账',
  `settlement_days` int DEFAULT 0 COMMENT 'T+N分账天数快照',
  `planned_revenue_time` int DEFAULT NULL COMMENT '计划结算时间',
  `status` tinyint(1) DEFAULT 0 COMMENT '状态：0待支付，1已结算，2待结算，3失败，4已取消',
  `revenue_time` int DEFAULT NULL COMMENT '结算时间',
  `create_time` int DEFAULT NULL,
  `update_time` int DEFAULT NULL,
  PRIMARY KEY (`ro_id`),
  KEY `idx_order` (`order_id`, `sod_id`),
  KEY `idx_trade_no` (`trade_no`),
  KEY `idx_machine_period` (`m_id`, `period_key`),
  KEY `idx_machine_goods` (`m_id`, `g_id`),
  KEY `idx_manager_status` (`manager_id`, `status`),
  KEY `idx_receiver_status` (`receiver_ao_id`, `status`),
  KEY `idx_planned_revenue` (`status`, `planned_revenue_time`),
  KEY `idx_rule_mode` (`rule_mode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='新分账订单表';

-- 可选初始化：从管理员账号资料创建余额型分账账户。该步骤只初始化新账户表，不迁移旧分账策略和旧分账订单。
-- INSERT INTO revenue_account (ao_id, manager_id, account_name, account, account_type, bill_account, status, creator, create_time, update_time)
-- SELECT ao_id, manager_id, CONCAT(nickname, '-默认分账账户'), bill_account, 'balance', bill_account, 1, creator, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
-- FROM auth_manager
-- WHERE bill_account IS NOT NULL AND bill_account <> ''
--   AND NOT EXISTS (
--     SELECT 1 FROM revenue_account ra WHERE ra.manager_id = auth_manager.manager_id AND ra.account_type = 'balance'
--   );

-- 可选初始化：配置需要参与新分账的收款渠道。未配置或停用的渠道不会生成 revenue_order。
-- INSERT INTO revenue_pay_channel (pay_type, payee_type, channel_name, status, creator, create_time, update_time)
-- VALUES
-- (1, 1, '微信支付', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
-- (2, 2, '支付宝支付', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
-- (4, 4, '京东收银', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
-- (20, 20, '余额支付', 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
