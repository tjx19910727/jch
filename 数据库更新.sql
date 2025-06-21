

# 20250621
ALTER TABLE `sale_orders_refund`
ADD INDEX `status` (`status`) USING BTREE ,
ADD INDEX `create_time` (`create_time`) USING BTREE ,
ADD INDEX `refund_trade_no` (`refund_trade_no`) USING BTREE ,
ADD INDEX `order_id` (`order_id`) USING BTREE ,
ADD INDEX `trade_no` (`trade_no`) USING BTREE ,
ADD INDEX `machine_id` (`machine_id`, `m_id`) USING BTREE ,
ADD INDEX `refund_no` (`refund_no`) USING BTREE ,
ADD INDEX `mc` (`mc_id`, `channel_code`) USING BTREE ;


ALTER TABLE `machine_channel_stock`
ADD INDEX `m_id` (`m_id`) USING BTREE ,
ADD INDEX `machine_id` (`machine_id`) USING BTREE ,
ADD INDEX `g_id` (`g_id`) USING BTREE ,
ADD INDEX `g_name` (`g_name`) USING BTREE ,
ADD INDEX `sku` (`sku`) USING BTREE ,
ADD INDEX `bar_code` (`bar_code`) USING BTREE ,
ADD INDEX `time` (`create_date`, `create_time`) USING BTREE ,
ADD INDEX `ao_id` (`ao_id`) USING BTREE ;

ALTER TABLE `earth_area`
ADD INDEX `country_id` (`country_id`) USING BTREE ;


ALTER TABLE `email_template`
ADD INDEX `ao_id` (`ao_id`) USING BTREE ;

ALTER TABLE `action_video`
ADD INDEX `creator` (`creator`) USING BTREE ,
ADD INDEX `tag` (`tag`) USING BTREE ,
ADD INDEX `create_time` (`create_time`) USING BTREE ,
ADD INDEX `update_id` (`update_id`) USING BTREE ,
ADD INDEX `update_time` (`update_time`) USING BTREE ,
ADD INDEX `ao_id` (`ao_id`) USING BTREE ;

ALTER TABLE `activity_coupon`
ADD INDEX `create_time` (`create_time`, `update_time`) USING BTREE ,
ADD INDEX `creator` (`creator`, `update_id`) USING BTREE ;

ALTER TABLE `activity_coupon_used`
ADD INDEX `time` (`create_time`, `update_time`) USING BTREE ,
ADD INDEX `status` (`status`) USING BTREE ;

ALTER TABLE `activity_fd_used`
ADD INDEX `fd_id` (`fd_id`) USING BTREE ,
ADD INDEX `m_id` (`m_id`) USING BTREE ,
ADD INDEX `machine_id` (`machine_id`) USING BTREE ,
ADD INDEX `fdc_id` (`fdc_id`) USING BTREE ,
ADD INDEX `order_id` (`order_id`) USING BTREE ,
ADD INDEX `trade_no` (`trade_no`) USING BTREE ,
ADD INDEX `fd_type` (`fd_type`) USING BTREE ,
ADD INDEX `g_id` (`g_id`) USING BTREE ,
ADD INDEX `g_name` (`g_name`) USING BTREE ,
ADD INDEX `used_time` (`used_time`) USING BTREE ,
ADD INDEX `create_time` (`create_time`) USING BTREE ;

ALTER TABLE `activity_fd_content`
ADD INDEX `fd_id` (`fd_id`) USING BTREE ,
ADD INDEX `g_id` (`g_id`) USING BTREE ,
ADD INDEX `g_name` (`g_name`) USING BTREE ,
ADD INDEX `gc_id` (`gc_id`) USING BTREE ,
ADD INDEX `gc_name` (`gc_name`) USING BTREE ,
ADD INDEX `sku` (`sku`) USING BTREE ;

ALTER TABLE `activity_goods`
ADD INDEX `a_id` (`a_id`) USING BTREE ,
ADD INDEX `a_type` (`a_type`) USING BTREE ,
ADD INDEX `g_id` (`g_id`) USING BTREE ,
ADD INDEX `sku` (`sku`) USING BTREE ,
ADD INDEX `gc_id` (`gc_id`) USING BTREE ;


ALTER TABLE `activity_lottery`
ADD INDEX `time` (`start_time`, `end_time`) USING BTREE ,
ADD INDEX `status` (`status`) USING BTREE ,
ADD INDEX `create_time` (`create_time`, `update_time`) USING BTREE ,
ADD INDEX `creator` (`creator`, `update_id`) USING BTREE ,
ADD INDEX `lottery_name` (`lottery_name`) USING BTREE ;


ALTER TABLE `activity_lottery_content`
ADD INDEX `al_id` (`al_id`) USING BTREE ,
ADD INDEX `g_id` (`g_id`) USING BTREE ,
ADD INDEX `sku` (`sku`) USING BTREE ;

ALTER TABLE `activity_lottery_used`
ADD INDEX `alc_id` (`alc_id`) USING BTREE ,
ADD INDEX `status` (`status`) USING BTREE ,
ADD INDEX `time` (`create_time`, `update_time`) USING BTREE ;

ALTER TABLE `activity_lottery_used_goods`
ADD INDEX `al_id` (`al_id`, `alu_id`, `alc_id`) USING BTREE ,
ADD INDEX `sod_id` (`sod_id`) USING BTREE ,
ADD INDEX `g_id` (`g_id`) USING BTREE ,
ADD INDEX `sku` (`sku`) USING BTREE ,
ADD INDEX `mc_id` (`mc_id`) USING BTREE ,
ADD INDEX `channel_code` (`channel_code`) USING BTREE ,
ADD INDEX `create_time` (`create_time`) USING BTREE ;

ALTER TABLE `activity_machine`
ADD INDEX `a_id` (`a_id`) USING BTREE ,
ADD INDEX `a_type` (`a_type`) USING BTREE ,
ADD INDEX `m_id` (`m_id`) USING BTREE ,
ADD INDEX `machine_id` (`machine_id`) USING BTREE ;

ALTER TABLE `activity_pick`
ADD INDEX `start_time` (`start_time`) USING BTREE ,
ADD INDEX `end_time` (`end_time`) USING BTREE ,
ADD INDEX `status` (`status`) USING BTREE ,
ADD INDEX `time` (`create_time`, `update_time`) USING BTREE ,
ADD INDEX `creator` (`creator`, `update_id`) USING BTREE ;

ALTER TABLE `activity_pick_code`
ADD INDEX `ap_id` (`ap_id`) USING BTREE ,
ADD INDEX `order_id` (`order_id`) USING BTREE ,
ADD INDEX `trade_no` (`trade_no`) USING BTREE ,
ADD INDEX `m_id` (`m_id`) USING BTREE ,
ADD INDEX `machine_id` (`machine_id`) USING BTREE ,
ADD INDEX `status` (`status`) USING BTREE ,
ADD INDEX `used_time` (`used_time`) USING BTREE ;

ALTER TABLE `advertisement_push`
ADD INDEX `batch_num` (`batch_num`) USING BTREE ,
ADD INDEX `adv_title` (`adv_title`, `res_title`) USING BTREE ,
ADD INDEX `res_id` (`res_id`) USING BTREE ,
ADD INDEX `type` (`type`) USING BTREE ,
ADD INDEX `machine` (`m_id`, `machine_id`) USING BTREE ,
ADD INDEX `date` (`start_date`, `end_date`) USING BTREE ,
ADD INDEX `time` (`start_time`, `end_time`) USING BTREE ,
ADD INDEX `position` (`position`) USING BTREE ,
ADD INDEX `ao_id` (`ao_id`) USING BTREE ,
ADD INDEX `status` (`status`) USING BTREE ,
ADD INDEX `create_time` (`create_time`, `update_time`) USING BTREE ,
ADD INDEX `creator` (`creator`, `update_id`) USING BTREE ;

ALTER TABLE `advertisement_record`
ADD INDEX `adv_id` (`adv_id`, `res_id`) USING BTREE ,
ADD INDEX `title` (`adv_title`, `res_title`) USING BTREE ,
ADD INDEX `machine` (`m_id`, `machine_id`) USING BTREE ,
ADD INDEX `position` (`position`) USING BTREE ,
ADD INDEX `create_time` (`create_time`, `play_time`) USING BTREE ;

ALTER TABLE `api_advance`
ADD INDEX `pick_time` (`pick_time`) USING BTREE ,
ADD INDEX `status` (`status`) USING BTREE ,
ADD INDEX `create_time` (`create_time`) USING BTREE ;

ALTER TABLE `api_callback`
ADD INDEX `aa_id` (`aa_id`) USING BTREE ,
ADD INDEX `create_time` (`create_time`) USING BTREE ;

ALTER TABLE `auth_manager`
ADD INDEX `ao_id` (`ao_id`) USING BTREE ,
ADD INDEX `pid` (`pid`) USING BTREE ,
ADD INDEX `level` (`level`) USING BTREE ,
ADD INDEX `user_id` (`user_id`) USING BTREE ,
ADD INDEX `wx_id` (`wx_id`) USING BTREE ,
ADD INDEX `openid` (`openid`) USING BTREE ,
ADD INDEX `email` (`email`) USING BTREE ,
ADD INDEX `creator` (`creator`, `update_id`) USING BTREE ,
ADD INDEX `time` (`create_time`, `update_time`) USING BTREE ;

ALTER TABLE `auth_manager_log`
ADD INDEX `ao_id` (`ao_id`) USING BTREE ,
ADD INDEX `manager_id` (`manager_id`) USING BTREE ,
ADD INDEX `account` (`account`) USING BTREE ,
ADD INDEX `nickname` (`nickname`) USING BTREE ,
ADD INDEX `create_time` (`create_time`) USING BTREE ;

ALTER TABLE `auth_manager_machine`
ADD INDEX `manager_id` (`manager_id`) USING BTREE ,
ADD INDEX `m_id` (`m_id`) USING BTREE ,
ADD INDEX `machine_id` (`machine_id`) USING BTREE ,
ADD INDEX `account` (`account`) USING BTREE ,
ADD INDEX `create_time` (`create_time`) USING BTREE ;

ALTER TABLE `auth_manager_role`
ADD INDEX `manager_id` (`manager_id`) USING BTREE ,
ADD INDEX `role_id` (`role_id`) USING BTREE ,
ADD INDEX `creator` (`creator`, `update_id`) USING BTREE ,
ADD INDEX `create_time` (`create_time`, `update_time`) USING BTREE ;

ALTER TABLE `auth_node`
ADD INDEX `pid` (`pid`) USING BTREE ,
ADD INDEX `url` (`url`) USING BTREE ,
ADD INDEX `sort` (`sort`) USING BTREE ,
ADD INDEX `type` (`type`) USING BTREE ,
ADD INDEX `is_auth` (`is_auth`) USING BTREE ,
ADD INDEX `is_button` (`is_button`) USING BTREE ,
ADD INDEX `data_auth` (`data_auth`) USING BTREE ,
ADD INDEX `time` (`create_time`, `update_time`) USING BTREE ,
ADD INDEX `status` (`status`) USING BTREE ;

ALTER TABLE `auth_organization`
ADD INDEX `sort` (`sort`) USING BTREE ,
ADD INDEX `time` (`create_time`, `update_time`) USING BTREE ,
ADD INDEX `creator` (`creator`, `create_time`) USING BTREE ;

ALTER TABLE `auth_role_node`
ADD INDEX `role_id` (`role_id`) USING BTREE ,
ADD INDEX `node_id` (`node_id`) USING BTREE ,
ADD INDEX `d_type` (`d_type`) USING BTREE ,
ADD INDEX `is_del` (`is_del`) USING BTREE ,
ADD INDEX `creator` (`creator`, `update_id`) USING BTREE ,
ADD INDEX `create_time` (`create_time`, `update_time`) USING BTREE ;

ALTER TABLE `config_api`
ADD INDEX `auth_name` (`auth_name`) USING BTREE ,
ADD INDEX `ao_id` (`ao_id`) USING BTREE ,
ADD INDEX `create_time` (`create_time`, `update_time`) USING BTREE ,
ADD INDEX `creator` (`creator`, `update_id`) USING BTREE ;

ALTER TABLE `email_template`
ADD INDEX `template_type` (`template_type`) USING BTREE ,
ADD INDEX `ec_id` (`ec_id`) USING BTREE ;

ALTER TABLE `email_template_log`
ADD INDEX `send_id` (`send_id`, `send_email`) USING BTREE ,
ADD INDEX `reply_email` (`reply_email`) USING BTREE ,
ADD INDEX `receive_id` (`receive_email`) USING BTREE ,
ADD INDEX `subject` (`subject`) USING BTREE ,
ADD INDEX `ec_id` (`ec_id`) USING BTREE ,
ADD INDEX `et_id` (`et_id`) USING BTREE ,
ADD INDEX `template_type` (`template_type`) USING BTREE ,
ADD INDEX `ao_id` (`ao_id`) USING BTREE ,
ADD INDEX `create_time` (`create_time`) USING BTREE ;

ALTER TABLE `goods`
ADD INDEX `bar_code` (`bar_code`) USING BTREE ,
ADD INDEX `sku` (`sku`) USING BTREE ,
ADD INDEX `sku2` (`sku2`) USING BTREE ,
ADD INDEX `create_time` (`create_time`, `update_time`) USING BTREE ;

ALTER TABLE `goods_category`
ADD INDEX `gc_pid` (`gc_pid`) USING BTREE ,
ADD INDEX `sort` (`sort`) USING BTREE ,
ADD INDEX `create_time` (`create_time`, `update_time`) USING BTREE ;

ALTER TABLE `goods_change`
KEY `machine` (`m_id`,`machine_id`) USING BTREE,
  KEY `mc_id` (`mc_id`,`channel_code`) USING BTREE,
  KEY `mg_id` (`mg_id`) USING BTREE,
  KEY `g_id` (`g_id`) USING BTREE,
  KEY `gc_id` (`gc_id`) USING BTREE,
  KEY `sku` (`sku`) USING BTREE,
  KEY `bar_code` (`bar_code`) USING BTREE,
  KEY `type` (`type`) USING BTREE,
  KEY `ao_id` (`ao_id`) USING BTREE,
  KEY `create_time` (`create_time`) USING BTREE,
  KEY `creator` (`creator`) USING BTREE

ALTER TABLE `goods_corner`
  ADD INDEX `corner_type` (`corner_type`) USING BTREE ,
  ADD INDEX `time` (`start_time`, `end_time`) USING BTREE ,
  ADD INDEX `status` (`status`) USING BTREE ;

ALTER TABLE `goods_hit`
  ADD INDEX `ao_id` (`ao_id`) USING BTREE ,
  ADD INDEX `sku` (`sku`) USING BTREE ,
  ADD INDEX `gc_id` (`gc_id`) USING BTREE ,
  ADD INDEX `machine` (`m_id`, `machine_id`) USING BTREE ,
  ADD INDEX `create_date` (`create_date`) USING BTREE ,
  ADD INDEX `create_time` (`create_time`) USING BTREE ;


ALTER TABLE `goods_lang`
  DROP INDEX `creator`,
  ADD INDEX `g_id` (`g_id`) USING BTREE ,
  ADD INDEX `lang` (`lang`) USING BTREE ,
  ADD INDEX `creator` (`creator`, `update_id`) USING BTREE ,
  ADD INDEX `create_time` (`create_time`, `update_time`) USING BTREE ;


ALTER TABLE `goods_multiple`
  ADD INDEX `time` (`start_time`, `end_time`) USING BTREE ,
  ADD INDEX `ao_id` (`ao_id`) USING BTREE ,
  ADD INDEX `creator` (`creator`, `update_id`) USING BTREE ,
  ADD INDEX `create_time` (`create_time`, `update_time`) USING BTREE ;


ALTER TABLE `goods_multiple_goods`
  ADD INDEX `gm_id` (`gm_id`) USING BTREE ,
  ADD INDEX `g_id` (`g_id`) USING BTREE ,
  ADD INDEX `sku` (`sku`) USING BTREE ;

ALTER TABLE `goods_multiple_machine`
  ADD INDEX `machine` (`m_id`, `machine_name`) USING BTREE ,
  ADD INDEX `gm_id` (`gm_id`) USING BTREE ;

ALTER TABLE `machine`
  ADD INDEX `device_type` (`device_type`) USING BTREE ,
  ADD INDEX `machine_level` (`machine_level`) USING BTREE ,
  ADD INDEX `lang` (`lang`) USING BTREE ,
  ADD INDEX `online` (`online`) USING BTREE ,
  ADD INDEX `last_online_time` (`last_online_time`) USING BTREE ,
  ADD INDEX `address` (`country_id`, `state_id`, `city_id`, `regions_id`) USING BTREE ,
  ADD INDEX `latlng` (`lat`, `lng`) USING BTREE ,
  ADD INDEX `ao_id` (`ao_id`) USING BTREE ,
  ADD INDEX `manager_id` (`manager_id`) USING BTREE ,
  ADD INDEX `tally_clerk` (`tally_clerk`) USING BTREE ,
  ADD INDEX `config_id` (`config_id`) USING BTREE ,
  ADD INDEX `help_ids` (`help_ids`) USING BTREE ,
  ADD INDEX `current_status` (`current_status`) USING BTREE ,
  ADD INDEX `creator` (`creator`, `update_id`) USING BTREE ,
  ADD INDEX `create_time` (`create_time`, `update_time`) USING BTREE ;

ALTER TABLE `machine_check_stock`
  ADD INDEX `machine` (`m_id`, `machine_id`) USING BTREE ,
  ADD INDEX `mc_id` (`mc_id`, `channel_code`) USING BTREE ,
  ADD INDEX `mg_id` (`mg_id`) USING BTREE ,
  ADD INDEX `g_id` (`g_id`) USING BTREE ,
  ADD INDEX `sku` (`sku`) USING BTREE ,
  ADD INDEX `gc_id` (`gc_id`) USING BTREE ,
  ADD INDEX `creator` (`creator`) USING BTREE ,
  ADD INDEX `create_time` (`create_time`) USING BTREE ,
  ADD INDEX `create_date` (`create_date`) USING BTREE ;

ALTER TABLE `machine_config`
  ADD INDEX `machine` (`m_id`, `machine_id`) USING BTREE ;


ALTER TABLE `machine_config_lang`
  ADD INDEX `machine` (`m_id`, `machine_id`) USING BTREE ,
  ADD INDEX `mc_id` (`mc_id`) USING BTREE ,
  ADD INDEX `lang` (`lang`) USING BTREE ;

ALTER TABLE `machine_error_code`
  ADD INDEX `machine` (`m_id`, `machine_id`) USING BTREE ,
  ADD INDEX `errorCode` (`errorCode`) USING BTREE ,
  ADD INDEX `ao_id` (`ao_id`) USING BTREE ,
  ADD INDEX `create_time` (`create_time`) USING BTREE ;

ALTER TABLE `machine_goods`
  DROP INDEX `m_id` ,
  ADD INDEX `machine` (`m_id`, `machine_id`) USING BTREE ,
  ADD INDEX `gc_id` (`gc_id`) USING BTREE ,
  ADD INDEX `sku` (`sku`) USING BTREE ,
  ADD INDEX `bar_code` (`bar_code`) USING BTREE ,
  ADD INDEX `is_shelf` (`is_shelf`) USING BTREE ,
  ADD INDEX `create_time` (`create_time`) USING BTREE ;

ALTER TABLE `machine_group`
  ADD INDEX `pid` (`pid`) USING BTREE ;

ALTER TABLE `machine_group_lang`
  ADD INDEX `mg_id` (`mg_id`) USING BTREE ,
  ADD INDEX `lang` (`lang`) USING BTREE ;

ALTER TABLE `machine_group_mg`
  ADD INDEX `machine` (`m_id`, `machine_id`) USING BTREE ,
  ADD INDEX `mg_id` (`mg_id`) USING BTREE ;

ALTER TABLE `machine_help`
  ADD INDEX `machine` (`m_id`, `machine_id`) USING BTREE ;

ALTER TABLE `machine_lang`
  ADD INDEX `machine` (`m_id`, `machine_id`) USING BTREE ,
  ADD INDEX `lang` (`lang`) USING BTREE ,
  ADD INDEX `create_time` (`create_time`, `update_time`) USING BTREE ,
  ADD INDEX `creator` (`creator`, `update_id`) USING BTREE ;

ALTER TABLE `machine_on_off`
  ADD INDEX `ao_id` (`ao_id`) USING BTREE ,
  ADD INDEX `status` (`status`) USING BTREE ,
  ADD INDEX `create_time` (`create_time`, `update_time`) USING BTREE ;

ALTER TABLE `machine_version`
  ADD INDEX `version_no` (`version_no`) USING BTREE ,
  ADD INDEX `create_time` (`create_time`, `update_time`) USING BTREE ;

ALTER TABLE `machine_version_plan`
  ADD INDEX `mv_id` (`mv_id`) USING BTREE ,
  ADD INDEX `machine` (`m_id`, `machine_id`) USING BTREE ,
  ADD INDEX `version_no` (`version_no`) USING BTREE ,
  ADD INDEX `publish_time` (`publish_time`) USING BTREE ,
  ADD INDEX `create_time` (`create_time`) USING BTREE ,
  ADD INDEX `update_time` (`update_time`) USING BTREE ;

ALTER TABLE `machine_view`
  ADD INDEX `template_id` (`template_id`) USING BTREE ,
  ADD INDEX `view_id` (`view_id`) USING BTREE ,
  ADD INDEX `machine` (`m_id`, `machine_id`) USING BTREE ,
  ADD INDEX `publish_time` (`publish_time`) USING BTREE ,
  ADD INDEX `expire_time` (`expire_time`) USING BTREE ,
  ADD INDEX `status` (`status`) USING BTREE ;

ALTER TABLE `micro_mall_machine`
  ADD INDEX `mm_id` (`mm_id`) USING BTREE ,
  ADD INDEX `m_id` (`m_id`) USING BTREE ;

ALTER TABLE `resource`
  ADD INDEX `title` (`title`) USING BTREE ,
  ADD INDEX `ao_id` (`ao_id`) USING BTREE ;

ALTER TABLE `robot_position`
  ADD INDEX `ac_id` (`ac_id`) USING BTREE ,
  ADD INDEX `machine` (`m_id`, `machine_id`) USING BTREE ,
  ADD INDEX `ao_id` (`ao_id`) USING BTREE ;

ALTER TABLE `sale_hotel_nightly`
  ADD INDEX `sh_id` (`sh_id`) USING BTREE ,
  ADD INDEX `hotelId` (`hotelId`) USING BTREE ,
  ADD INDEX `roomId` (`roomId`) USING BTREE ,
  ADD INDEX `effectiveDate` (`effectiveDate`) USING BTREE ;

ALTER TABLE `sale_orders_details`
  ADD INDEX `gmg_id` (`gmg_id`) USING BTREE ,
  ADD INDEX `g_id` (`g_id`) USING BTREE ,
  ADD INDEX `mg_id` (`mg_id`) USING BTREE ,
  ADD INDEX `sku` (`sku`) USING BTREE ,
  ADD INDEX `gc_id` (`gc_id`) USING BTREE ;

ALTER TABLE `sale_orders_refund`
  ADD INDEX `g_id` (`g_id`) USING BTREE ,
  ADD INDEX `gc_id` (`gc_id`) USING BTREE ,
  ADD INDEX `ao_id` (`ao_id`) USING BTREE ,
  ADD INDEX `sod_id` (`sod_id`) USING BTREE ,
  ADD INDEX `mg_id` (`mg_id`) USING BTREE ,
  ADD INDEX `manager_id` (`manager_id`) USING BTREE ,
  ADD INDEX `creator` (`creator`) USING BTREE ;

ALTER TABLE `sale_orders_revenue`
  ADD INDEX `si_id` (`si_id`) USING BTREE ,
  ADD INDEX `sp_id` (`sp_id`) USING BTREE ,
  ADD INDEX `order_id` (`order_id`) USING BTREE ,
  ADD INDEX `sod_id` (`sod_id`) USING BTREE ,
  ADD INDEX `m_id` (`m_id`) USING BTREE ,
  ADD INDEX `machine_id` (`machine_id`) USING BTREE ,
  ADD INDEX `refund_status` (`refund_status`) USING BTREE ,
  ADD INDEX `manager_id` (`manager_id`) USING BTREE ,
  ADD INDEX `bill_account` (`bill_account`) USING BTREE ,
  ADD INDEX `revenue_type` (`revenue_type`) USING BTREE ,
  ADD INDEX `revenue_time` (`revenue_time`) USING BTREE ,
  ADD INDEX `status` (`status`) USING BTREE ,
  ADD INDEX `create_time` (`create_time`, `update_time`) USING BTREE ;

ALTER TABLE `sale_orders_unclaimed`
  ADD INDEX `order_id` (`order_id`) USING BTREE ,
  ADD INDEX `trade_no` (`trade_no`) USING BTREE ,
  ADD INDEX `sod_id` (`sod_id`) USING BTREE ,
  ADD INDEX `machine` (`m_id`, `machine_id`) USING BTREE ,
  ADD INDEX `mc` (`mc_id`, `channel_code`) USING BTREE ,
  ADD INDEX `mg` (`mg_id`) USING BTREE ,
  ADD INDEX `g_id` (`g_id`) USING BTREE ,
  ADD INDEX `sku` (`sku`) USING BTREE ,
  ADD INDEX `transfer_time` (`transfer_time`) USING BTREE ,
  ADD INDEX `ao_id` (`ao_id`) USING BTREE ;

ALTER TABLE `strategy_machine`
  ADD INDEX `s_type` (`s_type`) USING BTREE ;

ALTER TABLE `strategy_manager`
  ADD INDEX `s_id` (`s_id`) USING BTREE ,
  ADD INDEX `s_type` (`s_type`) USING BTREE ,
  ADD INDEX `manager_id` (`manager_id`) USING BTREE ;


ALTER TABLE `strategy_payee`
  ADD INDEX `app_id` (`app_id`) USING BTREE ,
  ADD INDEX `mch_id` (`mch_id`) USING BTREE ;

ALTER TABLE `template_view`
  ADD INDEX `template_id` (`template_id`) USING BTREE ;

ALTER TABLE `trip_city`
  ADD INDEX `cityId` (`cityId`) USING BTREE ,
  ADD INDEX `initial` (`initial`) USING BTREE ;

ALTER TABLE `trip_multiple_hotel`
  ADD INDEX `tm_id` (`tm_id`) USING BTREE ,
  ADD INDEX `tc_id` (`tc_id`) USING BTREE ,
  ADD INDEX `cityId` (`cityId`) USING BTREE ,
  ADD INDEX `hotelId` (`hotelId`) USING BTREE ;

ALTER TABLE `trip_multiple_machine`
  ADD INDEX `tm_id` (`tm_id`) USING BTREE ,
  ADD INDEX `machine` (`m_id`, `machine_id`) USING BTREE ;


ALTER TABLE `user`
  ADD INDEX `wx_id` (`wx_id`) USING BTREE ,
  ADD INDEX `unionid` (`unionid`) USING BTREE ,
  ADD INDEX `openid` (`openid`) USING BTREE ,
  ADD INDEX `type` (`type`) USING BTREE ,
  ADD INDEX `manager_id` (`manager_id`) USING BTREE ;


ALTER TABLE `wx_official`
  ADD INDEX `gh_id` (`gh_id`) USING BTREE ,
  ADD INDEX `app_id` (`app_id`) USING BTREE ,
  ADD INDEX `ao_id` (`ao_id`) USING BTREE ;

ALTER TABLE `wx_official_login`
  ADD INDEX `wx_id` (`wx_id`) USING BTREE ,
  ADD INDEX `app_id` (`app_id`) USING BTREE ,
  ADD INDEX `machine` (`m_id`, `machine_id`) USING BTREE ,
  ADD INDEX `manager_id` (`manager_id`) USING BTREE ,
  ADD INDEX `account` (`account`) USING BTREE ,
  ADD INDEX `openid` (`openid`) USING BTREE ,
  ADD INDEX `login_type` (`login_type`) USING BTREE ,
  ADD INDEX `ao_id` (`ao_id`) USING BTREE ,
  ADD INDEX `status` (`status`) USING BTREE ,
  ADD INDEX `create_time` (`create_time`) USING BTREE ;

ALTER TABLE `wx_template`
  ADD INDEX `wx_id` (`wx_id`) USING BTREE ,
  ADD INDEX `template_type` (`template_type`) USING BTREE ,
  ADD INDEX `ao_id` (`ao_id`) USING BTREE ;

ALTER TABLE `wx_template_log`
  ADD INDEX `openid` (`openid`) USING BTREE ,
  ADD INDEX `ao_id` (`ao_id`) USING BTREE ,
  ADD INDEX `create_time` (`create_time`) USING BTREE ;

