-- 角色权限模板新版数据库完整升级
-- 生成依据：当前 jch 数据库（MySQL 5.5.62）
-- 生成时间：2026-06-15
--
-- 当前数据库确认：
-- 1. auth_role_template / auth_role_template_node / auth_role_template_navigation 均不存在。
-- 2. auth_manager.use_role_template、auth_manager.role_template_id、auth_node.permission_action 均不存在。
-- 3. 历史 auth_role_node.d_type 保留，用于未启用模板账号的兼容逻辑。
--
-- 此 SQL 按当前数据库状态生成，只执行一次。执行前请备份相关表。

SET NAMES utf8mb4;

-- ============================================================
-- 一、角色权限模板表
-- ============================================================

CREATE TABLE `auth_role_template` (
  `art_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '角色权限模板ID',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '模板名称',
  `desc` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '模板说明',
  `ao_id` int(11) DEFAULT '0' COMMENT '所属组织ID',
  `status` tinyint(1) DEFAULT '1' COMMENT '状态：1启用，2停用',
  `is_del` tinyint(1) NOT NULL DEFAULT '2' COMMENT '删除状态：1已删除，2未删除',
  `creator` int(11) DEFAULT NULL COMMENT '创建人ID',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_id` int(11) DEFAULT NULL COMMENT '修改人ID',
  `update_time` int(11) DEFAULT NULL COMMENT '修改时间',
  PRIMARY KEY (`art_id`) USING BTREE,
  KEY `idx_ao_status` (`ao_id`,`status`,`is_del`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色权限模板表';

CREATE TABLE `auth_role_template_node` (
  `artn_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '角色权限模板节点ID',
  `art_id` int(11) NOT NULL COMMENT '角色权限模板ID',
  `node_id` int(11) NOT NULL COMMENT '权限节点ID',
  `d_type` tinyint(1) DEFAULT '0' COMMENT '历史数据权限类型；新版模板不再写入',
  `data_scope` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '查询范围：organization/all；空值表示历史d_type逻辑',
  `is_del` tinyint(1) NOT NULL DEFAULT '2' COMMENT '删除状态：1已删除，2未删除',
  `creator` int(11) DEFAULT NULL COMMENT '创建人ID',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_id` int(11) DEFAULT NULL COMMENT '修改人ID',
  `update_time` int(11) DEFAULT NULL COMMENT '修改时间',
  PRIMARY KEY (`artn_id`) USING BTREE,
  UNIQUE KEY `uk_template_node` (`art_id`,`node_id`) USING BTREE,
  KEY `idx_node` (`node_id`) USING BTREE,
  KEY `idx_template_active` (`art_id`,`is_del`) USING BTREE,
  KEY `idx_data_scope` (`data_scope`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色权限模板最终节点表';

CREATE TABLE `auth_role_template_navigation` (
  `artnavi_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '模板导航配置ID',
  `art_id` int(11) NOT NULL COMMENT '角色权限模板ID',
  `node_id` int(11) NOT NULL COMMENT '顶级导航节点ID',
  `data_scope` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'organization' COMMENT '查询范围：organization/all',
  `create_enabled` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否授权新增接口',
  `delete_enabled` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否授权删除接口',
  `update_enabled` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否授权修改接口',
  `query_enabled` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否授权查询接口',
  `is_del` tinyint(1) NOT NULL DEFAULT '2' COMMENT '删除状态：1已删除，2未删除',
  `creator` int(11) DEFAULT NULL COMMENT '创建人ID',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_id` int(11) DEFAULT NULL COMMENT '修改人ID',
  `update_time` int(11) DEFAULT NULL COMMENT '修改时间',
  PRIMARY KEY (`artnavi_id`) USING BTREE,
  UNIQUE KEY `uk_art_node` (`art_id`,`node_id`) USING BTREE,
  KEY `idx_art_active` (`art_id`,`is_del`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='角色权限模板顶级导航配置表';

-- ============================================================
-- 二、现有权限表增加模板字段
-- ============================================================

ALTER TABLE `auth_manager`
  ADD COLUMN `use_role_template` tinyint(1) NOT NULL DEFAULT '2' COMMENT '是否使用角色权限模板：1是，2否（历史逻辑）' AFTER `status`,
  ADD COLUMN `role_template_id` int(11) NOT NULL DEFAULT '0' COMMENT '账号直接绑定的角色权限模板ID，单账号仅允许一个模板' AFTER `use_role_template`,
  ADD INDEX `idx_use_role_template` (`use_role_template`);

ALTER TABLE `auth_manager`
  ADD INDEX `idx_role_template_id` (`role_template_id`);

ALTER TABLE `auth_node`
  ADD COLUMN `permission_action` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unclassified'
    COMMENT '权限动作：menu/create/delete/update/query/unclassified' AFTER `data_auth`,
  ADD INDEX `idx_permission_action` (`permission_action`);

-- ============================================================
-- 三、现有权限节点初始化分类
-- ============================================================

-- 有子节点的节点，以及 type=1 的页面节点，统一视为导航。
UPDATE `auth_node` n
LEFT JOIN `auth_node` c ON c.`pid` = n.`node_id`
SET n.`permission_action` = 'menu'
WHERE n.`type` = 1
   OR c.`node_id` IS NOT NULL;

-- 仅对尚未分类的叶子接口节点按 URL 最后一段分类。
UPDATE `auth_node`
SET `permission_action` = 'query'
WHERE `permission_action` = 'unclassified'
  AND LOWER(SUBSTRING_INDEX(SUBSTRING_INDEX(`url`, '?', 1), '/', -1))
      REGEXP '^(get|query|find|check|list|search|export|refresh|bind|config|List|help|stats|detail|Detail|upload|saleData|http|login)';

UPDATE `auth_node`
SET `permission_action` = 'create'
WHERE `permission_action` = 'unclassified'
  AND LOWER(SUBSTRING_INDEX(SUBSTRING_INDEX(`url`, '?', 1), '/', -1))
      REGEXP '^(add|create|insert|upload|import|send|push|sync||)';

UPDATE `auth_node`
SET `permission_action` = 'delete'
WHERE `permission_action` = 'unclassified'
  AND LOWER(SUBSTRING_INDEX(SUBSTRING_INDEX(`url`, '?', 1), '/', -1))
      REGEXP '^(del|delete|remove|lock)';

UPDATE `auth_node`
SET `permission_action` = 'update'
WHERE `permission_action` = 'unclassified'
  AND LOWER(SUBSTRING_INDEX(SUBSTRING_INDEX(`url`, '?', 1), '/', -1))
      REGEXP '^(refund|trigger|Refund|update|edit|save|bind|apply|set|copy|push|sync|import|reset|enable|disable|reset|change|move|sort|config|bind|fix|change|remote|Update|cancel|audit|Handle|assign|takeDown|unbind|operation|upDown|usePickCode|sendMainControl|Update)';
-- ============================================================
-- 四、注册角色权限模板菜单及接口节点
-- ============================================================

-- 当前数据库中“权限角色”菜单为 node_id=114、pid=105。
-- 新模板菜单与“权限角色”同级，接口节点挂载在新模板菜单下。

INSERT INTO `auth_node`
  (`pid`,`name`,`url`,`desc`,`sort`,`type`,`is_auth`,`is_button`,`data_auth`,`permission_action`,`status`,`create_time`,`update_time`)
VALUES
  (105,'角色权限模板','/management/auth.auth_role_template/getList','角色权限模板管理',4,1,1,2,2,'menu',1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP());

SET @role_template_menu_id = LAST_INSERT_ID();

INSERT INTO `auth_node`
  (`pid`,`name`,`url`,`desc`,`sort`,`type`,`is_auth`,`is_button`,`data_auth`,`permission_action`,`status`,`create_time`,`update_time`)
VALUES
  (@role_template_menu_id,'查询模板详情','/management/auth.auth_role_template/getFind','查询角色权限模板详情',1,2,1,1,2,'query',1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
  (@role_template_menu_id,'新增权限模板','/management/auth.auth_role_template/add','新增角色权限模板',2,2,1,1,2,'create',1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
  (@role_template_menu_id,'修改权限模板','/management/auth.auth_role_template/update','修改角色权限模板',3,2,1,1,2,'update',1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
  (@role_template_menu_id,'删除权限模板','/management/auth.auth_role_template/del','删除未使用的角色权限模板',4,2,1,1,2,'delete',1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
  (@role_template_menu_id,'查询模板节点','/management/auth.auth_role_template/getNodes','查询角色权限模板最终节点',5,2,1,1,2,'query',1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
  (@role_template_menu_id,'保存模板节点','/management/auth.auth_role_template/saveNodes','手动覆盖保存角色权限模板节点',6,2,1,1,2,'update',1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
  (@role_template_menu_id,'查询模板导航权限树','/management/auth.auth_role_template/getTopNavigationNodes','查询导航权限树及模板当前配置',7,2,1,1,2,'query',1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
  (@role_template_menu_id,'保存模板导航权限','/management/auth.auth_role_template/saveTopNavigationNodes','按导航保存数据范围及增删改查权限',8,2,1,1,2,'update',1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
  (@role_template_menu_id,'查询模板关联账号','/management/auth.auth_role_template/getManagers','查询直接绑定当前模板的账号',9,2,1,1,2,'query',1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP()),
  (@role_template_menu_id,'模板批量设置账号','/management/auth.auth_role_template/applyManagers','直接替换设置模板绑定账号集合',10,2,1,1,2,'update',1,UNIX_TIMESTAMP(),UNIX_TIMESTAMP());

-- ============================================================
-- 五、执行后检查
-- ============================================================

-- 未分类接口必须人工确认后，再允许在新模板中授权。
SELECT `node_id`,`pid`,`name`,`url`,`permission_action`
FROM `auth_node`
WHERE `status` = 1
  AND `permission_action` = 'unclassified'
ORDER BY `node_id`;

-- 验证模板相关结构。
SELECT `TABLE_NAME`,`COLUMN_NAME`,`COLUMN_TYPE`,`COLUMN_DEFAULT`
FROM information_schema.COLUMNS
WHERE `TABLE_SCHEMA` = DATABASE()
  AND (
    `TABLE_NAME` IN ('auth_role_template','auth_role_template_node','auth_role_template_navigation')
    OR (`TABLE_NAME` = 'auth_manager' AND `COLUMN_NAME` = 'use_role_template')
    OR (`TABLE_NAME` = 'auth_manager' AND `COLUMN_NAME` = 'role_template_id')
    OR (`TABLE_NAME` = 'auth_node' AND `COLUMN_NAME` = 'permission_action')
  )
ORDER BY `TABLE_NAME`,`ORDINAL_POSITION`;

SELECT `node_id`,`pid`,`name`,`url`,`permission_action`
FROM `auth_node`
WHERE `url` LIKE '/management/auth.auth_role_template/%'
ORDER BY `node_id`;
