-- 故障上报与微信通知数据库变更
-- 适用数据库：MySQL 5.7
-- 生成时间：2026-08-12
--
-- 设计口径：
-- 1. 新增7张业务配置表。
-- 2. machine_error_code、wx_template_log只增加字段/索引，不转换存储引擎。
-- 3. 不增加外键，不依赖事务。
-- 4. auth_manager_notice_config保留，但新故障通知流程不再读取。
-- 5. 本文件只包含表结构和固定三级故障等级初始化，不初始化组织配置、分类和故障码。
-- 6. ALTER TABLE部分按一次性升级脚本编写，执行前请先备份相关表。

SET NAMES utf8mb4;

-- ============================================================
-- 一、新增表
-- ============================================================

-- ------------------------------------------------------------
-- 1. 故障通知全局设置
-- 每个组织一条配置；offline_minutes不填时由服务端按30分钟保存。
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `machine_fault_notice_config` (
  `ao_id` int(11) NOT NULL COMMENT '组织ID',
  `notice_enabled` tinyint(1) NOT NULL DEFAULT '1' COMMENT '故障通知总开关：1启用，2停用',
  `offline_notice_enabled` tinyint(1) NOT NULL DEFAULT '1' COMMENT '离线故障通知开关：1启用，2停用',
  `offline_minutes` int(11) NOT NULL DEFAULT '30' COMMENT '持续离线多少分钟生成离线故障，默认30分钟',
  `creator` int(11) NOT NULL DEFAULT '0' COMMENT '创建人manager_id',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_id` int(11) NOT NULL DEFAULT '0' COMMENT '修改人manager_id',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '修改时间',
  PRIMARY KEY (`ao_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='故障通知全局设置表';

-- ------------------------------------------------------------
-- 2. 故障等级通知策略
-- 按组织、故障等级配置静默时段和通知频率；不再按接收账号配置。
-- 未配置或删除某等级策略时，由服务端使用该等级的系统默认策略。
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `machine_fault_notice_frequency` (
  `ao_id` int(11) NOT NULL COMMENT '组织ID',
  `level` tinyint(1) NOT NULL COMMENT '故障等级：1紧急，2一般，3提示',
  `quiet_enabled` tinyint(1) NOT NULL DEFAULT '2' COMMENT '静默时段开关：1启用，2停用；紧急等级固定为2',
  `quiet_start` time DEFAULT NULL COMMENT '静默开始时间，支持跨天',
  `quiet_end` time DEFAULT NULL COMMENT '静默结束时间，支持跨天',
  `interval_minutes` int(11) NOT NULL DEFAULT '0' COMMENT '同设备同故障码最小通知间隔，单位分钟',
  `day_limit` int(11) NOT NULL DEFAULT '1' COMMENT '每自然日最多通知轮次，必须大于0',
  `creator` int(11) NOT NULL DEFAULT '0' COMMENT '创建人manager_id',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_id` int(11) NOT NULL DEFAULT '0' COMMENT '修改人manager_id',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '修改时间',
  PRIMARY KEY (`ao_id`,`level`),
  KEY `idx_level` (`level`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='故障等级通知策略表';

-- ------------------------------------------------------------
-- 3. 固定故障等级
-- level是固定数字主键；grade为tinyint等级值；本表不提供增删改接口。
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `machine_fault_level` (
  `level` tinyint(1) NOT NULL COMMENT '故障等级主键：1、2、3，非自增',
  `grade` tinyint(1) NOT NULL COMMENT '等级值：1、2、3',
  `level_name` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '等级名称：紧急、一般、提示',
  `level_desc` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '等级说明',
  `color` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '页面标签颜色',
  PRIMARY KEY (`level`),
  UNIQUE KEY `uk_grade` (`grade`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='固定故障等级表';

INSERT INTO `machine_fault_level` (`level`,`grade`,`level_name`,`level_desc`,`color`)
VALUES
  (1, 1, '紧急', '影响核心营业、支付或严重连通性，需要立即介入', '#F5222D'),
  (2, 2, '一般', '影响设备局部功能或需要安排运维', '#FA8C16'),
  (3, 3, '提示', '库存、操作记录、媒体等低风险提醒', '#1677FF')
ON DUPLICATE KEY UPDATE
  `grade` = VALUES(`grade`),
  `level_name` = VALUES(`level_name`),
  `level_desc` = VALUES(`level_desc`),
  `color` = VALUES(`color`);

-- ------------------------------------------------------------
-- 4. 故障分类
-- 新增/编辑分类时，从当前组织已启用的mFault微信模板中选择wt_id。
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `machine_fault_category` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '故障分类ID',
  `ao_id` int(11) NOT NULL DEFAULT '0' COMMENT '组织ID',
  `category_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '故障分类名称',
  `wt_id` int(11) NOT NULL DEFAULT '0' COMMENT '微信故障模板ID，对应wx_template.wt_id',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：1启用，2停用',
  `sort` int(11) NOT NULL DEFAULT '99' COMMENT '排序，越小越靠前',
  `creator` int(11) NOT NULL DEFAULT '0' COMMENT '创建人manager_id',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_id` int(11) NOT NULL DEFAULT '0' COMMENT '修改人manager_id',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '修改时间',
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `uk_ao_category_name` (`ao_id`,`category_name`) USING BTREE,
  KEY `idx_ao_status_sort` (`ao_id`,`status`,`sort`) USING BTREE,
  KEY `idx_wt_id` (`wt_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='故障分类及微信模板配置表';

-- ------------------------------------------------------------
-- 5. 故障码通知规则
-- machine_error_code_solution继续只保存解决方案，不承载本表字段。
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `machine_error_code_notice_rule` (
  `ao_id` int(11) NOT NULL DEFAULT '0' COMMENT '组织ID',
  `error_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '故障码',
  `error_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '后台完整故障名称',
  `wechat_text` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '微信模板短名称，最多20个字符',
  `category_id` int(11) NOT NULL DEFAULT '0' COMMENT '所属故障分类ID',
  `level` tinyint(1) NOT NULL DEFAULT '2' COMMENT '故障等级：1紧急，2一般，3提示',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '故障项状态：1启用，2停用',
  `notice_enabled` tinyint(1) NOT NULL DEFAULT '1' COMMENT '通知开关：1启用，2停用',
  `creator` int(11) NOT NULL DEFAULT '0' COMMENT '创建人manager_id',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_id` int(11) NOT NULL DEFAULT '0' COMMENT '修改人manager_id',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '修改时间',
  PRIMARY KEY (`ao_id`,`error_code`),
  KEY `idx_ao_category` (`ao_id`,`category_id`) USING BTREE,
  KEY `idx_ao_level_status` (`ao_id`,`level`,`status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='故障码分类等级及通知规则表';

-- ------------------------------------------------------------
-- 6. 故障通知接收人
-- 一个组织下一个后台账号只有一条接收配置。
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `machine_fault_receiver` (
  `receiver_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '接收人配置ID',
  `ao_id` int(11) NOT NULL DEFAULT '0' COMMENT '组织ID',
  `manager_id` int(11) NOT NULL DEFAULT '0' COMMENT '后台账号ID，对应auth_manager.manager_id',
  `machine_scope` tinyint(1) NOT NULL DEFAULT '1' COMMENT '设备范围：1全部设备，2指定设备',
  `fault_scope` tinyint(1) NOT NULL DEFAULT '2' COMMENT '故障范围：1全部故障，2故障分类，3具体故障码',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：1启用，2停用',
  `creator` int(11) NOT NULL DEFAULT '0' COMMENT '创建人manager_id',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_id` int(11) NOT NULL DEFAULT '0' COMMENT '修改人manager_id',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '修改时间',
  PRIMARY KEY (`receiver_id`),
  UNIQUE KEY `uk_ao_manager` (`ao_id`,`manager_id`) USING BTREE,
  KEY `idx_ao_status` (`ao_id`,`status`) USING BTREE,
  KEY `idx_manager_id` (`manager_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='故障通知接收人配置表';

-- ------------------------------------------------------------
-- 7. 接收人范围明细
-- 合并指定设备范围与故障范围：
-- scope_type=1：指定设备，target_value保存m_id；
-- scope_type=2：故障分类，target_value保存category_id；
-- scope_type=3：具体故障，target_value保存error_code。
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `machine_fault_receiver_scope` (
  `receiver_id` int(11) NOT NULL COMMENT '接收人配置ID，对应machine_fault_receiver.receiver_id',
  `scope_type` tinyint(1) NOT NULL COMMENT '范围类型：1设备，2故障分类，3故障码',
  `target_value` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '范围值：m_id、category_id或error_code',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`receiver_id`,`scope_type`,`target_value`),
  KEY `idx_scope_target` (`scope_type`,`target_value`,`receiver_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='故障通知接收人设备及故障范围明细表';

-- ============================================================
-- 二、修改现有表
-- ============================================================

-- ------------------------------------------------------------
-- 1. 故障事件表
-- 每次故障上报仍新增一行；以下字段保存事件发生时的分类、等级和通知结果。
-- 保持machine_error_code当前存储引擎，不执行ENGINE转换。
-- ------------------------------------------------------------
ALTER TABLE `machine_error_code`
  ADD COLUMN `category_id` int(11) NOT NULL DEFAULT '0' COMMENT '事件发生时的故障分类快照' AFTER `errorCode`,
  ADD COLUMN `level` tinyint(1) NOT NULL DEFAULT '2' COMMENT '事件发生时的故障等级快照：1紧急，2一般，3提示' AFTER `category_id`,
  ADD COLUMN `notice_status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '通知状态：0待判定，1全部成功，2部分成功，3全部失败，4未发送' AFTER `status`,
  ADD COLUMN `notice_reason` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '未发送或失败原因编码' AFTER `notice_status`,
  ADD COLUMN `notice_time` int(11) NOT NULL DEFAULT '0' COMMENT '本事件实际尝试发送时间，未发送为0' AFTER `notice_reason`,
  ADD COLUMN `handle_manager_id` int(11) NOT NULL DEFAULT '0' COMMENT '确认处理人manager_id' AFTER `notice_time`,
  ADD COLUMN `handle_time` int(11) NOT NULL DEFAULT '0' COMMENT '确认处理时间' AFTER `handle_manager_id`,
  ADD KEY `idx_category_id` (`category_id`) USING BTREE,
  ADD KEY `idx_level` (`level`) USING BTREE,
  ADD KEY `idx_notice_status` (`notice_status`) USING BTREE,
  ADD KEY `idx_fault_list` (`ao_id`,`create_time`) USING BTREE,
  ADD KEY `idx_fault_level_dashboard` (`ao_id`,`level`,`create_time`,`m_id`) USING BTREE,
  ADD KEY `idx_fault_pending` (`ao_id`,`status`) USING BTREE;

-- ------------------------------------------------------------
-- 2. 微信模板通知日志
-- 不增加重试、补发、延迟或邮件字段，只补查询索引。
-- 保持wx_template_log当前存储引擎，不执行ENGINE转换。
-- ------------------------------------------------------------
ALTER TABLE `wx_template_log`
  ADD KEY `idx_me_id` (`me_id`) USING BTREE,
  ADD KEY `idx_m_id` (`m_id`) USING BTREE,
  ADD KEY `idx_fault_frequency` (`ao_id`,`m_id`,`error_code`,`template_type`,`create_time`) USING BTREE,
  ADD KEY `idx_fault_dashboard` (`ao_id`,`template_type`,`create_time`,`send_status`) USING BTREE;
