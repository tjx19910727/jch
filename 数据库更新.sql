#20260117   补充更新，已执行，无需操作
ALTER TABLE kiosk.card_points_change_logs
  ADD COLUMN  `bind_id` varchar(50) DEFAULT NULL COMMENT '绑定id'  AFTER `reasons`;

update  card_points_change_logs set bind_id = '1922369655' where id=40;
update  card_points_change_logs set bind_id = '13829235560' where id=35;
update  card_points_change_logs set bind_id = '18575673257' where id=30;
update  card_points_change_logs set bind_id = '13268508868' where id=27;
update  card_points_change_logs set bind_id = '13829235560' where id=25;
update  card_points_change_logs set bind_id = '13302310001' where id=23;
update  card_points_change_logs set bind_id = '13726659948' where id=21;
update  card_points_change_logs set bind_id = '13612696146' where id=16;
update  card_points_change_logs set bind_id = '13714759235' where id=10;
update  card_points_change_logs set bind_id = '13714759235' where id=8;
update  card_points_change_logs set bind_id = '13714759235' where id=5;



#20260108
ALTER TABLE kiosk.machine_channel
  ADD COLUMN `intergral_rate` decimal(10,3) default 0 COMMENT '积分-现金兑换比例（1元=10积分） ' AFTER `retail_price`;
ALTER TABLE kiosk.machine_goods
  ADD COLUMN `intergral_rate` decimal(10,3) default 0 COMMENT '积分-现金兑换比例（1元=10积分） ' AFTER `retail_price`;
ALTER TABLE kiosk.goods
  ADD COLUMN `intergral_rate` decimal(10,3) default 0 COMMENT '积分-现金兑换比例（1元=10积分） ' AFTER `retail_price`;


ALTER TABLE kiosk.machine_channel
  ADD COLUMN `gift_points` decimal(10,3) default 0 COMMENT '赠送积分' AFTER `intergral_rate`;
ALTER TABLE kiosk.machine_goods
  ADD COLUMN `gift_points` decimal(10,3) default 0 COMMENT '赠送积分' AFTER `intergral_rate`;
ALTER TABLE kiosk.goods
  ADD COLUMN `gift_points` decimal(10,3) default 0 COMMENT '赠送积分' AFTER `intergral_rate`;

CREATE TABLE `card` (
  `card_no` varchar(20) NOT NULL COMMENT '芯片卡号，主键',
  `card_show_no` varchar(20) DEFAULT NULL COMMENT '卡面卡号',
  `points` decimal(10,2) DEFAULT 0 COMMENT '积分',
  `bind_id` varchar(20) DEFAULT NULL COMMENT '绑定会员id',
  `bind_id_points` decimal(10,2) DEFAULT 0 COMMENT '会员账户积分',
  PRIMARY KEY (`card_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `card_points_change_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `card_no` varchar(20) DEFAULT NULL COMMENT '卡号',
  `points_before_change` decimal(10,2) DEFAULT NULL COMMENT '变化前积分',
  `points_changed` decimal(10,2) DEFAULT NULL COMMENT '积分变化量',
  `points` decimal(10,2) DEFAULT NULL COMMENT '变化后积分',
  `change_type` int(1) DEFAULT NULL COMMENT '变化类型1：增加 2：减少',
  `trade_no` varchar(50) DEFAULT NULL COMMENT '积分变化关联订单编号',
  `reasons` varchar(200) COMMENT '原因',
  `bind_id` varchar(20) DEFAULT NULL COMMENT '绑定id',
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `card_points_change_logs_card_no_IDX` (`card_no`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='卡积分变化表';


CREATE TABLE `wc_goods` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sellerPrice` decimal(10,5) DEFAULT NULL COMMENT '分销价格',
  `effectiveBegin` date DEFAULT NULL COMMENT '有效开始时间',
  `type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL COMMENT '商品类型(1抢购、2景点门票、3酒店住宿、4旅游线路、5普通商品)',
  `maxBuy` int DEFAULT NULL COMMENT '最大购买数量',
  `area_id` int DEFAULT NULL COMMENT '区域id',
  `effectiveEnd` date DEFAULT NULL COMMENT '有效结束时间',
  `is_post` int DEFAULT NULL COMMENT '海报是否可以绑定分销商',
  `show_citys` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL COMMENT '展示城市',
  `goods_labels` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL COMMENT '商品标签',
  `price` decimal(10,5) DEFAULT NULL COMMENT '销售价',
  `supplierPrice` decimal(10,5) DEFAULT NULL COMMENT '采购价',
  `good_cancel_order_time` int DEFAULT NULL COMMENT '商品订单支付有效期（分钟）',
  `poster_background` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL COMMENT '商品图片(注: 图片完整地址 resourceDomain + poster_background)',
  `resourceDomain` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL COMMENT '资源服务器域名',
  `poster_type` int DEFAULT NULL COMMENT '海报类型',
  `is_show_customer_service` int DEFAULT NULL COMMENT '是否展示客服按钮',
  `auto_refund` int DEFAULT NULL COMMENT '是否自动退款',
  `is_show_mark` int DEFAULT NULL COMMENT '下单是否展示备注',
  `custom_item_config` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci COMMENT '自定义填写项配置',
  `sellbegin` date DEFAULT NULL COMMENT '开售日期',
  `detail_type` int DEFAULT NULL COMMENT '详情页类型',
  `valid_date_limit_end` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL COMMENT '指定可用日期',
  `open_meal` int DEFAULT NULL COMMENT '开启套餐',
  `order_limit_config` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `is_allow_refund` int DEFAULT NULL COMMENT '是否允许退款',
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL COMMENT '商品名称',
  `valid_date_type` int DEFAULT NULL COMMENT '可用日期类型',
  `order_limit` int DEFAULT NULL COMMENT '是否做下单限制',
  `slogan` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL COMMENT '广告语',
  `selfMentionArray` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci COMMENT '自提点信息',
  `city_id` int DEFAULT NULL COMMENT '城市id',
  `bookingDayEnd` int DEFAULT NULL COMMENT '提前预定天数',
  `no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL COMMENT '商品编号/货号',
  `marketPrice` decimal(10,5) DEFAULT NULL COMMENT '市场价',
  `site_audit_status` int DEFAULT NULL COMMENT '商家审核状态',
  `verification_limit` int DEFAULT NULL COMMENT '核验时间限制',
  `sellEnd` date DEFAULT NULL COMMENT '开售日期结束',
  `is_custom_item` int DEFAULT NULL COMMENT '是否自定义项，0是单游客，2是多游客',
  `freight` decimal(10,5) DEFAULT NULL COMMENT '运费',
  `description` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL COMMENT '商品简介',
  `is_good_order_finish` int DEFAULT NULL COMMENT '是否下单直接完成',
  `isRequireBuyerName` int DEFAULT NULL COMMENT '姓名是否必填（默认）',
  `total_stock` int DEFAULT NULL COMMENT '总库存',
  `valid_date_days` int DEFAULT NULL COMMENT '几天内可使用',
  `is_self_mention` int DEFAULT NULL COMMENT '是否自提',
  `resourcesArray` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci COMMENT '图片',
  `isRequireBuyerIdcard` int DEFAULT NULL COMMENT '身份证号码是否必填',
  `notice` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL COMMENT '支付通知备注',
  `surplus_stock` int DEFAULT NULL COMMENT '剩余库存',
  `address` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL COMMENT '邮寄地址',
  `link_phone` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL COMMENT '联系电话',
  `ticket_check_style` int DEFAULT NULL COMMENT '核验类型',
  `store` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL COMMENT '库存',
  `bookingTimeEnd` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL COMMENT '几点之前可预定',
  `buy_limit` int DEFAULT NULL COMMENT '购买限制',
  `mp3` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL COMMENT '语音信息',
  `isRequireBuyerPhone` int DEFAULT NULL COMMENT '手机号是否必填',
  `img_customize` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL COMMENT '自定义海报配置',
  `province_id` int DEFAULT NULL COMMENT '省id',
  `isRequireBuyerAddress` int DEFAULT NULL COMMENT '邮寄地址',
  `qianggou_related_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL COMMENT '抢购关联产品key',
  `isNeedReserve` int DEFAULT NULL COMMENT '是否需要预定',
  `category` int DEFAULT NULL COMMENT '商品类别id',
  `qq_video_id` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL COMMENT '腾讯视频id',
  `daysInfo` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci COMMENT '日历库存列表',
  `combination_goods` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci COMMENT '组合产品子产品列表',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='微程商品信息表';


CREATE TABLE `wc_request_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `request_url` varchar(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `request_headers` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `request_body` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `response_status` int DEFAULT NULL,
  `response_body` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `type` tinyint NOT NULL DEFAULT '1' COMMENT '请求类型：1-请求外部接口，2-外部接口回调',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=216 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='微程接口调用记录表';


#20251208
ALTER TABLE kiosk.sale_orders
  ADD COLUMN `intergral_rate` decimal(10,3) default 0 COMMENT '当前订单积分-现金兑换比例（1元=10积分） ' AFTER `total_price`;
ALTER TABLE kiosk.sale_orders
  ADD COLUMN `total_points` decimal(10,3) default 0 COMMENT '消耗总积分 ' AFTER `intergral_rate`;
ALTER TABLE kiosk.sale_orders
  ADD COLUMN `refund_points` decimal(10,3) default 0 COMMENT '退款总积分 ' AFTER `total_points`;

ALTER TABLE kiosk.sale_orders MODIFY COLUMN order_type tinyint(1) DEFAULT 1 NULL COMMENT '订单类型，1：普通订单，2：优惠券订单，3：取货码订单，4：盲盒活动，5：满减满送活动，6：叠加营销活动，7商场积分扣费订单';
ALTER TABLE kiosk.sale_orders MODIFY COLUMN pay_type tinyint(1) DEFAULT 0 NULL COMMENT '支付类型，0：免支付，1：微信支付，2：支付宝支付，3：，4：京东收银，5：会员支付，6：丽呈线上支付，7：机器人线上支付，8：COGOLINK，9：商场积分支付';

ALTER TABLE kiosk.sale_orders_details
  ADD COLUMN `total_sod_points` decimal(10,3) default 0 COMMENT '副表消耗总积分 ' AFTER `total_sod_price`;
ALTER TABLE kiosk.sale_orders_details
  ADD COLUMN `refund_points` decimal(10,3) default 0 COMMENT '副表消耗总积分 ' AFTER `refund_amount`;
ALTER TABLE kiosk.sale_orders_refund
  ADD COLUMN `refund_points` decimal(10,3) default 0 COMMENT '退还积分 ' AFTER `refund_amount`;

CREATE TABLE `mall` (                     
  `mall_id` int NOT NULL AUTO_INCREMENT,
  `mall_code` int NOT NULL COMMENT '商场编码',
  `mall_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `province` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `district` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `full_address` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `intergral_rate` decimal(10,2) NOT NULL DEFAULT '1.00' COMMENT '积分-现金兑换比例（1元=10积分）',
  `type` tinyint(1) DEFAULT '1' COMMENT '支付类型：1-不使用积分，2-只使用积分，3-积分+现金',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：1-有效，2-失效',
  `start_time` datetime DEFAULT NULL,
  `expire_time` datetime DEFAULT NULL,
  `creator` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updator` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mall_id` (`mall_id`),
  UNIQUE KEY `mall_name` (`mall_name`),
  UNIQUE KEY `account` (`account`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='商场信息表';

CREATE TABLE `mall_machine` (
  `id` int NOT NULL AUTO_INCREMENT,
  `mall_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `m_id` int(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `machine_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '关联状态：1-正常关联，2-暂停关联，3-其他状态',
  `creator` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '创建人',
  `updator` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '更新人',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='商场设备关联表';


CREATE TABLE `request_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `mall_id` int NOT NULL,
  `order_id` int NOT NULL,
  `request_url` varchar(1024) COLLATE utf8mb4_unicode_ci NOT NULL,
  `request_headers` text COLLATE utf8mb4_unicode_ci,
  `request_body` text COLLATE utf8mb4_unicode_ci,
  `response_status` int DEFAULT NULL,
  `response_body` text COLLATE utf8mb4_unicode_ci,
  `type` tinyint NOT NULL DEFAULT '1' COMMENT '请求类型：1-请求外部接口，2-外部接口回调',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='接口调用记录表';


#20251228
UPDATE kiosk.wx_template
SET  template_id='frqumju8oA7N8msUrhIiHkY6Gu1wgiGNq_Oo5TxjYsY', body='[{"设备编号":{"value":"{{machine_id}}","field":"character_string47"}},{"设备名称":{"value":"{{machine_name}}","field":"thing6"}},{"异常时间":{"value":"{{error_time}}","field":"time15"}},{"异常现象":{"value":"{{error_info}}","field":"thing12"}},{"异常类型":{"value":"{{error_code}}","field":"phrase13"}}]', 
WHERE template_type='mFault';

UPDATE kiosk.wx_template
SET  template_id='frqumju8oA7N8msUrhIiHsm0NQQEj3kZOgdMVnkfy-4', body='[{"设备编号":{"value":"{{machine_id}}","field":"character_string47"}},{"设备名称":{"value":"{{machine_name}}","field":"thing6"}},{"时间":{"value":"{{now}}","field":"time30"}},{"异常现象":{"value":"{{error_info}}","field":"thing12"}},{"异常类型":{"value":"{{error_code}}","field":"phrase13"}}]', 
WHERE template_type='tException';

#20251217
UPDATE kiosk.strategy_machine SET ao_id=19 WHERE sm_id=2372;
UPDATE kiosk.strategy_machine SET ao_id=19 WHERE sm_id=2373;

#20251216
UPDATE kiosk.auth_role SET ao_id=19 WHERE role_id=35;
UPDATE kiosk.auth_role SET ao_id=19 WHERE role_id=39;
UPDATE kiosk.auth_role SET ao_id=19 WHERE role_id=40;

#20251211
ALTER TABLE kiosk.machine
  ADD COLUMN `ckc_status` tinyint(1) DEFAULT 1 COMMENT '营业状态：1：正常营业，2-暂停营业'  AFTER `current_status` ;



#20251201
CREATE TABLE `wechat_menu` (
  `id` int NOT NULL AUTO_INCREMENT,
  `old_content` json CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '编辑成功前，公众号菜单内容',
  `new_content` json CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '编辑成功后，公众号菜单内容',
  `update_manager` int CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '操作人',
  `update_time` int NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='公众号自定义菜单记录表';


# 20251121
ALTER TABLE kiosk.machine_error_code
  ADD COLUMN `trade_no` varchar(50) NULL COMMENT '开门视频单号' AFTER `msg` ;
ALTER TABLE kiosk.machine_error_code ADD INDEX idx_trade_no (trade_no);

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

