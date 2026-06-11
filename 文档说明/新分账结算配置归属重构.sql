-- 新分账结算配置归属重构
-- 目标：
-- 1. revenue_pay_channel 只控制哪些支付渠道允许触发新分账。
-- 2. revenue_payee_config 按具体收款策略配置即时分账或 T+N。
-- 3. revenue_order 继续保存结算配置快照，历史订单不受后续配置修改影响。
-- 发布顺序：先执行本脚本，再部署新代码；验证通过后执行《新分账渠道旧结算字段清理.sql》。
-- 适用范围：revenue_pay_channel 已存在 settlement_type/settlement_days，
-- 且 revenue_payee_config 尚不存在这两个字段的数据库。请勿与《新分账结算时间数据库变更.sql》重复执行。

ALTER TABLE `revenue_payee_config`
  ADD COLUMN `settlement_type` tinyint(1) DEFAULT 1 COMMENT '分账时间类型：1即时分账，2 T+N分账' AFTER `enable_revenue`,
  ADD COLUMN `settlement_days` int DEFAULT 0 COMMENT 'T+N分账天数，即时分账为0' AFTER `settlement_type`;

-- 将原渠道级结算配置回填到该渠道下已存在的收款策略新分账配置。
-- pay_type 精确匹配优先；没有精确匹配时兼容历史 payee_type，按最新配置取值。
UPDATE `revenue_payee_config` rpcfg
SET rpcfg.settlement_type = IFNULL((
      SELECT rpc.settlement_type
      FROM revenue_pay_channel rpc
      WHERE rpc.pay_type = rpcfg.payee_type
         OR (rpc.payee_type IS NOT NULL AND rpc.payee_type = rpcfg.payee_type)
      ORDER BY (rpc.pay_type = rpcfg.payee_type) DESC, rpc.rpc_id DESC
      LIMIT 1
    ), 1),
    rpcfg.settlement_days = IF(IFNULL((
      SELECT rpc.settlement_type
      FROM revenue_pay_channel rpc
      WHERE rpc.pay_type = rpcfg.payee_type
         OR (rpc.payee_type IS NOT NULL AND rpc.payee_type = rpcfg.payee_type)
      ORDER BY (rpc.pay_type = rpcfg.payee_type) DESC, rpc.rpc_id DESC
      LIMIT 1
    ), 1) = 2, GREATEST(IFNULL((
      SELECT rpc.settlement_days
      FROM revenue_pay_channel rpc
      WHERE rpc.pay_type = rpcfg.payee_type
         OR (rpc.payee_type IS NOT NULL AND rpc.payee_type = rpcfg.payee_type)
      ORDER BY (rpc.pay_type = rpcfg.payee_type) DESC, rpc.rpc_id DESC
      LIMIT 1
    ), 1), 1), 0),
    rpcfg.update_time = UNIX_TIMESTAMP();

UPDATE `revenue_payee_config`
SET `settlement_type` = 1,
    `settlement_days` = 0
WHERE `settlement_type` IS NULL
   OR `settlement_type` NOT IN (1, 2)
   OR `settlement_days` IS NULL
   OR `settlement_days` < 0
   OR (`settlement_type` = 1 AND `settlement_days` <> 0)
   OR (`settlement_type` = 2 AND `settlement_days` < 1);

-- 此阶段暂时保留 revenue_pay_channel 的旧结算字段，兼容仍在运行的旧版本代码。
