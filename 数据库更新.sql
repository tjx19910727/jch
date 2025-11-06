# 20251105
ALTER TABLE kiosk.machine_error_code
  ADD COLUMN `transaction_video` varchar(255) NULL COMMENT '视频文件路径' AFTER `msg`;
  
# 20250901
ALTER TABLE `machine` 
  ADD COLUMN `factory` varchar(100) NULL COMMENT '所属工厂' AFTER `current_status`;

ALTER TABLE `machine` 
  ADD COLUMN `inventory_location` varchar(100) NULL COMMENT '库存地点' AFTER `factory`;

ALTER TABLE `sale_orders` 
  ADD COLUMN `factory` varchar(100) NULL COMMENT '所属工厂' AFTER `manager_id`;

ALTER TABLE `sale_orders` 
  ADD COLUMN `inventory_location` varchar(100) NULL COMMENT '库存地点' AFTER `factory`;




# 20250830
ALTER TABLE `machine_help`
  ADD COLUMN `pid`  int NULL DEFAULT 0 COMMENT '主帮助信息ID' AFTER `mh_id`;






# 20250822
# 增加商品分类组织架构ID
ALTER TABLE `goods_category`
  ADD COLUMN `ao_id`  int NULL DEFAULT 0 COMMENT '组织架构ID' AFTER `status`;
UPDATE `goods_category` gc set ao_id = (SELECT ao_id FROM auth_manager m WHERE m.manager_id = gc.creator LIMIT 1);

# 设备分组增加组织架构ID
ALTER TABLE `machine_group`
  ADD COLUMN `ao_id`  int NULL DEFAULT 0 COMMENT '组织架构ID' AFTER `status`;
UPDATE `machine_group` mg set ao_id = (SELECT ao_id FROM auth_manager m WHERE m.manager_id = mg.creator LIMIT 1);

# 商品角标增加组织架构ID
ALTER TABLE `goods_corner`
  ADD COLUMN `ao_id`  int NULL DEFAULT 0 COMMENT '组织架构ID' AFTER `status`;
UPDATE `goods_corner` gc set ao_id = (SELECT ao_id FROM auth_manager m WHERE m.manager_id = gc.creator LIMIT 1);

# 模板增加组织架构ID
ALTER TABLE `template`
  ADD COLUMN `ao_id`  int NULL DEFAULT 0 COMMENT '组织架构ID' AFTER `resolution`;
UPDATE `template` t set ao_id = (SELECT ao_id FROM auth_manager m WHERE m.manager_id = t.creator LIMIT 1);

# 视图增加组织架构ID
ALTER TABLE `template_view`
  ADD COLUMN `ao_id`  int NULL DEFAULT 0 COMMENT '组织ID' AFTER `plugin_data`;
UPDATE `template_view` tv set ao_id = (SELECT ao_id FROM auth_manager m WHERE m.manager_id = tv.creator LIMIT 1);

# 设备视图增加组织架构ID
ALTER TABLE `machine_view`
  ADD COLUMN `ao_id`  int NULL DEFAULT 0 COMMENT '组织ID' AFTER `status`;
UPDATE `machine_view` mv set ao_id = (SELECT ao_id FROM machine m WHERE m.m_id = mv.m_id LIMIT 1);

# 性能参数增加组织ID
ALTER TABLE `config_performance`
  ADD COLUMN `ao_id`  int NULL DEFAULT 0 COMMENT '组织ID' AFTER `lang`;
UPDATE `config_performance` cp set ao_id = (SELECT ao_id FROM auth_manager m WHERE m.manager_id = cp.creator LIMIT 1);

# 尺寸管理增加组织ID
ALTER TABLE `config_size`
  ADD COLUMN `ao_id`  int NULL DEFAULT 0 COMMENT '组织ID' AFTER `type`;
UPDATE `config_size` set ao_id = 17;

# 场景增加组织ID
ALTER TABLE `config_scene`
  ADD COLUMN `ao_id`  int NULL DEFAULT 0 COMMENT '组织ID' AFTER `status`;
UPDATE `config_scene` cs set ao_id = (SELECT ao_id FROM auth_manager m WHERE m.manager_id = cs.creator LIMIT 1);

# 调整库存盘点记录统计统计视图
DROP VIEW machine_check_stock_count;
CREATE ALGORITHM=UNDEFINED DEFINER=`cf`@`%` SQL SECURITY DEFINER VIEW `machine_check_stock_count` AS select `mcs`.`m_id` AS `m_id`,`mcs`.`machine_id` AS `machine_id`,`mcs`.`machine_name` AS `machine_name`,sum(`mcs`.`check_stock`) AS `check_stock`,sum(`mcs`.`system_stock`) AS `system_stock`,sum((case `mcs`.`mc_id` when 0 then `mcs`.`check_stock` else 0 end)) AS `stock_reserve`,max(`mcs`.`create_time`) AS `create_time`,(select `au`.`nickname` from `auth_manager` `au` where (`au`.`manager_id` = `mcs`.`creator`)) AS `creator_nickname`,(select `m`.`ao_id` from `machine` `m` where (`m`.`m_id` = `mcs`.`m_id`) limit 1) AS `ao_id` from `machine_check_stock` `mcs` group by `mcs`.`m_id`,`mcs`.`create_date`

# 设备库存盘点记录表增加组织ID
ALTER TABLE `machine_check_stock`
  ADD COLUMN `ao_id`  int NULL DEFAULT 0 COMMENT '组织ID' AFTER `type`;
UPDATE `machine_check_stock` mcs set ao_id = (SELECT ao_id FROM machine m WHERE m.m_id = mcs.m_id LIMIT 1);

# 设备在线记录增加组织ID
ALTER TABLE `machine_online`
  ADD COLUMN `ao_id`  int NULL DEFAULT 0 COMMENT '组织ID' AFTER `duration`;
UPDATE `machine_online` mo set ao_id = (SELECT ao_id FROM machine m WHERE m.m_id = mo.m_id LIMIT 1);

# 设备在线记录详情增加组织ID
ALTER TABLE `machine_online_details`
  ADD COLUMN `ao_id`  int NULL DEFAULT 0 COMMENT '组织ID' AFTER `d_date`;
UPDATE `machine_online_details` d set ao_id = (SELECT ao_id FROM machine m WHERE m.m_id = d.m_id LIMIT 1);

# 货道补货记录增加组织ID
ALTER TABLE `machine_channel_replenishment`
  ADD COLUMN `ao_id`  int NULL DEFAULT 0 COMMENT '组织ID' AFTER `after`;
UPDATE `machine_channel_replenishment` mcr set ao_id = (SELECT ao_id FROM machine m WHERE m.m_id = mcr.m_id LIMIT 1);

# 导出日志增加组织ID
ALTER TABLE `export_log`
  ADD COLUMN `ao_id`  int NULL DEFAULT 0 COMMENT '组织ID' AFTER `status`;
UPDATE `export_log` el set ao_id = (SELECT ao_id FROM auth_manager m WHERE m.manager_id = el.creator LIMIT 1);

# 广告播放记录增加组织ID
ALTER TABLE `advertisement_record`
  ADD COLUMN `ao_id`  int NULL DEFAULT 0 COMMENT '组织ID' AFTER `play_time`;
UPDATE `advertisement_record` ar set ao_id = (SELECT ao_id FROM machine m WHERE m.m_id = ar.m_id LIMIT 1);

# 优惠券活动增加组织ID
ALTER TABLE `activity_coupon`
  ADD COLUMN `ao_id`  int NULL DEFAULT 0 COMMENT '组织ID' AFTER `status`;
UPDATE `activity_coupon` ac set ao_id = (SELECT ao_id FROM auth_manager m WHERE m.manager_id = ac.creator LIMIT 1);

# 满减活动增加组织ID
ALTER TABLE `activity_fd`
  ADD COLUMN `ao_id`  int NULL DEFAULT 0 COMMENT '组织ID' AFTER `status`;
UPDATE `activity_fd` fd set ao_id = (SELECT ao_id FROM auth_manager m WHERE m.manager_id = fd.creator LIMIT 1);

# 抽奖活动增加组织ID
ALTER TABLE `activity_lottery`
  ADD COLUMN `ao_id`  int NULL DEFAULT 0 COMMENT '组织ID' AFTER `status`;
UPDATE `activity_lottery` al set ao_id = (SELECT ao_id FROM auth_manager m WHERE m.manager_id = al.creator LIMIT 1);

# 提货码增加组织ID
ALTER TABLE `activity_pick`
  ADD COLUMN `ao_id`  int NULL DEFAULT 0 COMMENT '组织ID' AFTER `status`;
UPDATE `activity_pick` ap set ao_id = (SELECT ao_id FROM auth_manager m WHERE m.manager_id = ap.creator LIMIT 1);

# 收款策略增加组织ID
ALTER TABLE `strategy_payee`
  ADD COLUMN `ao_id`  int NULL DEFAULT 0 COMMENT '组织ID' AFTER `status`;
UPDATE `strategy_payee` sp set ao_id = (SELECT ao_id FROM auth_manager m WHERE m.manager_id = sp.creator LIMIT 1);

# 收款策略绑定设备增加组织ID
ALTER TABLE `strategy_machine`
  ADD COLUMN `ao_id`  int NULL DEFAULT 0 COMMENT '组织ID' AFTER `sort`;
UPDATE `strategy_machine` sm set ao_id = (SELECT ao_id FROM machine m WHERE m.m_id = sm.m_id LIMIT 1);

# 分润策略增加组织ID
ALTER TABLE `strategy_income`
  ADD COLUMN `ao_id`  int NULL DEFAULT 0 COMMENT '组织ID' AFTER `status`;
UPDATE `strategy_income` si set ao_id = (SELECT ao_id FROM auth_manager m WHERE m.manager_id = si.creator LIMIT 1);

# 策略绑定账号增加组织ID
ALTER TABLE `strategy_manager`
  ADD COLUMN `ao_id`  int NULL DEFAULT 0 COMMENT '组织ID' AFTER `sort`;
UPDATE `strategy_manager` sm set ao_id = (SELECT ao_id FROM auth_manager m WHERE m.manager_id = sm.manager_id LIMIT 1);

# 权限角色增加组织ID
ALTER TABLE `auth_role`
  ADD COLUMN `ao_id`  int NULL DEFAULT 0 COMMENT '组织ID' AFTER `status`;
UPDATE `auth_role` ar set ao_id = (SELECT ao_id FROM auth_manager m WHERE m.manager_id = ar.creator LIMIT 1);

















# 20250621 2
# sale_orders_daily_count视图调整
CREATE ALGORITHM=UNDEFINED DEFINER=`cf`@`%` SQL SECURITY DEFINER VIEW `sale_orders_daily_count` AS select (select `ao`.`organization_name` from `auth_organization` `ao` where (`ao`.`ao_id` = `so`.`ao_id`)) AS `ao_name`,`so`.`m_id` AS `m_id`,`so`.`machine_id` AS `machine_id`,`so`.`machine_name` AS `machine_name`,sum((select sum(`sor`.`refund_amount`) from `sale_orders_refund` `sor` where ((`sor`.`order_id` = `so`.`order_id`) and (`sor`.`status` = 2) and (`sor`.`create_time` between `so`.`create_date` and (`so`.`create_date` + 86400))) limit 1)) AS `totalRefundAmount`,sum((select sum(`sor2`.`refund_quantity`) from `sale_orders_refund` `sor2` where ((`sor2`.`order_id` = `so`.`order_id`) and (`sor2`.`status` = 2) and (`sor2`.`create_time` between `so`.`create_date` and (`so`.`create_date` + 86400))) limit 1)) AS `totalRefundQuantity`,sum(`so`.`total_price`) AS `totalPrice`,sum(`so`.`discount_price`) AS `totalDiscountPrice`,sum(`so`.`total_quantity`) AS `totalQuantity`,ifnull((select sum(`sod`.`success_quantity`) from `sale_orders_details` `sod` where ((`sod`.`order_id` = `so`.`order_id`) and (`sod`.`is_gift` = 1))),0) AS `giftQuantity`,`so`.`ao_id` AS `ao_id`,sum((case when (`so`.`coupon_id` > 0) then 1 else 0 end)) AS `coupon_used`,sum((case when (`so`.`lottery_id` > 0) then 1 else 0 end)) AS `lottery_used`,sum((case when (`so`.`lottery_id` > 0) then (`so`.`total_price` - `so`.`refund_amount`) else 0 end)) AS `lotteryAmount`,sum((case when (`so`.`lottery_id` > 0) then (`so`.`total_quantity` - `so`.`refund_quantity`) else 0 end)) AS `lotteryQuantity`,count(`so`.`order_id`) AS `order_num`,date_format(from_unixtime(`so`.`create_date`),'%Y-%m-%d') AS `countDate`,`so`.`create_date` AS `create_date`,date_format(now(),'%Y-%m-%d %H:%i:%s') AS `last_update_time` from `sale_orders` `so` where (`so`.`pay_status` = 3) group by `so`.`machine_id`,`so`.`m_id`,`so`.`create_date`

# sale_orders_goods_count视图调整
CREATE ALGORITHM=UNDEFINED DEFINER=`cf`@`%` SQL SECURITY DEFINER VIEW `sale_orders_goods_count` AS select `sod`.`g_id` AS `g_id`,`sod`.`g_name` AS `g_name`,`sod`.`pic` AS `pic`,`sod`.`sku` AS `sku`,`sod`.`gc_id` AS `gc_id`,`sod`.`gc_name` AS `gc_name`,`sod`.`cost_price` AS `cost_price`,`sod`.`market_price` AS `market_price`,`sod`.`retail_price` AS `retail_price`,`so`.`ao_id` AS `ao_id`,sum(`sod`.`total_sod_price`) AS `totalPrice`,sum(`sod`.`quantity`) AS `totalQuantity`,sum((select sum(`sor`.`refund_amount`) from `sale_orders_refund` `sor` where ((`sor`.`sod_id` = `sod`.`sod_id`) and (`sor`.`status` = 2) and (`sor`.`create_time` between `so`.`create_date` and (`so`.`create_date` + 86400))) limit 1)) AS `totalRefundAmount`,sum((select sum(`sor2`.`refund_quantity`) from `sale_orders_refund` `sor2` where ((`sor2`.`sod_id` = `sod`.`sod_id`) and (`sor2`.`status` = 2) and (`sor2`.`create_time` between `so`.`create_date` and (`so`.`create_date` + 86400))) limit 1)) AS `totalRefundQuantity`,sum(`sod`.`discount_price`) AS `totalDiscountPrice`,`so`.`create_date` AS `countDate`,date_format(now(),'%Y-%m-%d %H:%i:%s') AS `last_update_time` from (`sale_orders_details` `sod` left join `sale_orders` `so` on((`so`.`order_id` = `sod`.`order_id`))) where (`so`.`pay_status` = 3) group by `sod`.`g_id`,`so`.`create_date`

# sale_orders_machine_count视图调整
CREATE ALGORITHM=UNDEFINED DEFINER=`cf`@`%` SQL SECURITY DEFINER VIEW `sale_orders_machine_count` AS select `so`.`m_id` AS `m_id`,`so`.`machine_id` AS `machine_id`,`so`.`machine_name` AS `machine_name`,sum(ifnull((select sum(`sor`.`refund_amount`) from `sale_orders_refund` `sor` where ((`sor`.`order_id` = `so`.`order_id`) and (`sor`.`status` = 2) and (`sor`.`create_time` between `so`.`create_date` and (`so`.`create_date` + 86400))) limit 1),0)) AS `totalRefundAmount`,sum(ifnull((select sum(`sor2`.`refund_quantity`) from `sale_orders_refund` `sor2` where ((`sor2`.`order_id` = `so`.`order_id`) and (`sor2`.`status` = 2) and (`sor2`.`create_time` between `so`.`create_date` and (`so`.`create_date` + 86400))) limit 1),0)) AS `totalRefundQuantity`,sum(`so`.`total_price`) AS `totalPrice`,sum(`so`.`discount_price`) AS `totalDiscountPrice`,sum(`so`.`total_quantity`) AS `totalQuantity`,`so`.`ao_id` AS `ao_id`,count((select `acu`.`cu_id` from `activity_coupon_used` `acu` where ((`acu`.`order_id` = `so`.`order_id`) and (`acu`.`status` = 2)))) AS `coupon_used`,count(`so`.`order_id`) AS `order_num`,`so`.`create_date` AS `countDate`,date_format(now(),'%Y-%m-%d %H:%i:%s') AS `last_update_time` from `sale_orders` `so` where (`so`.`pay_status` = 3) group by `so`.`m_id`,`so`.`ao_id`,`so`.`create_date` order by `so`.`create_date` desc


# 20250621 1
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

