#20260305
ALTER TABLE kiosk.wc_request_logs MODIFY COLUMN response_body longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL;
ALTER TABLE kiosk.wc_goods MODIFY COLUMN goods longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '实物商品信息';
ALTER TABLE kiosk.wc_goods MODIFY COLUMN get_data longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '接口返回内容';
ALTER TABLE kiosk.wc_goods MODIFY COLUMN notice text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL COMMENT '支付通知备注';
ALTER TABLE kiosk.sale_orders ADD  `wc_order_no` text COLLATE utf8mb4_unicode_ci COMMENT '对应微程单号' after `order_id`;

ALTER TABLE kiosk.wc_goods_local MODIFY COLUMN daysInfo longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL;
ALTER TABLE kiosk.wc_goods_local ADD COLUMN  `isNeedReserve` int default 0 COMMENT '是否抢购' AFTER `type`;

CREATE TABLE `wc_machine_channel` (
  `mc_id` int NOT NULL AUTO_INCREMENT COMMENT '货道ID',
  `m_id` int NOT NULL COMMENT '设备ID',
  `machine_id` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '设备编号',
  `channel_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '货道编号',
  `g_id` int DEFAULT '0' COMMENT '商品ID',
  `out_no` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '0' COMMENT '微程商品外部编码',
  `g_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '商品名称',
  `gc_id` int DEFAULT '0' COMMENT '分类ID',
  `gc_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '分类名称',
  `pic` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '商品图片',
  `sku` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'SKU码',
  `bar_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '条码',
  `retail_price` decimal(10,2) DEFAULT '0.00' COMMENT '零食价',
  `intergral_rate` decimal(10,3) DEFAULT '0.000' COMMENT '积分-现金兑换比例（1元=10积分） ',
  `gift_points` decimal(10,3) DEFAULT '0.000' COMMENT '赠送积分',
  `daysInfo` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `sort` int DEFAULT NULL COMMENT '排序',
  `create_time` int DEFAULT '0' COMMENT '创建时间',
  `update_time` int DEFAULT '0' COMMENT '修改时间',
  PRIMARY KEY (`mc_id`) USING BTREE,
  KEY `m_id` (`m_id`) USING BTREE,
  KEY `machine_id` (`machine_id`) USING BTREE,
  KEY `g_id` (`g_id`) USING BTREE,
  KEY `sku` (`sku`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='微程虚拟商品设备货道表';

CREATE TABLE `wc_machine_goods` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '主键',
  `m_id` int DEFAULT NULL COMMENT '设备ID',
  `machine_id` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '设备编号',
  `out_no` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '父商品编码',
  `g_id` int DEFAULT NULL COMMENT '商品ID',
  `g_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '商品名称',
  `type` int DEFAULT NULL COMMENT '商品分类ID',
  `type_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '商品分类',
  `sort` int DEFAULT '1' COMMENT '排序',
  `pic` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '商品图片',
  `sku` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'SKU码',
  `bar_code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cost_price` decimal(10,2) DEFAULT '0.00' COMMENT '成本价',
  `market_price` decimal(10,2) DEFAULT '0.00' COMMENT '市场价',
  `retail_price` decimal(10,2) DEFAULT '0.00' COMMENT '零售价',
  `daysInfo` text COLLATE utf8mb4_unicode_ci,
  `intergral_rate` decimal(10,3) DEFAULT '0.000' COMMENT '积分-现金兑换比例（1元=10积分） ',
  `gift_points` decimal(10,3) DEFAULT '0.000' COMMENT '赠送积分',
  `available_stock` int DEFAULT '0' COMMENT '可用库存',
  `disabled_stock` int DEFAULT '0' COMMENT '不可用库存',
  `reserve_stock` int DEFAULT '0' COMMENT '预订量',
  `standby_stock` int DEFAULT '0' COMMENT '备用库存',
  `pre_loading_stock` int DEFAULT '0' COMMENT '预上货库存',
  `is_shelf` tinyint(1) DEFAULT '2' COMMENT '已上架，1：是，2：否',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `g_id` (`g_id`) USING BTREE,
  KEY `machine` (`m_id`,`machine_id`) USING BTREE,
  KEY `type` (`type`) USING BTREE,
  KEY `sku` (`sku`) USING BTREE,
  KEY `bar_code` (`bar_code`) USING BTREE,
  KEY `is_shelf` (`is_shelf`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='微程设备商品表';


CREATE TABLE `wc_users_addresses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `bind_id` varchar(20) NOT NULL COMMENT '会员账户：一般为手机号',
  `address` text COMMENT '邮寄地址',
  `link_name` varchar(50) DEFAULT NULL COMMENT '收件人姓名',
  `phone` varchar(100) DEFAULT NULL COMMENT '收件人手机号',
  PRIMARY KEY (`id`),
  UNIQUE KEY `wc_users_unique` (`bind_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='微程会员寄送地址';


CREATE TABLE `wechat_menu` (
  `id` int NOT NULL AUTO_INCREMENT,
  `old_content` json DEFAULT NULL COMMENT '修改前，公众号菜单内容',
  `new_content` json DEFAULT NULL COMMENT '修改后，公众号菜单内容',
  `update_manager` int DEFAULT NULL COMMENT '操作人',
  `update_time` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='公众号自定义菜单记录表';


#20260226
ALTER TABLE kiosk.machine_channel
  ADD COLUMN `cost_points` decimal(10,3) default 0 COMMENT '消费积分' AFTER `gift_points`;
ALTER TABLE kiosk.machine_goods
  ADD COLUMN `cost_points` decimal(10,3) default 0 COMMENT '消费积分' AFTER `gift_points`;
ALTER TABLE kiosk.goods
  ADD COLUMN `cost_points` decimal(10,3) default 0 COMMENT '消费积分' AFTER `gift_points`;

ALTER TABLE kiosk.sale_orders
  ADD COLUMN `total_cost_points` decimal(10,3) default 0 COMMENT '当前订单总消费积分 ' AFTER `total_price`;

ALTER TABLE kiosk.sale_orders_details
  ADD COLUMN `total_sod_cost_points` decimal(10,3) default 0 COMMENT '当前子订单总消费积分 ' AFTER `total_sod_price`;

ALTER TABLE kiosk.sale_orders
  ADD COLUMN `refund_cost_points` decimal(10,3) default 0 COMMENT '退还消费积分 ' AFTER `refund_amount`;
ALTER TABLE kiosk.sale_orders_details
  ADD COLUMN `refund_cost_points` decimal(10,3) default 0 COMMENT '副表消耗总积分 ' AFTER `refund_amount`;
ALTER TABLE kiosk.sale_orders_refund
  ADD COLUMN `refund_cost_points` decimal(10,3) default 0 COMMENT '退还积分 ' AFTER `refund_amount`;
  
#20260209
ALTER TABLE kiosk.machine_config ADD points_type int 0 COMMENT '会员积分版本;0-无会员无卡     1-会员无卡   2-会员有卡' after `pay_type`;



#20260123
ALTER TABLE kiosk.wc_goods 
  ADD get_data TEXT NULL COMMENT '接口返回内容';

ALTER TABLE kiosk.wc_goods 
  ADD COLUMN goods text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL COMMENT '实物商品信息';

ALTER TABLE kiosk.wc_goods MODIFY COLUMN order_limit_config text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL COMMENT '';
ALTER TABLE kiosk.wc_goods MODIFY COLUMN description text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL COMMENT '商品简介';
ALTER TABLE kiosk.wc_goods MODIFY COLUMN daysInfo text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL COMMENT '日历库存列表';

CREATE TABLE `wc_goods_local` (
  `g_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '商品ID',
  `out_no` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '外层商品编码',
  `no` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '商品编码',
  `g_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '商品名称',
  `gc_id` int(11) DEFAULT '0' COMMENT '分类ID',
  `gc_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '分类名称',
  `g_type` tinyint(1) DEFAULT '1' COMMENT '商品类型，1抢购、2景点门票、3酒店住宿、4旅游线路、5普通商品',
  `g_type_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '商品类型描述',
  `model` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '型号',
  `bar_code` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '条形码',
  `sku` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'SKU码',
  `sku2` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '关联SKU，关联不同包装形式的同种商品SKU，用于库存盘点中计算同种商品总库存',
  `pic` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '商品图片',
  `banner` varchar(2500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '轮播图片，;隔开文件地址',
  `cost_price` decimal(10,2) DEFAULT '0.00' COMMENT '成本价',
  `market_price` decimal(10,2) DEFAULT '0.00' COMMENT '市场价',
  `retail_price` decimal(10,2) DEFAULT '0.00' COMMENT '零售价',
  `intergral_rate` decimal(10,3) DEFAULT '0.000' COMMENT '积分-现金兑换比例（1元=10积分） ',
  `gift_points` decimal(10,3) DEFAULT '0.000' COMMENT '赠送积分',
  `manufacturer` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '生产商',
  `service_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '商家电话',
  `details_pic` varchar(2500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '详情图片，【;】隔开',
  `desc` longtext COLLATE utf8mb4_unicode_ci COMMENT '简介',
  `performance` text COLLATE utf8mb4_unicode_ci COMMENT '性能参数',
  `sell_channel` tinyint(1) DEFAULT '1' COMMENT '销售渠道，1：机器 ，2：微商城，3：机器+商城',
  `exter_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '购买外部链接',
  `expire_notice` tinyint(1) DEFAULT '0' COMMENT '商品有效期提醒，0：否，大于0为提前X天提醒，单位：天',
  `is_gift` tinyint(1) DEFAULT '2' COMMENT '赠品，1：是，2：否',
  `is_recommend` tinyint(1) DEFAULT '2' COMMENT '是否推荐商品，1：是，2：否',
  `recoverable` tinyint(1) DEFAULT '2' COMMENT '可回收，1：是，2：否',
  `heat` tinyint(4) DEFAULT '0' COMMENT '加热，0：不加热，大于0为档位，1~7档',
  `release_time` int(11) DEFAULT NULL COMMENT '发售时间',
  `length` int(11) DEFAULT '0' COMMENT '长度，单位：毫米',
  `width` int(11) DEFAULT '0' COMMENT '宽度，单位：毫米',
  `height` int(11) DEFAULT '0' COMMENT '高度，单位：毫米',
  `group_quantity` int(11) DEFAULT '0' COMMENT '单组数量',
  `sale_amount` decimal(10,3) DEFAULT '0.000' COMMENT '销售额',
  `sale_num` int(11) DEFAULT '0' COMMENT '销量',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态，1：启用，2：禁用',
  PRIMARY KEY (`g_id`) USING BTREE,
  KEY `c_id` (`gc_id`) USING BTREE,
  KEY `g_name` (`g_name`) USING BTREE,
  KEY `bar_code` (`bar_code`) USING BTREE,
  KEY `sku` (`sku`) USING BTREE,
  KEY `sku2` (`sku2`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='微程商品表本地化';

#20260121

ALTER TABLE kiosk.wc_goods MODIFY COLUMN show_citys text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL COMMENT '展示城市';

ALTER TABLE kiosk.wc_goods ADD is_pub TINYINT NULL COMMENT '是否上架：1是0否';
ALTER TABLE kiosk.wc_goods ADD tempcolumn varchar(100) NULL;
ALTER TABLE kiosk.wc_goods ADD temprownumber varchar(100) NULL;


CREATE TABLE `wc_goods_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL COMMENT '分类名称',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='微程商品分类表';

INSERT INTO wc_goods_types
(id, name, created_at, updated_at)
VALUES(1, '抢购', '2026-01-21 10:21:14', '2026-01-21 10:21:14');
INSERT INTO wc_goods_types
(id, name, created_at, updated_at)
VALUES(2, '‌景点门票', '2026-01-21 10:21:14', '2026-01-21 10:21:14');
INSERT INTO wc_goods_types
(id, name, created_at, updated_at)
VALUES(3, '酒店住宿', '2026-01-21 10:21:14', '2026-01-21 10:21:14');
INSERT INTO wc_goods_types
(id, name, created_at, updated_at)
VALUES(4, '旅游线路', '2026-01-21 10:21:14', '2026-01-21 10:21:14');
INSERT INTO wc_goods_types
(id, name, created_at, updated_at)
VALUES(5, '普通商品', '2026-01-21 10:21:14', '2026-01-21 10:21:14');
INSERT INTO wc_goods_types
(id, name, created_at, updated_at)
VALUES(11, '组合产品', '2026-01-21 10:21:14', '2026-01-21 10:21:14');

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


#20251230
CREATE TABLE `remote_action_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `machine_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '设备id',
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '消息类型',
  `order_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '订单id',
  `sod_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '订单子表id',
  `goods_id` int DEFAULT NULL COMMENT '商品id',
  `channel_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '货道编号',
  `status` int NOT NULL DEFAULT '1' COMMENT '状态： 1-已发送，2 -已接收命令，3-操作成功，4-操作失败',
  `operator_at` timestamp NOT NULL COMMENT '每个状态记录时间',
  `manager_id` int DEFAULT NULL COMMENT '操作人id',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='远程操作记录表';

ALTER TABLE kiosk.sale_orders_details
  ADD COLUMN `remote_out_goods_status` int default 0 COMMENT '远程出货状态：1-已发出货指令 2-已接收出货指令  3-出货成功 4-出货失败' AFTER `fail_quantity`;
ALTER TABLE kiosk.sale_orders_details
  ADD COLUMN `remote_out_goods_video` varchar(200) default NULL COMMENT '远程出货视频 ' AFTER `remote_out_goods_status`;

ALTER TABLE kiosk.sale_orders_details
  ADD COLUMN `refund_photo` varchar(250) default NULL COMMENT '远程退货拍照' AFTER `refund_quantity`;

ALTER TABLE kiosk.machine
  ADD COLUMN `recycle_box_total_capacity` int default 0 COMMENT '回收箱容量' AFTER `version`;
ALTER TABLE kiosk.machine
  ADD COLUMN `recycle_box_remain_capacity` int default 0 COMMENT '回收箱当前可用容量' AFTER `recycle_box_total_capacity`;


UPDATE kiosk.wx_template
SET  template_id='frqumju8oA7N8msUrhIiHkY6Gu1wgiGNq_Oo5TxjYsY', body='[{"设备编号":{"value":"{{machine_id}}","field":"character_string47"}},{"设备名称":{"value":"{{machine_name}}","field":"thing6"}},{"异常时间":{"value":"{{error_time}}","field":"time15"}},{"异常现象":{"value":"{{error_info}}","field":"thing12"}},{"异常类型":{"value":"{{error_code}}","field":"phrase13"}}]' 
WHERE template_type='understock';

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
SET  template_id='frqumju8oA7N8msUrhIiHkY6Gu1wgiGNq_Oo5TxjYsY', body='[{"设备编号":{"value":"{{machine_id}}","field":"character_string47"}},{"设备名称":{"value":"{{machine_name}}","field":"thing6"}},{"异常时间":{"value":"{{error_time}}","field":"time15"}},{"异常现象":{"value":"{{error_info}}","field":"thing12"}},{"异常类型":{"value":"{{error_code}}","field":"phrase13"}}]' 
WHERE template_type='mFault';

UPDATE kiosk.wx_template
SET  template_id='frqumju8oA7N8msUrhIiHsm0NQQEj3kZOgdMVnkfy-4', body='[{"设备编号":{"value":"{{machine_id}}","field":"character_string47"}},{"设备名称":{"value":"{{machine_name}}","field":"thing6"}},{"时间":{"value":"{{now}}","field":"time30"}},{"异常现象":{"value":"{{error_info}}","field":"thing12"}},{"异常类型":{"value":"{{error_code}}","field":"phrase13"}}]' 
WHERE template_type='tException';


UPDATE kiosk.wx_template
SET  template_id='frqumju8oA7N8msUrhIiHvilI4ayV3u6q4TKEbCYTtM', body='[{"设备编号":{"value":"{{machine_id}}","field":"character_string16"}},{"设备名称":{"value":"{{machine_name}}","field":"thing6"}},{"时间":{"value":"{{now}}","field":"time15"}},{"异常现象":{"value":"{{error_info}}","field":"thing12"}},{"异常类型":{"value":"{{error_code}}","field":"phrase13"}}]' 
WHERE template_type='sale';

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

#20260309
CREATE TABLE `machine_level_desc` (
  `machine_level` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '等级名称',
  `pic` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '等级图片' ,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`machine_level`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='设备等级表';

#20260309
INSERT INTO `machine_level_desc` (`machine_level`, `name`, `pic`)
VALUES
  (1, '简易版', ''),
  (2, '豪华版', ''),
  (3, '华屹机', ''),
  (4, '立式机', '');

#20260311
ALTER TABLE kiosk.machine_config
  ADD COLUMN `receipt_code1_desc` VARCHAR(50) DEFAULT '' COMMENT '二维码1的自定义文字' AFTER `receipt_code3`;

ALTER TABLE kiosk.machine_config
  ADD COLUMN `receipt_code2_desc` VARCHAR(50) DEFAULT '' COMMENT '二维码2的自定义文字' AFTER `receipt_code3`;
  
#20260312
ALTER TABLE kiosk.machine
  ADD COLUMN `vending_machine_type` tinyint(1) DEFAULT '1' COMMENT '售货机类型：1-主柜，2-弧柜，3-边柜' AFTER `device_type`;

#20260312
ALTER TABLE kiosk.machine_channel
  ADD COLUMN `old_retail_price` decimal(10,2) DEFAULT '-1.00' COMMENT '旧零售价' AFTER `retail_price`;
  ALTER TABLE kiosk.machine_channel
  ADD COLUMN `old_gift_points` decimal(10,3) DEFAULT '-1.000' COMMENT '旧赠送积分' AFTER `gift_points`;
  ALTER TABLE kiosk.machine_channel
  ADD COLUMN `old_stock_warning` int(10) DEFAULT '-1' COMMENT '旧库存预警' AFTER `stock_warning`;

#20260316
INSERT INTO `wx_template` (`wx_id`, `template_name`, `template_type`, `template_id`, `ao_id`,`miniprogram`, `body`,`status`, `create_time`, `update_time`)
VALUES
  (3, '设备自动售卖成功通知','payment_success','5uXcNNLJWe4Pr8X_ciZ_6vOGNb5625d25DyTtRSBYHI','1','{"appid":"","pagepath":""}', '[{"设备编号":{"value":"{{machine_id}}","field":"character_string1"}},{"设备名称":{"value":"{{machine_name}}","field":"thing8"}},{"订单编号":{"value":"{{trade_no}}","field":"character_string6"}},{"金额":{"value":"{{total_price}}","field":"amount7"}},{"时间":{"value":"{{pay_time}}","field":"time5"}}]',1,1773653091,1773653091);

#20260318
ALTER TABLE kiosk.machine_config ADD gate_detection tinyint(1) NOT NULL DEFAULT 2 COMMENT '门控检测：1开 2关闭' after `backsweeper`;
ALTER TABLE kiosk.machine_config ADD COLUMN `receipt_code3_desc` VARCHAR(50) DEFAULT '' COMMENT '二维码3的自定义文字' AFTER `receipt_code3`;

#20260319
ALTER TABLE kiosk.machine_channel
ADD COLUMN `channel_name` VARCHAR(50) DEFAULT '' COMMENT '货道名称' AFTER `channel_code`;

CREATE TABLE `card_balance_change_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `card_no` varchar(20) NOT NULL DEFAULT '' COMMENT '卡号',
  `balance_before_change` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '变化前余额',
  `balance_changed` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '余额变化量',
  `balance` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '变化后余额',
  `change_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '变化类型1：增加 2：减少',
  `balance_type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '变化类型1：购物消费 2：后台充值 3：提现 4：退款 5：充值到积分 6：活动赠送',
  `trade_no` varchar(50) DEFAULT NULL COMMENT '余额变化关联订单编号',
  `activity_id` int(11) NOT NULL DEFAULT 0 COMMENT '活动ID(预留)',
  `expire_at` bigint(20) NOT NULL DEFAULT 0 COMMENT '有效期时间戳，0为永久',
  `reasons` varchar(200) DEFAULT NULL COMMENT '原因',
  `remark` varchar(200) DEFAULT NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `card_no_IDX` (`card_no`) USING BTREE,
  KEY `trade_no_IDX` (`trade_no`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='卡余额变化表';

ALTER TABLE kiosk.card ADD COLUMN `balance` decimal(12,2) DEFAULT 0 COMMENT '卡余额' AFTER points;
ALTER TABLE kiosk.card ADD COLUMN `password` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '密码' AFTER points;
ALTER TABLE kiosk.card ADD COLUMN `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '余额卡激活状态' AFTER points;
ALTER TABLE kiosk.card ADD COLUMN `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '余额卡持有人姓名' AFTER points;
ALTER TABLE kiosk.card ADD COLUMN `activation_time` int(11) NOT NULL DEFAULT 0 COMMENT '余额卡激活时间' AFTER points;

#20260320
CREATE TABLE `machine_auxiliary` (
  `m_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `machine_id` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '设备编号',
  `main_m_id` int(10) NOT NULL DEFAULT '0' COMMENT '主柜m_id',
  `machine_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '设备名称',
  `machine_serial_number` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '设备序列号',
  `address` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '地址',
  `pic` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '图片',
  `mac_address` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '物理网卡地址',
  `machine_type` tinyint DEFAULT '1' COMMENT '设备类型：1弧柜 2边柜',
  `ao_id` int DEFAULT '0' COMMENT '归属组织ID',
  `status` tinyint(1) DEFAULT '2' COMMENT '1:已挂接已启用 2:未挂接未启用 3:已挂接未启用',
  `manager_id` int DEFAULT '0' COMMENT '管理员ID',
  `remark` varchar(200) DEFAULT '' COMMENT '备注',
  `bind_time` bigint(20) DEFAULT '0' COMMENT '挂接时间',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`m_id`),
  KEY `machine_id_IDX` (`machine_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='副柜信息表';

ALTER TABLE kiosk.machine_channel
ADD COLUMN `is_admin` TINYINT(1) DEFAULT 2 COMMENT '是否后台创建 1是 2否' AFTER `mg_id`;

#20260326
CREATE TABLE `activity_card_activation` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '卡激活活动ID',
  `pick_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '卡激活活动名称',
  `money` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '卡激活活动金额',
  `desc` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '活动描述',
  `start_time` int DEFAULT NULL COMMENT '开始时间',
  `end_time` int DEFAULT NULL COMMENT '结束时间',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态，1：未开始，2：进行中，3：已结束，4：主动下架',
  `ao_id` int DEFAULT '0' COMMENT '组织ID',
  `creator` int DEFAULT NULL COMMENT '创建人ID',
  `create_time` int DEFAULT NULL COMMENT '创建时间',
  `update_time` int DEFAULT NULL COMMENT '修改时间',
  PRIMARY KEY (`id`),
  KEY `start_time` (`start_time`) USING BTREE,
  KEY `end_time` (`end_time`) USING BTREE,
  KEY `status` (`status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='卡激活活动表';

CREATE TABLE `activity_card_activation_detail` (
  `acd_id` int NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `aca_id` int DEFAULT '0' COMMENT '活动ID',
  `money` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '实际激活时赠送的金额',
  `order_id` int DEFAULT NULL COMMENT '订单ID',
  `trade_no` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '订单编号',
  `card_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '卡号',
  `m_id` int DEFAULT NULL COMMENT '设备ID',
  `balance_log_id` bigint NOT NULL DEFAULT 0 COMMENT '余额日志ID', 
  `machine_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '设备编号',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态，1：未使用，2：已使用',
  `create_time` int DEFAULT NULL COMMENT '创建时间',
  `used_time` int DEFAULT NULL COMMENT '激活时间',
  PRIMARY KEY (`acd_id`),
  KEY `aca_id` (`aca_id`) USING BTREE,
  KEY `order_id` (`order_id`) USING BTREE,
  KEY `m_id` (`m_id`) USING BTREE,
  KEY `used_time` (`used_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='卡激活活动使用表';  (3, '设备自动售卖成功通知','payment_success','5uXcNNLJWe4Pr8X_ciZ_6vOGNb5625d25DyTtRSBYHI','1','{"appid":"","pagepath":""}', '[{"设备编号":{"value":"{{machine_id}}","field":"character_string1"}},{"设备名称":{"value":"{{machine_name}}","field":"thing8"}},{"订单编号":{"value":"{{trade_no}}","field":"character_string6"}},{"金额":{"value":"{{total_price}}","field":"amount7"}},{"时间":{"value":"{{pay_time}}","field":"time5"}}]',1,1773653091,1773653091);

#20260401
CREATE TABLE `card_balance_buckets` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `card_no` varchar(20) NOT NULL DEFAULT '' COMMENT '卡号',
  `batch_no` varchar(50) NOT NULL DEFAULT '' COMMENT '批次号(同一次充值本金和赠送关联)',
  `source_type` varchar(30) NOT NULL DEFAULT '' COMMENT '来源类型: recharge/gift/order_refund/refund',
  `source_no` varchar(50) NOT NULL DEFAULT '' COMMENT '来源单号',
  `amount_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '金额类型 1本金 2赠送',
  `refund_eligible` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否可退卡 1是 0否',
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '分笔总额',
  `remain_amount` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '分笔剩余额度',
  `expire_at` bigint(20) NOT NULL DEFAULT 0 COMMENT '有效期时间戳, 0为永久',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_card_no` (`card_no`) USING BTREE,
  KEY `idx_card_expire_remain` (`card_no`,`expire_at`,`remain_amount`) USING BTREE,
  KEY `idx_source_no` (`source_no`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='卡余额分笔表';  (3, '设备自动售卖成功通知','payment_success','5uXcNNLJWe4Pr8X_ciZ_6vOGNb5625d25DyTtRSBYHI','1','{"appid":"","pagepath":""}', '[{"设备编号":{"value":"{{machine_id}}","field":"character_string1"}},{"设备名称":{"value":"{{machine_name}}","field":"thing8"}},{"订单编号":{"value":"{{trade_no}}","field":"character_string6"}},{"金额":{"value":"{{total_price}}","field":"amount7"}},{"时间":{"value":"{{pay_time}}","field":"time5"}}]',1,1773653091,1773653091);

  #20260401
ALTER TABLE kiosk.auth_manager ADD audit_status INT DEFAULT 0 NULL COMMENT '是否有审核分账权限：0无1有'  AFTER status;

ALTER TABLE kiosk.wc_goods ADD COLUMN `gift_points` decimal(10,3) DEFAULT 0 COMMENT '微程赠送积分' AFTER `no`;
ALTER TABLE kiosk.wc_goods_local ADD COLUMN `gift_points` decimal(10,3) DEFAULT 0 COMMENT '微程赠送积分' AFTER `intergral_rate`;


ALTER TABLE kiosk.machine_goods ADD ao_id int NULL after `machine_id`;
update machine_goods a set a.ao_id = (select b.ao_id from machine b where b.m_id = a.m_id);


ALTER TABLE kiosk.sale_orders_revenue ADD ao_id INT NULL COMMENT '组织id'AFTER m_id;
ALTER TABLE kiosk.sale_orders_details ADD ao_id INT DEFAULT 0 COMMENT '组织id' AFTER sod_id;

update kiosk.sale_orders_details a set a.ao_id = (select b.ao_id from sale_orders b where a.order_id = b.ao_id) where a.ao_id  = 0 ;

ALTER TABLE kiosk.strategy_manager ADD m_id int NULL COMMENT '设备id'  AFTER s_id;


CREATE TABLE `auth_org_machine_channel` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ao_id` int DEFAULT NULL COMMENT '组织id',
  `m_id` int DEFAULT NULL COMMENT '设备m_id',
  `machine_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL COMMENT '设备编码',
  `channel_code` text COMMENT '货道编码',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `auth_org_machine_channel_unique` (`ao_id`,`m_id`,`machine_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='组织租赁货道信息表';

CREATE TABLE `auth_withdraw_requests` (
  `wr_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '提现申请ID',
  `ao_id` int(11) NOT NULL COMMENT '组织ID',
  `requester_manager_id` int(11) DEFAULT NULL COMMENT '申请人管理员ID',
  `amount` decimal(10,2) NOT NULL COMMENT '提现金额',
  `account` varchar(255) DEFAULT NULL COMMENT '提现账号（银行卡/支付宝/微信/京东分账账户）',
  `account_type` varchar(50) DEFAULT 'bank' COMMENT '账户类型 bank/alipay/wechat/jd_account',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态：1-待审核，2-通过，3-拒绝',
  `manager_id` int(11) DEFAULT NULL COMMENT '审核人管理员ID',
  `remark` varchar(500) DEFAULT NULL COMMENT '备注/审核意见',
  `creator` int(11) DEFAULT NULL COMMENT '创建人',
  `created_at`  timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`wr_id`),
  KEY `idx_ao_id` (`ao_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='组织提现申请表';


CREATE TABLE `auth_org_revenue_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '组织分账日志ID',
  `ao_id` int(11) NOT NULL COMMENT '组织ID',
  `order_id` varchar(50) DEFAULT NULL COMMENT '订单ID',
  `m_id` int(11) DEFAULT NULL COMMENT '设备ID',
  `machine_name` varchar(200) DEFAULT NULL COMMENT '机器名称',
  `machine_id` varchar(50) DEFAULT NULL COMMENT '设备编码',
  `order_amount` decimal(10,2) DEFAULT 0.00 COMMENT '订单金额',
  `sod_id` varchar(50) DEFAULT NULL COMMENT '子订单ID',
  `sod_amount` decimal(10,2) DEFAULT 0.00 COMMENT '子订单单价',
  `sod_quantity` int(11) DEFAULT 0 COMMENT '子订单数量',
  `sod_total_price` decimal(10,2) DEFAULT 0.00 COMMENT '子订单总价',
  `sp_id` int(11) DEFAULT NULL COMMENT '策略ID',
  `si_id` int(11) DEFAULT NULL COMMENT '分润策略ID',
  `income_value` decimal(10,3) DEFAULT 0.000 COMMENT '分润比例(%)或固定值',
  `revenue_type` tinyint(1) DEFAULT 0 COMMENT '分账方式(参照revenue_type)',
  `income_amount` decimal(10,2) DEFAULT 0.00 COMMENT '应分账金额',
  `remark` varchar(500) DEFAULT NULL COMMENT '备注',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ao_id` (`ao_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_si_id` (`si_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='组织分账日志表';

#20260409
CREATE TABLE `sale_orders_exception` (
  `id` bigint NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `order_id` int DEFAULT NULL COMMENT '订单ID',
  `sod_id` int DEFAULT '0' COMMENT '订单副表ID',
  `m_id` int DEFAULT 0 COMMENT '设备ID',
  `manager_id` int DEFAULT 0 COMMENT '处理人ID',
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '备注信息',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态，1. 已处理，2. 未处理',
  `create_time` int DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`) USING BTREE,
  KEY `sod_id` (`sod_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='异常订单处理表';
  
#20260331.15:38
CREATE TABLE `machine_calibration_config` (
  `id` int NOT NULL AUTO_INCREMENT,
  `m_id` int NOT NULL DEFAULT 0 COMMENT '设备m_id' ,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '名称',
  `machine_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '设备的machine_id' ,
  `version` int NOT NULL DEFAULT 0 COMMENT '配置的版本',
  `key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '配置的键',
  `value` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '配置的值',
  `value_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'string' COMMENT '配置的值类型: string, int, float, bool',
  `desc` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '配置的描述',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  key `m_id` (`m_id`) USING BTREE,
  key `machine_id` (`machine_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='设备校准页配置表';


#20260410~19:20
CREATE TABLE `topic_page` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `top_logo` varchar(255) NOT NULL DEFAULT '' COMMENT '顶部logo',
  `bg_url` varchar(255) NOT NULL DEFAULT '' COMMENT '背景图',
  `maintenance_bg` varchar(255) NOT NULL DEFAULT '' COMMENT '维护页背景图',
  `error_url` varchar(255) NOT NULL DEFAULT '' COMMENT '错误页背景图',
  `closed_url` varchar(255) NOT NULL DEFAULT '' COMMENT '暂停营业背景图',
  `verification_url` varchar(255) NOT NULL DEFAULT '' COMMENT '核销背景图',
  `pickup_url` varchar(255) NOT NULL DEFAULT '' COMMENT '取货页广告图',
  `shipping_url` varchar(255) NOT NULL DEFAULT '' COMMENT '出货页广告图',
  `pickup_qrcode_1` varchar(255) NOT NULL DEFAULT '' COMMENT '出/取货图片1',
  `pickup_qrcode_2` varchar(255) NOT NULL DEFAULT '' COMMENT '出/取货图片2',
  `scan_url` varchar(255) NOT NULL DEFAULT '' COMMENT '支付页反扫图',
  `qr_code_url` varchar(255) NOT NULL DEFAULT '' COMMENT '支付页扫码图',
  `balance_url` varchar(255) NOT NULL DEFAULT '' COMMENT '支付页余额支付图',
  `card_url` varchar(255) NOT NULL DEFAULT '' COMMENT '支付页刷卡图',
  `manager_id` int(11) NOT NULL DEFAULT 0 COMMENT '管理员ID',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态 1启用 2禁用',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_manager_id` (`manager_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='主题页表';

CREATE TABLE `topic_page_machine` (
  `topic_id` int(11) NOT NULL DEFAULT 0 COMMENT '主题页ID',
  `m_id` int(11) NOT NULL DEFAULT 0 COMMENT '设备m_id',
  `machine_id` varchar(255) NOT NULL DEFAULT '' COMMENT '设备编码',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_topic_id` (`topic_id`) USING BTREE,
  KEY `idx_m_id` (`m_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='主题页分配设备表';

ALTER TABLE kiosk.machine_config ADD `raster_state` tinyint(1) NOT NULL DEFAULT 1 COMMENT '取货后是否开启光栅检测：1开 2关闭' after `backsweeper`;

ALTER TABLE kiosk.machine_config ADD `raster_delay_time` int NOT NULL DEFAULT 5 COMMENT '光栅检测延迟时间，单位秒' after `raster_state`;
ALTER TABLE kiosk.machine_config ADD `discharge_camera_check` tinyint(1) DEFAULT '2' COMMENT '初始化是否跳过出料口摄像头，1-跳过，2-不跳过',
ALTER TABLE kiosk.machine_config ADD  `internal_camera_check` tinyint(1) DEFAULT '2' COMMENT '初始化是否跳过内部摄像头，1-跳过，2-不跳过',

#20260408--设备在营标记
ALTER TABLE kiosk.machine
  ADD COLUMN `is_operating` tinyint(1) NOT NULL DEFAULT 2 COMMENT '在营状态，1-在营 2-在库3-外售' AFTER `status`;
ALTER TABLE kiosk.sale_orders ADD http_out_status TINYINT DEFAULT 1 NULL COMMENT 'http出货状态：1-已发送，2 -已接收命令，3-操作成功，4-操作失败' after `out_status` ;


ALTER TABLE kiosk.sale_orders_details
  ADD COLUMN `remote_refund_status` int default 0 COMMENT '远程退货状态：0-未退货 1-已退货' AFTER `refund_photo`;

ALTER TABLE kiosk.sale_orders_details
  ADD COLUMN `remote_refund_audit_manager` int default 0 COMMENT '远程退货执行人' AFTER `refund_photo`;

  update wx_template set body = '[{"设备编号":{"value":"{{machine_id}}","field":"character_string16"}},{"设备名称":{"value":"{{machine_name}}","field":"thing6"}},{"异常时间":{"value":"{{error_time}}","field":"time15"}},{"异常现象":{"value":"{{error_info}}","field":"thing12"}},{"设备地址":{"value":"{{error_code}}","field":"thing9"}}]' , template_id = 'frqumju8oA7N8msUrhIiHpDd18j2Ie-DxLGlz5jWz8g' where wt_id =5;



#20260511 因为本次更新内容较多：需要先备份sale_orders，sale_orders_details表，同时需要定义以下几个定时任务：
1.每天0点1分定时同步物联卡流量数据：php /app/kiosk/backend/think time_task machine updateSimCardUsage
2.每隔5分钟执行，检查在营设备未开机的设备：php /app/kiosk/backend/think time_task machine checkOperatingStartup


#20260418 订单分类能力
ALTER TABLE kiosk.sale_orders
  ADD COLUMN `pay_channel` tinyint(1) NOT NULL DEFAULT '0' COMMENT '订单分类渠道' AFTER `pay_method`,
  ADD COLUMN `pay_channel_name` varchar(50) NOT NULL DEFAULT '' COMMENT '订单分类名称快照' AFTER `pay_channel`;


ALTER TABLE kiosk.machine MODIFY COLUMN recycle_box_remain_capacity INTEGER DEFAULT -1 NULL COMMENT '回收箱当前可用容量';

ALTER TABLE kiosk.remote_action_log ADD msgType varchar(100) NULL COMMENT 'mq类型' AFTER channel_code;
ALTER TABLE kiosk.machine ADD COLUMN `http_online` tinyint(1) NOT NULL DEFAULT 2 COMMENT 'HTTP心跳在线：1在线 2离线' AFTER `online`;

ALTER TABLE kiosk.remote_action_log
  ADD COLUMN `field` varchar(250) DEFAULT NULL COMMENT '图片地址' AFTER `manager_id`;

update machine set country_id = 44, state_id = 53, city_id = 683, regions_id = 1791 where is_operating  = 2 or (is_operating  = 1 and  street like '%东莞%') ;

ALTER TABLE kiosk.machine_config ADD  `remote_calibration` tinyint(1) DEFAULT '2' COMMENT '是否开启远程校准功能，1-开启，2-关闭';
ALTER TABLE kiosk.machine_config ADD  `head_camera_check` tinyint(1) DEFAULT '2' COMMENT '初始化是否跳过头部摄像头，1-跳过，2-不跳过';


ALTER TABLE kiosk.wx_template_log
    ADD COLUMN `me_id` bigint DEFAULT 0 COMMENT '错误ID' AFTER `remark`,
    ADD COLUMN `send_status` tinyint(1) DEFAULT 2 COMMENT '发送状态：1-发送成功，2-发送失败' AFTER `remark`,
    ADD COLUMN `confirm_status` tinyint(1) DEFAULT 2 COMMENT '确认状态：1-已确认，2-未确认' AFTER `remark`,
    ADD COLUMN `error_code` varchar(20) DEFAULT '' COMMENT '错误码' AFTER `remark`,
    ADD COLUMN `m_id` int DEFAULT 0 COMMENT '设备ID' AFTER `remark`,
    ADD COLUMN `confirm_time` bigint DEFAULT 0 COMMENT '确认时间' AFTER `remark`;

CREATE INDEX idx_me_id ON kiosk.wx_template_log(me_id);
CREATE INDEX idx_m_id ON kiosk.wx_template_log(m_id);

update wx_template set body = '[{"设备编号":{"value":"{{machine_id}}","field":"character_string1"}},{"设备名称":{"value":"{{machine_name}}","field":"thing8"}},{"订单编号":{"value":"{{trade_no}}","field":"character_string6"}},{"金额":{"value":"{{total_price}}","field":"amount7"}},{"时间":{"value":"{{pay_time}}","field":"time5"}}]',template_id = '5uXcNNLJWe4Pr8X_ciZ_6vOGNb5625d25DyTtRSBYHI'  where wt_id =9;

alter table kiosk.machine_goods add column `auto_refund` tinyint(1) default 2 comment '是否自动退款1是 2否' after `is_shelf`;

ALTER TABLE kiosk.machine_config ADD `automatic_goods_sorting` tinyint(1) DEFAULT '1' COMMENT '是否开启自动理货1开启2关闭' after `gate_detection`;


#20260419--在营设备2小时在线状态快照
CREATE TABLE `machine_online_snapshot` (
  `mos_id` int(11) NOT NULL AUTO_INCREMENT,
  `m_id` int(11) NOT NULL DEFAULT 0 COMMENT '设备ID',
  `machine_id` varchar(50) NOT NULL DEFAULT '' COMMENT '设备编号',
  `machine_name` varchar(100) NOT NULL DEFAULT '' COMMENT '设备名称',
  `record_date` int(11) NOT NULL DEFAULT 0 COMMENT '统计日期（当天0点时间戳）',
  `collect_time` int(11) NOT NULL DEFAULT 0 COMMENT '采集时间',
  `slot_start_time` int(11) NOT NULL DEFAULT 0 COMMENT '2小时槽位开始时间',
  `slot_end_time` int(11) NOT NULL DEFAULT 0 COMMENT '2小时槽位结束时间',
  `online` tinyint(1) NOT NULL DEFAULT 2 COMMENT '在线状态：1在线 2离线',
  `is_operating` tinyint(1) NOT NULL DEFAULT 2 COMMENT '在营状态：1在营 2在库 3外售',
  `ckc_status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '营业状态：1正常营业 2暂停营业',
  `business_start_time` int(11) NOT NULL DEFAULT 0 COMMENT '营业开始秒数',
  `business_end_time` int(11) NOT NULL DEFAULT 86399 COMMENT '营业结束秒数',
  `ao_id` int(11) NOT NULL DEFAULT 0 COMMENT '组织ID',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`mos_id`),
  UNIQUE KEY `uniq_machine_day_slot` (`machine_id`,`record_date`,`slot_start_time`) USING BTREE,
  KEY `idx_m_id` (`m_id`) USING BTREE,
  KEY `idx_collect_time` (`collect_time`) USING BTREE,
  KEY `idx_ao_id` (`ao_id`) USING BTREE,
  KEY `idx_online` (`online`) USING BTREE,
  KEY `idx_is_operating` (`is_operating`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='在营设备2小时在线状态快照表';


CREATE TABLE `sim_card_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `m_id` int(11) NOT NULL DEFAULT 0 COMMENT '设备ID',
  `machine_id` varchar(50) NOT NULL DEFAULT '' COMMENT '设备编码',
  `iccid` varchar(50) NOT NULL DEFAULT '' COMMENT '物联卡id',
  `carrier` varchar(50) NOT NULL DEFAULT '' COMMENT '运营商类型:china_telecom:电信、china_mobile:移动、china_unicom:联通、china_broadnet:广电、international_carrier:国际、mix_carrier:融合',
  `carrier_id` int(11) NOT NULL DEFAULT 0 COMMENT '运营商ID',
  `msisdn` varchar(50) NOT NULL DEFAULT '' COMMENT '手机号码',
  `imsi` varchar(255) NOT NULL DEFAULT '' COMMENT 'imsi',
  `allocated_at` datetime NULL COMMENT '分配时间',
  `silent_period_end_date` datetime NULL COMMENT '沉默期截止时间',
  `activated_time` datetime NULL COMMENT '激活时间',
  `service_end_time` datetime NULL COMMENT '服务结束时间',
  `expect_cancel_time` datetime NULL COMMENT '预计销卡时间',
  `life_cycle` tinyint(1) NOT NULL DEFAULT 0 COMMENT '生命周期:0库存、1沉默期、2可用、3待续期订购、4待销卡、5已销卡',
  `network_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '联网状态:0正常、1强制断网、2客户断网、3超套停、4服务结束、5提请销卡、6销卡、7超池停',
  `imei` varchar(50) NOT NULL DEFAULT '' COMMENT '最近使用设备imei',
  `device_card_status` varchar(20) NOT NULL DEFAULT '' COMMENT '机卡分离状态',
  `power_status` tinyint(1) NOT NULL DEFAULT 2 COMMENT '开关机状态 0关机 1开机 2未知',
  `online_status` tinyint(1) NOT NULL DEFAULT 2 COMMENT '在线状态 0离线 1在线 2未知',
  `business_type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '业务模式:0自定义自然月、1自定义天数、2流量共享、3流量共享(总池)、4空白卡',
  `number` varchar(50) NOT NULL DEFAULT '' COMMENT '套餐编号',
  `title` varchar(50) NOT NULL DEFAULT '' COMMENT '套餐名称',
  `service_period` int NOT NULL DEFAULT 0 COMMENT '套餐周期时长',
  `service_period_type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '套餐周期时长单位:0自然月、1天',
  `package_capacity` decimal(18,2) NOT NULL DEFAULT 0.00 COMMENT '套餐周期容量',
  `capacity_type` varchar(20) NOT NULL DEFAULT '' COMMENT '容量单位:mb(兆字节)、gb(千兆字节)、(count)次',
  `voice_capacity` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '周期内语音时长（分钟）',
  `subscribed_time` datetime NULL COMMENT '套餐订购时间',
  `start_time` datetime NULL COMMENT '套餐开始时间',
  `end_time` datetime NULL COMMENT '套餐结束时间',
  `periods` int(11) NOT NULL DEFAULT 0 COMMENT '周期数',
  `period_list` varchar(50) NOT NULL DEFAULT '' COMMENT '周期系列。例如:2/3,一共3个周期,目前处于第2个周期',
  `current_period_begin_time` datetime NULL COMMENT '套餐周期生效时间',
  `current_period_end_time` datetime NULL COMMENT '套餐周期失效时间间',
  `current_period_usage` decimal(18,2) NOT NULL DEFAULT 0.00 COMMENT '当前周期已用量。流量:KB,次数:次',
  `current_period_voice_usage` decimal(18,2) NOT NULL DEFAULT 0.00 COMMENT '周期内已用语音时长（分钟）',
  `future_package_count` int NOT NULL DEFAULT 0 COMMENT '后续套餐个数',
  `future_cycle_count` int NOT NULL DEFAULT 0 COMMENT '后续周期个数',
  `created_at` datetime NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_iccid` (`iccid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='物联卡信息表';


CREATE TABLE `sim_card_machine` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `m_id` int(11) NOT NULL DEFAULT 0 COMMENT '设备ID',
  `machine_id` varchar(50) NOT NULL DEFAULT '' COMMENT '设备编码',
  `iccid` varchar(50) NOT NULL DEFAULT '' COMMENT '物联卡id',
  `date` date default NULL COMMENT '使用日期',
  `total_usage` decimal(18,2) NOT NULL DEFAULT 0.00 COMMENT '卡累计使用量,单位kb,用于计算两日之间的差值',
  `usage` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '卡使用量,单位kb',
  `machine_usage` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '设备使用量,单位kb(软件)',
  `camera_usage` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '摄像头使用量,单位kb',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `created_at` datetime NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_iccid` (`iccid`) USING BTREE,
  KEY `idx_m_id` (`m_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='物联卡每日使用流量表';


CREATE TABLE `auth_manager_notice_config` (
  `id` bigint NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `manager_id` int not null DEFAULT 0 COMMENT '管理员id',
  `is_default` tinyint not null DEFAULT '0' COMMENT '是否默认通知配置，1是 2自定义',
  `interval_minutes` int not null DEFAULT '0' COMMENT '通知频率:分钟',
  `day_count` int DEFAULT 0 COMMENT '次数/天',
  `notice_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '备注信息',
  `create_time` bigint not null DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `manager_id` (`manager_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员通知配置表';

CREATE TABLE `sim_signal_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `m_id` int(11) NOT NULL DEFAULT 0 COMMENT '设备ID',
  `machine_id` varchar(50) NOT NULL DEFAULT '' COMMENT '设备编码',
  `iccid` varchar(50) NOT NULL DEFAULT '' COMMENT '物联卡id',
  `rsrp` int(11) NOT NULL DEFAULT 0 COMMENT '信号强度',
  `rsrp_level` tinyint(1) NOT NULL DEFAULT 0 COMMENT '信号强度等级',
  `sinr` int(11) NOT NULL DEFAULT 0 COMMENT '信噪比',
  `sinr_level` tinyint(1) NOT NULL DEFAULT 0 COMMENT '信噪比等级',
  `created_at` datetime NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_iccid` (`iccid`) USING BTREE,
  KEY `idx_m_id` (`m_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='物联卡实时信号表';


CREATE TABLE `machine_service_log` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '操作视频ID',
  `m_id` int DEFAULT 0 COMMENT '设备ID',
  `machine_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '设备编码',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '文件名称',
  `path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '文件路径',
  `date` date DEFAULT NULL COMMENT '日志日期',
  `remark` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT '文件备注',
  `create_time` bigint DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `machine_id` (`machine_id`) USING BTREE,
  KEY `m_id` (`m_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='设备运行日志表';



以下sql是对历史订单信息兼容新支付渠道字段的更新sql, 计划执行但暂不执行，待上线一周后观察订单及出货无误，同时确保新上线的支付渠道分类逻辑运行无误后，再确认执行

#20260418 历史订单分类回填（仅补分类，不改支付原字段）
UPDATE kiosk.sale_orders so
LEFT JOIN (
  SELECT
    order_id,
    MAX(
      CASE
        WHEN wc_order_no IS NULL OR wc_order_no = '' THEN 0
        ELSE 1
      END
    ) AS has_wc_order_no
  FROM kiosk.sale_orders_details
  GROUP BY order_id
) sod ON sod.order_id = so.order_id
SET
  so.pay_channel = CASE
    WHEN so.pay_type = 20 THEN 6
    WHEN IFNULL(so.total_cost_points, 0) > 0 THEN 4
    WHEN (IFNULL(so.gift_points, 0) > 0 OR IFNULL(so.total_points, 0) > 0) AND IFNULL(sod.has_wc_order_no, 0) = 0 THEN 3
    WHEN IFNULL(sod.has_wc_order_no, 0) = 1 THEN 1
    WHEN so.pay_type = 7 THEN 2
    WHEN so.order_type = 3 AND IFNULL(so.acp_id, 0) > 0 THEN 5
    WHEN so.pay_type IN (1, 11, 12) THEN 7
    WHEN so.pay_type IN (2, 21, 22) THEN 8
    WHEN so.pay_method IN (3, 4, 5) OR so.pay_type IN (4, 10, 33, 34, 35) THEN 9
    WHEN so.pay_method IN (6, 7) OR so.pay_type IN (36, 37) THEN 10
    ELSE 11
  END,
  so.pay_channel_name = CASE
    WHEN so.pay_type = 20 THEN '余额支付订单'
    WHEN IFNULL(so.total_cost_points, 0) > 0 THEN '商场积分订单'
    WHEN (IFNULL(so.gift_points, 0) > 0 OR IFNULL(so.total_points, 0) > 0) AND IFNULL(sod.has_wc_order_no, 0) = 0 THEN '售卖机会员积分订单'
    WHEN IFNULL(sod.has_wc_order_no, 0) = 1 THEN '微程小程序订单'
    WHEN so.pay_type = 7 THEN '机械车小程序订单'
    WHEN so.order_type = 3 AND IFNULL(so.acp_id, 0) > 0 THEN '取货码订单'
    WHEN so.pay_type IN (1, 11, 12) THEN '微信支付'
    WHEN so.pay_type IN (2, 21, 22) THEN '支付宝支付'
    WHEN so.pay_method IN (3, 4, 5) OR so.pay_type IN (4, 10, 33, 34, 35) THEN 'POS/刷卡支付'
    WHEN so.pay_method IN (6, 7) OR so.pay_type IN (36, 37) THEN '现金支付'
    ELSE '其他'
  END
WHERE IFNULL(so.pay_channel, 0) = 0
   OR IFNULL(so.pay_channel_name, '') = '';

#20260519
alter table machine_config add column machine_button_1 tinyint(1) default 2 comment '预留按钮1 1开启2关闭' after automatic_goods_sorting;
alter table machine_config add column machine_button_2 tinyint(1) default 2 comment '预留按钮2 1开启2关闭' after automatic_goods_sorting;
alter table machine_config add column machine_button_3 tinyint(1) default 2 comment '预留按钮3 1开启2关闭' after automatic_goods_sorting;
alter table machine_config add column machine_button_4 tinyint(1) default 2 comment '预留按钮4 1开启2关闭' after automatic_goods_sorting;
alter table machine_config add column machine_button_5 tinyint(1) default 2 comment '预留按钮5 1开启2关闭' after automatic_goods_sorting;

alter table topic_page add column `deal_success_title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '交易成功页方案，标题' after `manager_id`;
alter table topic_page add column `deal_success_sub_title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '交易成功页副标题' after `manager_id`;
alter table topic_page add column `deal_abnormal_pic` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '交易异常页图片' after `manager_id`;
alter table topic_page add column `deal_fail_title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '交易失败页方案，标题' after `manager_id`;
alter table topic_page add column `deal_fail_sub_title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '交易失败页副标题' after `manager_id`;
alter table topic_page add column `is_service_phone` tinyint(1)  DEFAULT 2 COMMENT '交易客服是否隐藏，1显示2隐藏' after `manager_id` ;
alter table topic_page add column `claim_goods_title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '取货页文案' after `manager_id`;
alter table topic_page add column `out_goods_title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '出货页文案' after `manager_id`;
alter table topic_page add column `pickup_qrcode_text1` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '出/取货二维码文案1' after `manager_id`;
alter table topic_page add column `pickup_qrcode_text2` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '出/取货二维码文案2' after `manager_id`;


ALTER TABLE kiosk.sale_orders_refund MODIFY COLUMN remark text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT '退款备注';

ALTER TABLE `machine_online_snapshot`
  MODIFY COLUMN `record_date` timestamp NOT NULL COMMENT '统计日期（当天0点）',
  MODIFY COLUMN `collect_time` timestamp NOT NULL COMMENT '采集时间',
  MODIFY COLUMN `slot_start_time` timestamp NOT NULL COMMENT '2小时槽位开始时间',
  MODIFY COLUMN `slot_end_time` timestamp NOT NULL COMMENT '2小时槽位结束时间',
  MODIFY COLUMN `business_start_time` timestamp NOT NULL COMMENT '营业开始时间',
  MODIFY COLUMN `business_end_time` timestamp NOT NULL COMMENT '营业结束时间',
  MODIFY COLUMN `create_time` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  MODIFY COLUMN `update_time` timestamp NULL DEFAULT NULL COMMENT '更新时间';

CREATE TABLE `maintenance_items` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '项目唯一ID',
  `parent_id` int DEFAULT NULL COMMENT '父项目ID',
  `item_name` varchar(255) NOT NULL COMMENT '项目名称',
  `item_level` tinyint NOT NULL DEFAULT '1' COMMENT '层级',
  `cycle_days` int DEFAULT NULL COMMENT '维护周期',
  `description` text COMMENT '描述',
  `sort_order` int DEFAULT '0' COMMENT '排序',
  `is_active` tinyint(1) DEFAULT '1' COMMENT '是否启用',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_item_level` (`item_level`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='维护项目清单表';

CREATE TABLE `maintenance_records` (
  `id` bigint NOT NULL AUTO_INCREMENT COMMENT '记录唯一ID',
  `records_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '操作记录编码',
  `item_id` int NOT NULL COMMENT '关联的项目ID',
  `machine_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL COMMENT '设备编号',
  `maintainer_id` varchar(50) NOT NULL COMMENT '维护人ID',
  `check_status` int NULL COMMENT '1-正常 2-异常';
  `maintenance_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '维护时间',
  `notes` text COMMENT '备注',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '入库时间',
  PRIMARY KEY (`id`),
  KEY `idx_item_id` (`item_id`),
  KEY `idx_maintainer_id` (`maintainer_id`),
  KEY `idx_maintenance_time` (`maintenance_time`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COMMENT='设备维护记录表';

CREATE TABLE `check_list_items` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '项目唯一ID',
  `parent_id` int DEFAULT NULL COMMENT '父项目ID',
  `item_name` varchar(255) NOT NULL COMMENT '项目名称',
  `item_level` tinyint NOT NULL DEFAULT '1' COMMENT '层级',
  `cycle_days` int DEFAULT NULL COMMENT '维护周期',
  `description` text COMMENT '描述',
  `sort_order` int DEFAULT '0' COMMENT '排序',
  `is_active` tinyint(1) DEFAULT '1' COMMENT '是否启用',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_item_level` (`item_level`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='小蜜蜂维护清单表';

CREATE TABLE `check_list_records` (
  `id` bigint NOT NULL AUTO_INCREMENT COMMENT '记录唯一ID',
  `records_code` varchar(20) CHARACTER SET utf8mb4  NOT NULL COMMENT '操作记录编码',
  `item_id` int NOT NULL COMMENT '关联的项目ID',
  `machine_id` varchar(50) CHARACTER SET utf8mb4  DEFAULT NULL COMMENT '设备编号',
  `manager_id` varchar(50) NOT NULL COMMENT '维护人ID',
  `check_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '维护时间',
  `check_status` int  DEFAULT NULL COMMENT '维护状态1-正常，2-异常',
  `notes` text COMMENT '备注',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '入库时间',
  PRIMARY KEY (`id`),
  KEY `idx_item_id` (`item_id`),
  KEY `idx_manager_id` (`manager_id`),
  KEY `idx_check_time` (`check_time`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='小蜜蜂设备维护记录表';

INSERT INTO check_list_items
(id, parent_id, item_name, item_level, description, sort_order, is_active, created_at, updated_at)
VALUES(1, NULL, '基础状态', 1, NULL, 1, 1, '2026-05-15 16:00:21', '2026-05-15 16:00:35');
INSERT INTO check_list_items
(id, parent_id, item_name, item_level, description, sort_order, is_active, created_at, updated_at)
VALUES(2, NULL, '商品陈列', 1, NULL, 2, 1, '2026-05-15 16:04:13', '2026-05-15 16:04:13');
INSERT INTO check_list_items
(id, parent_id, item_name, item_level, description, sort_order, is_active, created_at, updated_at)
VALUES(3, NULL, '核心功能', 1, NULL, 3, 1, '2026-05-15 16:04:30', '2026-05-15 16:04:37');
INSERT INTO check_list_items
(id, parent_id, item_name, item_level, description, sort_order, is_active, created_at, updated_at)
VALUES(4, 1, '电源与系统', 1, '机台已通电，屏幕点亮，购买销售界面正常', 4, 1, '2026-05-15 16:04:30', '2026-05-15 20:27:02');
INSERT INTO check_list_items
(id, parent_id, item_name, item_level, description, sort_order, is_active, created_at, updated_at)
VALUES(5, 1, '外观清洁', 1, '玻璃、触摸屏、无污渍、灰尘、指纹', 5, 1, '2026-05-15 16:04:30', '2026-05-15 20:27:02');
INSERT INTO check_list_items
(id, parent_id, item_name, item_level, description, sort_order, is_active, created_at, updated_at)
VALUES(6, 1, '活动物料', 1, '（1）活动内容在当期内，未过期；（2）摆放位置显眼合理，无明损坏', 6, 1, '2026-05-15 16:04:30', '2026-05-15 20:27:02');
INSERT INTO check_list_items
(id, parent_id, item_name, item_level, description, sort_order, is_active, created_at, updated_at)
VALUES(7, 1, '营业环境', 1, '地面干净，无纸屑、杂物、污渍等垃圾', 7, 1, '2026-05-15 16:04:30', '2026-05-15 20:27:02');
INSERT INTO check_list_items
(id, parent_id, item_name, item_level, description, sort_order, is_active, created_at, updated_at)
VALUES(8, 1, '机台皮肤', 1, '皮肤完好，关键重要正面位置无划痕、破损、变形', 8, 1, '2026-05-15 16:04:30', '2026-05-15 20:27:02');
INSERT INTO check_list_items
(id, parent_id, item_name, item_level, description, sort_order, is_active, created_at, updated_at)
VALUES(9, 1, '机台内部', 1, '无杂物堆积、无违规存放货品、物料等，整体干净卫生', 9, 1, '2026-05-15 16:04:30', '2026-05-15 20:27:02');
INSERT INTO check_list_items
(id, parent_id, item_name, item_level, description, sort_order, is_active, created_at, updated_at)
VALUES(10, 1, 'POP 海报/宣传图片', 1, '各类 POP 海报/宣传图片，确认无过期物料；有异常通知点位负责人协助处理', 10, 1, '2026-05-15 16:04:30', '2026-05-15 20:27:13');
INSERT INTO check_list_items
(id, parent_id, item_name, item_level, description, sort_order, is_active, created_at, updated_at)
VALUES(11, 1, '广告机', 1, '有电视的点位，需确保电视 100% 开机，大屏画面全屏露出，无画面不全、黑屏情况', 11, 1, '2026-05-15 16:04:30', '2026-05-15 20:27:13');
INSERT INTO check_list_items
(id, parent_id, item_name, item_level, description, sort_order, is_active, created_at, updated_at)
VALUES(12, 1, '机台美陈', 1, '检查电视周边及机台旁的 KT 板，确认无脱胶/掉落、损坏情况，若有问题需及反馈更换', 12, 1, '2026-05-15 16:04:30', '2026-05-15 20:27:21');
INSERT INTO check_list_items
(id, parent_id, item_name, item_level, description, sort_order, is_active, created_at, updated_at)
VALUES(13, 2, '主机价格签', 2, '每个商品均有价签，价签位置统一（对应产品正中间），价格与系统显示一致', 1, 1, '2026-05-15 16:04:30', '2026-05-15 20:27:29');
INSERT INTO check_list_items
(id, parent_id, item_name, item_level, description, sort_order, is_active, created_at, updated_at)
VALUES(14, 2, '库存情况', 2, '快速巡视，通报货道仅1的商品；同时，提前评估热销款及补货需求', 2, 1, '2026-05-15 16:04:30', '2026-05-15 20:27:39');
INSERT INTO check_list_items
(id, parent_id, item_name, item_level, description, sort_order, is_active, created_at, updated_at)
VALUES(15, 2, '展品陈列', 2, '机台边柜、弧柜出样商品需按标准陈列，无歪斜、无空货道', 3, 1, '2026-05-15 16:04:30', '2026-05-15 20:27:39');
INSERT INTO check_list_items
(id, parent_id, item_name, item_level, description, sort_order, is_active, created_at, updated_at)
VALUES(16, 2, '展柜/弧柜水牌', 2, '陈列品价格水牌是否都有且对应正确', 4, 1, '2026-05-15 16:04:30', '2026-05-15 20:27:57');
INSERT INTO check_list_items
(id, parent_id, item_name, item_level, description, sort_order, is_active, created_at, updated_at)
VALUES(17, 3, '商品界面', 3, '主机上架的产品，与机台的陈列匹配', 1, 1, '2026-05-15 16:04:30', '2026-05-15 20:28:01');
INSERT INTO check_list_items
(id, parent_id, item_name, item_level, description, sort_order, is_active, created_at, updated_at)
VALUES(18, 3, '价格设定', 3, '核对机台商品标价与实际收费是否一致，无错价、漏价情况', 2, 1, '2026-05-15 16:04:30', '2026-05-15 20:28:07');
INSERT INTO check_list_items
(id, parent_id, item_name, item_level, description, sort_order, is_active, created_at, updated_at)
VALUES(19, 3, '商品选购', 3, '模拟顾客选择商品，流程顺畅，界面无卡顿', 3, 1, '2026-05-15 16:04:30', '2026-05-15 20:28:16');
INSERT INTO check_list_items
(id, parent_id, item_name, item_level, description, sort_order, is_active, created_at, updated_at)
VALUES(20, 3, '支付测试', 3, '检查多种支付方式（如扫码、反扫等）是否正常，支付后能否顺利触发出货', 4, 1, '2026-05-15 16:04:30', '2026-05-15 20:28:16');

INSERT INTO check_list_items
(id, parent_id, item_name, item_level, description, sort_order, is_active, created_at, updated_at)
VALUES(1, NULL, '基础状态', 1, NULL, 1, 1, '2026-05-15 16:00:21', '2026-05-15 16:00:35');
INSERT INTO check_list_items
(id, parent_id, item_name, item_level, description, sort_order, is_active, created_at, updated_at)
VALUES(2, NULL, '商品陈列', 1, NULL, 2, 1, '2026-05-15 16:04:13', '2026-05-15 16:04:13');
INSERT INTO check_list_items
(id, parent_id, item_name, item_level, description, sort_order, is_active, created_at, updated_at)
VALUES(3, NULL, '核心功能', 1, NULL, 3, 1, '2026-05-15 16:04:30', '2026-05-15 16:04:37');
INSERT INTO check_list_items
(id, parent_id, item_name, item_level, description, sort_order, is_active, created_at, updated_at)
VALUES(4, 1, '电源与系统', 1, '机台已通电，屏幕点亮，购买销售界面正常', 4, 1, '2026-05-15 16:04:30', '2026-05-15 20:27:02');
INSERT INTO check_list_items
(id, parent_id, item_name, item_level, description, sort_order, is_active, created_at, updated_at)
VALUES(5, 1, '外观清洁', 1, '玻璃、触摸屏、无污渍、灰尘、指纹', 5, 1, '2026-05-15 16:04:30', '2026-05-15 20:27:02');
INSERT INTO check_list_items
(id, parent_id, item_name, item_level, description, sort_order, is_active, created_at, updated_at)
VALUES(6, 1, '活动物料', 1, '（1）活动内容在当期内，未过期；（2）摆放位置显眼合理，无明损坏', 6, 1, '2026-05-15 16:04:30', '2026-05-15 20:27:02');
INSERT INTO check_list_items
(id, parent_id, item_name, item_level, description, sort_order, is_active, created_at, updated_at)
VALUES(7, 1, '营业环境', 1, '地面干净，无纸屑、杂物、污渍等垃圾', 7, 1, '2026-05-15 16:04:30', '2026-05-15 20:27:02');
INSERT INTO check_list_items
(id, parent_id, item_name, item_level, description, sort_order, is_active, created_at, updated_at)
VALUES(8, 1, '机台皮肤', 1, '皮肤完好，关键重要正面位置无划痕、破损、变形', 8, 1, '2026-05-15 16:04:30', '2026-05-15 20:27:02');
INSERT INTO check_list_items
(id, parent_id, item_name, item_level, description, sort_order, is_active, created_at, updated_at)
VALUES(9, 1, '机台内部', 1, '无杂物堆积、无违规存放货品、物料等，整体干净卫生', 9, 1, '2026-05-15 16:04:30', '2026-05-15 20:27:02');
INSERT INTO check_list_items
(id, parent_id, item_name, item_level, description, sort_order, is_active, created_at, updated_at)
VALUES(10, 1, 'POP 海报/宣传图片', 1, '各类 POP 海报/宣传图片，确认无过期物料；有异常通知点位负责人协助处理', 10, 1, '2026-05-15 16:04:30', '2026-05-15 20:27:13');
INSERT INTO check_list_items
(id, parent_id, item_name, item_level, description, sort_order, is_active, created_at, updated_at)
VALUES(11, 1, '广告机', 1, '有电视的点位，需确保电视 100% 开机，大屏画面全屏露出，无画面不全、黑屏情况', 11, 1, '2026-05-15 16:04:30', '2026-05-15 20:27:13');
INSERT INTO check_list_items
(id, parent_id, item_name, item_level, description, sort_order, is_active, created_at, updated_at)
VALUES(12, 1, '机台美陈', 1, '检查电视周边及机台旁的 KT 板，确认无脱胶/掉落、损坏情况，若有问题需及反馈更换', 12, 1, '2026-05-15 16:04:30', '2026-05-15 20:27:21');
INSERT INTO check_list_items
(id, parent_id, item_name, item_level, description, sort_order, is_active, created_at, updated_at)
VALUES(13, 2, '主机价格签', 2, '每个商品均有价签，价签位置统一（对应产品正中间），价格与系统显示一致', 1, 1, '2026-05-15 16:04:30', '2026-05-15 20:27:29');
INSERT INTO check_list_items
(id, parent_id, item_name, item_level, description, sort_order, is_active, created_at, updated_at)
VALUES(14, 2, '库存情况', 2, '快速巡视，通报货道仅1的商品；同时，提前评估热销款及补货需求', 2, 1, '2026-05-15 16:04:30', '2026-05-15 20:27:39');
INSERT INTO check_list_items
(id, parent_id, item_name, item_level, description, sort_order, is_active, created_at, updated_at)
VALUES(15, 2, '展品陈列', 2, '机台边柜、弧柜出样商品需按标准陈列，无歪斜、无空货道', 3, 1, '2026-05-15 16:04:30', '2026-05-15 20:27:39');
INSERT INTO check_list_items
(id, parent_id, item_name, item_level, description, sort_order, is_active, created_at, updated_at)
VALUES(16, 2, '展柜/弧柜水牌', 2, '陈列品价格水牌是否都有且对应正确', 4, 1, '2026-05-15 16:04:30', '2026-05-15 20:27:57');
INSERT INTO check_list_items
(id, parent_id, item_name, item_level, description, sort_order, is_active, created_at, updated_at)
VALUES(17, 3, '商品界面', 3, '主机上架的产品，与机台的陈列匹配', 1, 1, '2026-05-15 16:04:30', '2026-05-15 20:28:01');
INSERT INTO check_list_items
(id, parent_id, item_name, item_level, description, sort_order, is_active, created_at, updated_at)
VALUES(18, 3, '价格设定', 3, '核对机台商品标价与实际收费是否一致，无错价、漏价情况', 2, 1, '2026-05-15 16:04:30', '2026-05-15 20:28:07');
INSERT INTO check_list_items
(id, parent_id, item_name, item_level, description, sort_order, is_active, created_at, updated_at)
VALUES(19, 3, '商品选购', 3, '模拟顾客选择商品，流程顺畅，界面无卡顿', 3, 1, '2026-05-15 16:04:30', '2026-05-15 20:28:16');
INSERT INTO check_list_items
(id, parent_id, item_name, item_level, description, sort_order, is_active, created_at, updated_at)
VALUES(20, 3, '支付测试', 3, '检查多种支付方式（如扫码、反扫等）是否正常，支付后能否顺利触发出货', 4, 1, '2026-05-15 16:04:30', '2026-05-15 20:28:16');
#20260428
CREATE TABLE `laser_resource` (
  `res_id` int NOT NULL AUTO_INCREMENT COMMENT '素材ID',
  `file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '素材文件路径',
  `type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '素材类型（1：图片，2：视频）',
  `file_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '原文件名',
  `desc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '素材描述',
  `length` int DEFAULT '0' COMMENT '高度',
  `width` int DEFAULT '0' COMMENT '宽度',
  `size` int DEFAULT '0' COMMENT '素材大小，B',
  `order_id` int DEFAULT '0' COMMENT '归属订单ID',
  `is_diy` tinyint(1) DEFAULT '2' COMMENT '是否是diy素材,1是 2否',
  `create_time` bigint NOT NULL DEFAULT 0 COMMENT '创建时间',
  PRIMARY KEY (`res_id`) USING BTREE,
  KEY `order_id` (`order_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='镭射机素材表';