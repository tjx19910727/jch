-- 设备商品差异化分账数据库变更
-- calc_type=1：按订单商品明细总金额比例分账
-- calc_type=2：按每件商品固定金额分账，实际金额 = calc_value * quantity
-- rule_mode=4：设备商品分账

ALTER TABLE `revenue_rule_item`
  ADD COLUMN `g_id` int DEFAULT NULL COMMENT '商品ID，设备商品分账模式必填' AFTER `rr_id`,
  ADD INDEX `idx_rule_goods_status` (`rr_id`, `g_id`, `status`);

ALTER TABLE `revenue_order`
  ADD COLUMN `g_id` int DEFAULT NULL COMMENT '商品ID快照' AFTER `sod_id`,
  ADD COLUMN `mg_id` int DEFAULT NULL COMMENT '设备商品ID快照' AFTER `g_id`,
  ADD INDEX `idx_machine_goods` (`m_id`, `g_id`);

INSERT INTO auth_node
  (pid, name, url, `desc`, sort, type, is_auth, is_button, data_auth, status, create_time, update_time)
SELECT pid, '新增设备商品分账明细', '/management/revenue.revenue_rule/addProductItem',
       '新增按设备和商品区分的比例或按件金额分账明细',
       sort, type, is_auth, is_button, data_auth, status, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM auth_node
WHERE url = '/management/revenue.revenue_rule/addItem'
  AND NOT EXISTS (
    SELECT 1 FROM auth_node WHERE url = '/management/revenue.revenue_rule/addProductItem'
  )
LIMIT 1;
