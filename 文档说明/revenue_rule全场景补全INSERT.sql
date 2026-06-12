INSERT INTO kiosk.revenue_account
(ra_id, ao_id, manager_id, account_name, account, account_type, bill_account, status, creator, create_time, update_time)
VALUES(2, 1, 63, '2号分账账号', '22334455', 'balance', 'A_BALANCE', 1, 1, 1780971556, 1780971556);
INSERT INTO kiosk.revenue_account
(ra_id, ao_id, manager_id, account_name, account, account_type, bill_account, status, creator, create_time, update_time)
VALUES(3, 1, 64, '3号分账账号', '22334455', 'balance', 'A_BALANCE', 1, 1, 1780971556, 1780971556);
INSERT INTO kiosk.revenue_account
(ra_id, ao_id, manager_id, account_name, account, account_type, bill_account, status, creator, create_time, update_time)
VALUES(4, 1, 62, '3号分账账号', '123123', 'balance', 'C_BALANCE', 1, 1, 1780986598, 1780986598);
INSERT INTO kiosk.revenue_account
(ra_id, ao_id, manager_id, account_name, account, account_type, bill_account, status, creator, create_time, update_time)
VALUES(5, 1, 62, '3号分账账号', '123123', 'balance', 'C_BALANCE', 1, 1, 1781065593, 1781065593);
INSERT INTO kiosk.revenue_account
(ra_id, ao_id, manager_id, account_name, account, account_type, bill_account, status, creator, create_time, update_time)
VALUES(6, 35, 59, '测试', '123', 'balance', '456', 1, 1, 1781072905, 1781072905);

INSERT INTO kiosk.revenue_order
(ro_id, order_id, sod_id, trade_no, sp_id, m_id, machine_id, machine_name, order_amount, sod_amount, sod_quantity, sod_total_price, rule_mode, rr_id, rri_id, rrit_id, payer_ao_id, receiver_ao_id, ra_id, manager_id, manager_name, account_type, account, calc_type, income_value, income_amount, refund_amount, period_key, period_amount_before, period_amount_after, source, planned_revenue_time, settlement_days, settlement_type, status, revenue_time, create_time, update_time)
VALUES(1, 31823, NULL, '20260610121054407571736', 5, 407, 'JCHM-H2D-0311', 'JCHM-H2D-0311', 0.01, 0.00, 0, 0.00, 1, NULL, NULL, NULL, 17, 1, 1, 62, '分账账号1', 'balance', '112233', 3, 100.000, 0.01, 0.00, NULL, 0.00, 0.00, 'normal', NULL, 0, 1, 1, 1781064663, 1781064655, 1781064663);


INSERT INTO kiosk.revenue_pay_channel
(rpc_id, pay_type, payee_type, channel_name, status, creator, create_time, update_time)
VALUES(2, 11, 1, '微信掃碼', 1, 1, 1781089614, 1781089614);
INSERT INTO kiosk.revenue_pay_channel
(rpc_id, pay_type, payee_type, channel_name, status, creator, create_time, update_time)
VALUES(3, 12, 1, '微信反掃', 1, 1, 1781089614, 1781089614);
INSERT INTO kiosk.revenue_pay_channel
(rpc_id, pay_type, payee_type, channel_name, status, creator, create_time, update_time)
VALUES(4, 22, 2, '支付宝反掃', 1, 1, 1781089614, 1781089614);
INSERT INTO kiosk.revenue_pay_channel
(rpc_id, pay_type, payee_type, channel_name, status, creator, create_time, update_time)
VALUES(5, 21, 2, '支付宝掃碼', 1, 1, 1781089614, 1781089614);
INSERT INTO kiosk.revenue_pay_channel
(rpc_id, pay_type, payee_type, channel_name, status, creator, create_time, update_time)
VALUES(6, 4, 4, '京东收银', 1, 1, 1781089614, 1781089614);



INSERT INTO kiosk.revenue_rule
(rr_id, rule_name, rule_mode, base_type, turnover_type, tier_calc_mode, settlement_type, settlement_days, status, creator, create_time, update_time)
VALUES(1, '设备固定比例分账-更新', 2, 1, 1, 1, 1, 0, 1, 0, 1780923909, 1781074946);
INSERT INTO kiosk.revenue_rule
(rr_id, rule_name, rule_mode, base_type, turnover_type, tier_calc_mode, settlement_type, settlement_days, status, creator, create_time, update_time)
VALUES(2, 'REVNEW_JCHM_H2D_0064_设备固定比例_B20_C30', 3, 1, 1, 1, 1, 0, 2, 0, 1780923909, 1780923909);
INSERT INTO kiosk.revenue_rule
(rr_id, rule_name, rule_mode, base_type, turnover_type, tier_calc_mode, settlement_type, settlement_days, status, creator, create_time, update_time)
VALUES(3, 'REVNEW_JCHM_H2D_0064_设备阶梯_A_B', 3, 1, 1, 1, 1, 0, 2, 0, 1780923909, 1780923909);
INSERT INTO kiosk.revenue_rule
(rr_id, rule_name, rule_mode, base_type, turnover_type, tier_calc_mode, settlement_type, settlement_days, status, creator, create_time, update_time)
VALUES(4, '普通订单分账', 1, 1, 1, 1, 1, 0, 2, 1, 1780978464, 1780978464);
INSERT INTO kiosk.revenue_rule
(rr_id, rule_name, rule_mode, base_type, turnover_type, tier_calc_mode, settlement_type, settlement_days, status, creator, create_time, update_time)
VALUES(5, '普通订单分账', 1, 1, 1, 1, 1, 0, 2, 1, 1780984793, 1780984793);
INSERT INTO kiosk.revenue_rule
(rr_id, rule_name, rule_mode, base_type, turnover_type, tier_calc_mode, settlement_type, settlement_days, status, creator, create_time, update_time)
VALUES(6, '设备出租方全额分账', 2, 1, 1, 1, 1, 0, 2, 1, 1780984852, 1780984852);
INSERT INTO kiosk.revenue_rule
(rr_id, rule_name, rule_mode, base_type, turnover_type, tier_calc_mode, settlement_type, settlement_days, status, creator, create_time, update_time)
VALUES(7, '设备固定比例分账', 3, 1, 1, 1, 1, 0, 2, 1, 1780984869, 1780984869);
INSERT INTO kiosk.revenue_rule
(rr_id, rule_name, rule_mode, base_type, turnover_type, tier_calc_mode, settlement_type, settlement_days, status, creator, create_time, update_time)
VALUES(8, '设备月营业额阶梯分账', 3, 1, 1, 1, 1, 0, 2, 1, 1780984883, 1780984883);
INSERT INTO kiosk.revenue_rule
(rr_id, rule_name, rule_mode, base_type, turnover_type, tier_calc_mode, settlement_type, settlement_days, status, creator, create_time, update_time)
VALUES(9, '设备月营业额阶梯分账', 3, 1, 1, 1, 1, 0, 2, 1, 1781074907, 1781074907);



INSERT INTO kiosk.revenue_rule_item
(rri_id, rr_id, receiver_ao_id, ra_id, manager_id, calc_type, calc_value, sort, status, create_time, update_time)
VALUES(1, 1, 1, 1, 62, 1, 20.000, 1, 1, 1780990164, 1780990164);



INSERT INTO kiosk.revenue_rule_item_tier
(rrit_id, rri_id, threshold_min, threshold_max, calc_value, sort, status, create_time, update_time)
VALUES(1, 3, 0.00, 5000.00, 10.000, 1, 1, 1780990177, 1780991494);
INSERT INTO kiosk.revenue_rule_item_tier
(rrit_id, rri_id, threshold_min, threshold_max, calc_value, sort, status, create_time, update_time)
VALUES(2, 3, 5000.00, 8000.00, 20.000, 2, 1, 1780991494, 1780991494);
INSERT INTO kiosk.revenue_rule_item_tier
(rrit_id, rri_id, threshold_min, threshold_max, calc_value, sort, status, create_time, update_time)
VALUES(3, 3, 8000.00, NULL, 30.000, 3, 1, 1780991511, 1780991511);


INSERT INTO kiosk.revenue_rule
(rr_id, rule_name, rule_mode, base_type, turnover_type, tier_calc_mode, settlement_type, settlement_days, status, creator, create_time, update_time)
VALUES(900001, 'JCHM-H2D-0064_普通全额即时分账', 1, 1, 1, 1, 1, 0, 1, 1, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule
(rr_id, rule_name, rule_mode, base_type, turnover_type, tier_calc_mode, settlement_type, settlement_days, status, creator, create_time, update_time)
VALUES(900002, 'JCHM-H2D-0064_普通比例A40_B60_T1', 1, 1, 1, 1, 2, 1, 1, 1, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule
(rr_id, rule_name, rule_mode, base_type, turnover_type, tier_calc_mode, settlement_type, settlement_days, status, creator, create_time, update_time)
VALUES(900003, 'JCHM-H2D-0064_设备出租组织35全额分账', 2, 1, 1, 1, 1, 0, 1, 1, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule
(rr_id, rule_name, rule_mode, base_type, turnover_type, tier_calc_mode, settlement_type, settlement_days, status, creator, create_time, update_time)
VALUES(900004, 'JCHM-H2D-0064_设备固定比例A20_B30', 3, 1, 1, 1, 1, 0, 1, 1, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule
(rr_id, rule_name, rule_mode, base_type, turnover_type, tier_calc_mode, settlement_type, settlement_days, status, creator, create_time, update_time)
VALUES(900005, 'JCHM-H2D-0064_设备月营业额阶梯', 3, 1, 1, 1, 1, 0, 1, 1, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule
(rr_id, rule_name, rule_mode, base_type, turnover_type, tier_calc_mode, settlement_type, settlement_days, status, creator, create_time, update_time)
VALUES(900006, 'JCHM-H2D-0064_设备商品比例10', 4, 1, 1, 1, 1, 0, 1, 1, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule
(rr_id, rule_name, rule_mode, base_type, turnover_type, tier_calc_mode, settlement_type, settlement_days, status, creator, create_time, update_time)
VALUES(900007, 'JCHM-H2D-0064_设备商品每件3元_T1', 4, 1, 1, 1, 2, 1, 1, 1, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule_item
(rri_id, rr_id, g_id, receiver_ao_id, ra_id, manager_id, calc_type, calc_value, sort, status, create_time, update_time)
VALUES(900001, 900001, NULL, 1, 2, 63, 3, 100.000, 1, 1, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule_item
(rri_id, rr_id, g_id, receiver_ao_id, ra_id, manager_id, calc_type, calc_value, sort, status, create_time, update_time)
VALUES(900002, 900002, NULL, 1, 2, 63, 1, 40.000, 1, 1, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule_item
(rri_id, rr_id, g_id, receiver_ao_id, ra_id, manager_id, calc_type, calc_value, sort, status, create_time, update_time)
VALUES(900003, 900002, NULL, 1, 3, 64, 1, 60.000, 2, 1, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule_item
(rri_id, rr_id, g_id, receiver_ao_id, ra_id, manager_id, calc_type, calc_value, sort, status, create_time, update_time)
VALUES(900004, 900003, NULL, 35, 6, 59, 1, 100.000, 1, 1, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule_item
(rri_id, rr_id, g_id, receiver_ao_id, ra_id, manager_id, calc_type, calc_value, sort, status, create_time, update_time)
VALUES(900005, 900004, NULL, 1, 2, 63, 1, 20.000, 1, 1, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule_item
(rri_id, rr_id, g_id, receiver_ao_id, ra_id, manager_id, calc_type, calc_value, sort, status, create_time, update_time)
VALUES(900006, 900004, NULL, 1, 3, 64, 1, 30.000, 2, 1, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule_item
(rri_id, rr_id, g_id, receiver_ao_id, ra_id, manager_id, calc_type, calc_value, sort, status, create_time, update_time)
VALUES(900007, 900005, NULL, 1, 4, 62, 4, 0.000, 1, 1, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule_item
(rri_id, rr_id, g_id, receiver_ao_id, ra_id, manager_id, calc_type, calc_value, sort, status, create_time, update_time)
VALUES(900008, 900006, 123, 1, 2, 63, 1, 10.000, 1, 1, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule_item
(rri_id, rr_id, g_id, receiver_ao_id, ra_id, manager_id, calc_type, calc_value, sort, status, create_time, update_time)
VALUES(900009, 900007, 123, 1, 3, 64, 2, 3.000, 1, 1, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule_item_tier
(rrit_id, rri_id, threshold_min, threshold_max, calc_value, sort, status, create_time, update_time)
VALUES(900001, 900007, 0.00, 5000.00, 10.000, 1, 1, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule_item_tier
(rrit_id, rri_id, threshold_min, threshold_max, calc_value, sort, status, create_time, update_time)
VALUES(900002, 900007, 5000.00, 8000.00, 20.000, 2, 1, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule_item_tier
(rrit_id, rri_id, threshold_min, threshold_max, calc_value, sort, status, create_time, update_time)
VALUES(900003, 900007, 8000.00, NULL, 30.000, 3, 1, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule_machine
(rrm_id, rr_id, m_id, ao_id, sort, status, create_time, update_time)
VALUES(900001, 900001, 127, 1, 1, 1, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule_machine
(rrm_id, rr_id, m_id, ao_id, sort, status, create_time, update_time)
VALUES(900002, 900002, 127, 1, 2, 2, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule_machine
(rrm_id, rr_id, m_id, ao_id, sort, status, create_time, update_time)
VALUES(900003, 900003, 127, 1, 1, 1, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule_machine
(rrm_id, rr_id, m_id, ao_id, sort, status, create_time, update_time)
VALUES(900004, 900004, 127, 1, 1, 1, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule_machine
(rrm_id, rr_id, m_id, ao_id, sort, status, create_time, update_time)
VALUES(900005, 900005, 127, 1, 2, 2, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule_machine
(rrm_id, rr_id, m_id, ao_id, sort, status, create_time, update_time)
VALUES(900006, 900006, 127, 1, 1, 1, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule_machine
(rrm_id, rr_id, m_id, ao_id, sort, status, create_time, update_time)
VALUES(900007, 900007, 127, 1, 2, 2, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule
(rr_id, rule_name, rule_mode, base_type, turnover_type, tier_calc_mode, settlement_type, settlement_days, status, creator, create_time, update_time)
VALUES(900008, 'JCHM-H2D-0064_设备出租固定金额5元', 2, 1, 1, 1, 1, 0, 1, 1, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule
(rr_id, rule_name, rule_mode, base_type, turnover_type, tier_calc_mode, settlement_type, settlement_days, status, creator, create_time, update_time)
VALUES(900009, 'JCHM-H2D-0064_设备出租全额', 2, 1, 1, 1, 1, 0, 1, 1, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule
(rr_id, rule_name, rule_mode, base_type, turnover_type, tier_calc_mode, settlement_type, settlement_days, status, creator, create_time, update_time)
VALUES(900010, 'JCHM-H2D-0064_设备固定金额5元', 3, 1, 1, 1, 1, 0, 1, 1, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule
(rr_id, rule_name, rule_mode, base_type, turnover_type, tier_calc_mode, settlement_type, settlement_days, status, creator, create_time, update_time)
VALUES(900011, 'JCHM-H2D-0064_设备全额分账', 3, 1, 1, 1, 1, 0, 1, 1, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule
(rr_id, rule_name, rule_mode, base_type, turnover_type, tier_calc_mode, settlement_type, settlement_days, status, creator, create_time, update_time)
VALUES(900012, 'JCHM-H2D-0064_设备分账扣除出租基数', 3, 2, 1, 1, 1, 0, 1, 1, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule
(rr_id, rule_name, rule_mode, base_type, turnover_type, tier_calc_mode, settlement_type, settlement_days, status, creator, create_time, update_time)
VALUES(900013, 'JCHM-H2D-0064_设备阶梯支付成功金额口径', 3, 1, 2, 1, 1, 0, 1, 1, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule
(rr_id, rule_name, rule_mode, base_type, turnover_type, tier_calc_mode, settlement_type, settlement_days, status, creator, create_time, update_time)
VALUES(900014, 'JCHM-H2D-0064_设备跨阶梯拆分', 3, 1, 1, 2, 1, 0, 1, 1, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule_item
(rri_id, rr_id, g_id, receiver_ao_id, ra_id, manager_id, calc_type, calc_value, sort, status, create_time, update_time)
VALUES(900010, 900008, NULL, 35, 6, 59, 2, 5.000, 1, 1, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule_item
(rri_id, rr_id, g_id, receiver_ao_id, ra_id, manager_id, calc_type, calc_value, sort, status, create_time, update_time)
VALUES(900011, 900009, NULL, 35, 6, 59, 3, 100.000, 1, 1, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule_item
(rri_id, rr_id, g_id, receiver_ao_id, ra_id, manager_id, calc_type, calc_value, sort, status, create_time, update_time)
VALUES(900012, 900010, NULL, 1, 2, 63, 2, 5.000, 1, 1, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule_item
(rri_id, rr_id, g_id, receiver_ao_id, ra_id, manager_id, calc_type, calc_value, sort, status, create_time, update_time)
VALUES(900013, 900011, NULL, 1, 2, 63, 3, 100.000, 1, 1, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule_item
(rri_id, rr_id, g_id, receiver_ao_id, ra_id, manager_id, calc_type, calc_value, sort, status, create_time, update_time)
VALUES(900014, 900012, NULL, 1, 2, 63, 3, 100.000, 1, 1, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule_item
(rri_id, rr_id, g_id, receiver_ao_id, ra_id, manager_id, calc_type, calc_value, sort, status, create_time, update_time)
VALUES(900015, 900013, NULL, 1, 4, 62, 4, 0.000, 1, 1, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule_item
(rri_id, rr_id, g_id, receiver_ao_id, ra_id, manager_id, calc_type, calc_value, sort, status, create_time, update_time)
VALUES(900016, 900014, NULL, 1, 4, 62, 4, 0.000, 1, 1, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule_item_tier
(rrit_id, rri_id, threshold_min, threshold_max, calc_value, sort, status, create_time, update_time)
VALUES(900004, 900015, 0.00, 5000.00, 10.000, 1, 1, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule_item_tier
(rrit_id, rri_id, threshold_min, threshold_max, calc_value, sort, status, create_time, update_time)
VALUES(900005, 900015, 5000.00, 8000.00, 20.000, 2, 1, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule_item_tier
(rrit_id, rri_id, threshold_min, threshold_max, calc_value, sort, status, create_time, update_time)
VALUES(900006, 900015, 8000.00, NULL, 30.000, 3, 1, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule_item_tier
(rrit_id, rri_id, threshold_min, threshold_max, calc_value, sort, status, create_time, update_time)
VALUES(900007, 900016, 0.00, 5000.00, 10.000, 1, 1, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule_item_tier
(rrit_id, rri_id, threshold_min, threshold_max, calc_value, sort, status, create_time, update_time)
VALUES(900008, 900016, 5000.00, 8000.00, 20.000, 2, 1, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule_item_tier
(rrit_id, rri_id, threshold_min, threshold_max, calc_value, sort, status, create_time, update_time)
VALUES(900009, 900016, 8000.00, NULL, 30.000, 3, 1, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule_machine
(rrm_id, rr_id, m_id, ao_id, sort, status, create_time, update_time)
VALUES(900008, 900008, 127, 1, 3, 2, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule_machine
(rrm_id, rr_id, m_id, ao_id, sort, status, create_time, update_time)
VALUES(900009, 900009, 127, 1, 4, 2, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule_machine
(rrm_id, rr_id, m_id, ao_id, sort, status, create_time, update_time)
VALUES(900010, 900010, 127, 1, 3, 2, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule_machine
(rrm_id, rr_id, m_id, ao_id, sort, status, create_time, update_time)
VALUES(900011, 900011, 127, 1, 4, 2, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule_machine
(rrm_id, rr_id, m_id, ao_id, sort, status, create_time, update_time)
VALUES(900012, 900012, 127, 1, 5, 2, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule_machine
(rrm_id, rr_id, m_id, ao_id, sort, status, create_time, update_time)
VALUES(900013, 900013, 127, 1, 6, 2, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule_machine
(rrm_id, rr_id, m_id, ao_id, sort, status, create_time, update_time)
VALUES(900014, 900014, 127, 1, 7, 2, 1781136000, 1781136000);

INSERT INTO kiosk.revenue_rule_item
(rri_id, rr_id, g_id, receiver_ao_id, ra_id, manager_id, calc_type, calc_value, sort, status, create_time, update_time)
VALUES(3, 3, NULL, 1, 4, 62, 4, 0.000, 1, 1, 1781136000, 1781136000);
