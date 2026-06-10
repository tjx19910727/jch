INSERT INTO auth_manager
(manager_id, ao_id, nickname, account, password, pid, `level`, sex, pic, user_id, wx_id, openid, email, wx_notice, email_notice, balance, frozen, withdrawal, bill_account, real_name, status, audit_status, creator, create_time, update_id, update_time, query_start_time, query_start_urls)
VALUES(62, 1, '分账账号1', 'fz1', '77f82101fc53b2e8c4e14ed409757541', 1, 2, 1, '', 0, 0, NULL, NULL, '', '', 0.000, 0.000, 0.000, 'A_BALANCE', '分账账号1', 1, 0, 1, 1780965322, NULL, 1780965322, 0, NULL);

INSERT INTO auth_manager
(manager_id, ao_id, nickname, account, password, pid, `level`, sex, pic, user_id, wx_id, openid, email, wx_notice, email_notice, balance, frozen, withdrawal, bill_account, real_name, status, audit_status, creator, create_time, update_id, update_time, query_start_time, query_start_urls)
VALUES(63, 1, '分账账号2', 'fz2', '77f82101fc53b2e8c4e14ed409757541', 1, 2, 1, '', 0, 0, NULL, NULL, '', '', 0.000, 0.000, 0.000, 'B_BALANCE', '分账账号2', 1, 0, 1, 1780965366, NULL, 1780965366, 0, NULL);

INSERT INTO auth_manager
(manager_id, ao_id, nickname, account, password, pid, `level`, sex, pic, user_id, wx_id, openid, email, wx_notice, email_notice, balance, frozen, withdrawal, bill_account, real_name, status, audit_status, creator, create_time, update_id, update_time, query_start_time, query_start_urls)
VALUES(64, 1, '分账账号3', 'fz3', '77f82101fc53b2e8c4e14ed409757541', 1, 2, 1, '', 0, 0, NULL, NULL, '', '', 0.000, 0.000, 0.000, 'C_BALANCE', '分账账号3', 1, 0, 1, 1780965384, NULL, 1780965384, 0, NULL);

INSERT INTO revenue_account
(ra_id, ao_id, manager_id, account_name, account, account_type, bill_account, status, creator, create_time, update_time)
VALUES(1, 1, 62, '1号分账账号', '112233', 'balance', 'A_BALANCE', 1, 1, 1780970965, 1780971339);

INSERT INTO revenue_account
(ra_id, ao_id, manager_id, account_name, account, account_type, bill_account, status, creator, create_time, update_time)
VALUES(2, 1, 63, '2号分账账号', '22334455', 'balance', 'B_BALANCE', 1, 1, 1780971556, 1780971556);

INSERT INTO revenue_account
(ra_id, ao_id, manager_id, account_name, account, account_type, bill_account, status, creator, create_time, update_time)
VALUES(3, 1, 64, '3号分账账号', '33445566', 'balance', 'C_BALANCE', 1, 1, 1780971556, 1780971556);

INSERT INTO revenue_pay_channel
(rpc_id, pay_type, payee_type, channel_name, status, creator, create_time, update_time)
VALUES(1, 1, 1, '微信支付', 1, 1, 1780975448, 1780975594);

INSERT INTO revenue_payee_config
(rpcfg_id, sp_id, payee_type, ao_id, default_ra_id, default_manager_id, enable_revenue, settlement_type, settlement_days, status, creator, create_time, update_time)
VALUES(1, 5, 1, 1, 1, 62, 1, 1, 0, 1, 1, 1780990272, 1780990272);

INSERT INTO revenue_rule
(rr_id, rule_name, rule_mode, payer_ao_id, base_type, turnover_type, tier_calc_mode, status, creator, create_time, update_time)
VALUES(6, '设备出租方全额分账', 2, 1, 1, 1, 1, 1, 1, 1780984852, 1780984852);

INSERT INTO revenue_rule
(rr_id, rule_name, rule_mode, payer_ao_id, base_type, turnover_type, tier_calc_mode, status, creator, create_time, update_time)
VALUES(7, '设备固定比例分账-B20-C30', 3, 1, 1, 1, 1, 1, 1, 1780984869, 1780984869);

INSERT INTO revenue_rule
(rr_id, rule_name, rule_mode, payer_ao_id, base_type, turnover_type, tier_calc_mode, status, creator, create_time, update_time)
VALUES(8, '设备月营业额阶梯分账-A', 3, 1, 1, 1, 1, 1, 1, 1780984883, 1780984883);

INSERT INTO revenue_rule_item
(rri_id, rr_id, receiver_ao_id, ra_id, manager_id, calc_type, calc_value, sort, status, create_time, update_time)
VALUES(1, 6, 1, 1, 62, 3, 100.000, 1, 1, 1780990164, 1780990164);

INSERT INTO revenue_rule_item
(rri_id, rr_id, receiver_ao_id, ra_id, manager_id, calc_type, calc_value, sort, status, create_time, update_time)
VALUES(2, 7, 1, 2, 63, 1, 20.000, 1, 1, 1780990164, 1780990164);

INSERT INTO revenue_rule_item
(rri_id, rr_id, receiver_ao_id, ra_id, manager_id, calc_type, calc_value, sort, status, create_time, update_time)
VALUES(3, 7, 1, 3, 64, 1, 30.000, 2, 1, 1780990164, 1780990164);

INSERT INTO revenue_rule_item
(rri_id, rr_id, receiver_ao_id, ra_id, manager_id, calc_type, calc_value, sort, status, create_time, update_time)
VALUES(4, 8, 1, 1, 62, 4, 0.000, 1, 1, 1780990164, 1780990164);

INSERT INTO revenue_rule_item_tier
(rrit_id, rri_id, threshold_min, threshold_max, calc_value, sort, status, create_time, update_time)
VALUES(1, 4, 0.00, 5000.00, 10.000, 1, 1, 1780990177, 1780991494);

INSERT INTO revenue_rule_item_tier
(rrit_id, rri_id, threshold_min, threshold_max, calc_value, sort, status, create_time, update_time)
VALUES(2, 4, 5000.00, 8000.00, 20.000, 2, 1, 1780991494, 1780991494);

INSERT INTO revenue_rule_item_tier
(rrit_id, rri_id, threshold_min, threshold_max, calc_value, sort, status, create_time, update_time)
VALUES(3, 4, 8000.00, NULL, 30.000, 3, 1, 1780991511, 1780991511);

INSERT INTO revenue_rule_machine
(rr_id, m_id, ao_id, sort, status, create_time, update_time)
VALUES(7, 127, 1, 10, 1, 1780991511, 1780991511);
