-- 自定义角色权限模板数据库变更

CREATE TABLE IF NOT EXISTS `auth_role_template` (
  `art_id` int NOT NULL AUTO_INCREMENT COMMENT '角色权限模板ID',
  `name` varchar(100) NOT NULL COMMENT '模板名称',
  `desc` varchar(255) DEFAULT NULL COMMENT '模板说明',
  `ao_id` int DEFAULT 0 COMMENT '所属组织ID',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态：1启用，2停用',
  `is_del` tinyint(1) DEFAULT 2 COMMENT '删除状态：1已删除，2未删除',
  `creator` int DEFAULT NULL,
  `create_time` int DEFAULT NULL,
  `update_id` int DEFAULT NULL,
  `update_time` int DEFAULT NULL,
  PRIMARY KEY (`art_id`),
  KEY `idx_ao_status` (`ao_id`, `status`, `is_del`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='角色权限模板表';

CREATE TABLE IF NOT EXISTS `auth_role_template_node` (
  `artn_id` int NOT NULL AUTO_INCREMENT COMMENT '角色权限模板节点ID',
  `art_id` int NOT NULL COMMENT '角色权限模板ID',
  `node_id` int NOT NULL COMMENT '权限节点ID',
  `d_type` tinyint(1) DEFAULT 0 COMMENT '数据权限类型',
  `is_del` tinyint(1) DEFAULT 2 COMMENT '删除状态：1已删除，2未删除',
  `creator` int DEFAULT NULL,
  `create_time` int DEFAULT NULL,
  `update_id` int DEFAULT NULL,
  `update_time` int DEFAULT NULL,
  PRIMARY KEY (`artn_id`),
  UNIQUE KEY `uk_template_node` (`art_id`, `node_id`),
  KEY `idx_node` (`node_id`),
  KEY `idx_template_active` (`art_id`, `is_del`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='角色权限模板节点表';

ALTER TABLE `auth_role`
  ADD COLUMN `template_id` int DEFAULT NULL COMMENT '角色权限模板ID' AFTER `ao_id`,
  ADD INDEX `idx_template_id` (`template_id`);

ALTER TABLE `auth_manager`
  ADD COLUMN `use_role_template` tinyint(1) NOT NULL DEFAULT 2 COMMENT '是否使用角色权限模板：1是，2否（走历史逻辑）' AFTER `status`,
  ADD INDEX `idx_use_role_template` (`use_role_template`);

ALTER TABLE `auth_node`
  ADD COLUMN `permission_action` varchar(16) NOT NULL DEFAULT 'manage' COMMENT '权限动作：menu/query/export/manage' AFTER `data_auth`,
  ADD INDEX `idx_permission_action` (`permission_action`);

-- 历史节点初始化分类。初始化后应在节点管理中人工复核，避免依赖名称或URL长期判断。
UPDATE `auth_node` SET `permission_action` = 'menu' WHERE `is_button` = 2;
UPDATE `auth_node` SET `permission_action` = 'export'
WHERE `is_button` = 1 AND (`url` LIKE '%/export%' OR `url` LIKE '%Export%');
UPDATE `auth_node` SET `permission_action` = 'query'
WHERE `is_button` = 1
  AND (`url` LIKE '%/get%' OR `url` LIKE '%/query%' OR `url` LIKE '%/find%' OR `url` LIKE '%/check%')
  AND `permission_action` = 'manage';

-- 注册角色权限模板接口节点，与“权限角色”接口放在同一菜单下。
INSERT INTO auth_node (pid, name, url, `desc`, sort, type, is_auth, is_button, data_auth, status, create_time, update_time)
SELECT pid, '角色权限模板列表', '/management/auth.auth_role_template/getList', '查询角色权限模板列表', sort, type, 1, 1, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM auth_node
WHERE url = '/management/auth.auth_role/getList'
  AND NOT EXISTS (SELECT 1 FROM auth_node WHERE url = '/management/auth.auth_role_template/getList')
LIMIT 1;

INSERT INTO auth_node (pid, name, url, `desc`, sort, type, is_auth, is_button, data_auth, permission_action, status, create_time, update_time)
SELECT pid, '查询模板顶级导航权限树', '/management/auth.auth_role_template/getTopNavigationNodes', '查询角色权限模板顶级导航组合权限树', sort, type, 1, 1, 1, 'query', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM auth_node
WHERE url = '/management/auth.auth_role_template/getNodes'
  AND NOT EXISTS (SELECT 1 FROM auth_node WHERE url = '/management/auth.auth_role_template/getTopNavigationNodes')
LIMIT 1;

INSERT INTO auth_node (pid, name, url, `desc`, sort, type, is_auth, is_button, data_auth, permission_action, status, create_time, update_time)
SELECT pid, '保存模板顶级导航组合权限', '/management/auth.auth_role_template/saveTopNavigationNodes', '递归展开并保存查询、导出或全部权限', sort, type, 1, 1, 1, 'manage', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM auth_node
WHERE url = '/management/auth.auth_role_template/saveNodes'
  AND NOT EXISTS (SELECT 1 FROM auth_node WHERE url = '/management/auth.auth_role_template/saveTopNavigationNodes')
LIMIT 1;

INSERT INTO auth_node (pid, name, url, `desc`, sort, type, is_auth, is_button, data_auth, status, create_time, update_time)
SELECT pid, '角色权限模板详情', '/management/auth.auth_role_template/getFind', '查询角色权限模板详情', sort, type, 1, 1, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM auth_node
WHERE url = '/management/auth.auth_role/getList'
  AND NOT EXISTS (SELECT 1 FROM auth_node WHERE url = '/management/auth.auth_role_template/getFind')
LIMIT 1;

INSERT INTO auth_node (pid, name, url, `desc`, sort, type, is_auth, is_button, data_auth, status, create_time, update_time)
SELECT pid, '新增角色权限模板', '/management/auth.auth_role_template/add', '新增角色权限模板', sort, type, 1, 1, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM auth_node
WHERE url = '/management/auth.auth_role/getList'
  AND NOT EXISTS (SELECT 1 FROM auth_node WHERE url = '/management/auth.auth_role_template/add')
LIMIT 1;

INSERT INTO auth_node (pid, name, url, `desc`, sort, type, is_auth, is_button, data_auth, status, create_time, update_time)
SELECT pid, '修改角色权限模板', '/management/auth.auth_role_template/update', '修改角色权限模板', sort, type, 1, 1, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM auth_node
WHERE url = '/management/auth.auth_role/getList'
  AND NOT EXISTS (SELECT 1 FROM auth_node WHERE url = '/management/auth.auth_role_template/update')
LIMIT 1;

INSERT INTO auth_node (pid, name, url, `desc`, sort, type, is_auth, is_button, data_auth, status, create_time, update_time)
SELECT pid, '删除角色权限模板', '/management/auth.auth_role_template/del', '删除角色权限模板', sort, type, 1, 1, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM auth_node
WHERE url = '/management/auth.auth_role/getList'
  AND NOT EXISTS (SELECT 1 FROM auth_node WHERE url = '/management/auth.auth_role_template/del')
LIMIT 1;

INSERT INTO auth_node (pid, name, url, `desc`, sort, type, is_auth, is_button, data_auth, status, create_time, update_time)
SELECT pid, '查询角色权限模板节点', '/management/auth.auth_role_template/getNodes', '查询角色权限模板节点', sort, type, 1, 1, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM auth_node
WHERE url = '/management/auth.auth_role/getList'
  AND NOT EXISTS (SELECT 1 FROM auth_node WHERE url = '/management/auth.auth_role_template/getNodes')
LIMIT 1;

INSERT INTO auth_node (pid, name, url, `desc`, sort, type, is_auth, is_button, data_auth, status, create_time, update_time)
SELECT pid, '保存角色权限模板节点', '/management/auth.auth_role_template/saveNodes', '保存角色权限模板节点', sort, type, 1, 1, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM auth_node
WHERE url = '/management/auth.auth_role/getList'
  AND NOT EXISTS (SELECT 1 FROM auth_node WHERE url = '/management/auth.auth_role_template/saveNodes')
LIMIT 1;

INSERT INTO auth_node (pid, name, url, `desc`, sort, type, is_auth, is_button, data_auth, status, create_time, update_time)
SELECT pid, '应用角色权限模板', '/management/auth.auth_role_template/apply', '将权限模板应用到角色', sort, type, 1, 1, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM auth_node
WHERE url = '/management/auth.auth_role/getList'
  AND NOT EXISTS (SELECT 1 FROM auth_node WHERE url = '/management/auth.auth_role_template/apply')
LIMIT 1;

INSERT INTO auth_node (pid, name, url, `desc`, sort, type, is_auth, is_button, data_auth, status, create_time, update_time)
SELECT pid, '角色批量设置账号', '/management/auth.auth_manager_role/setRoleManagers', '替换设置角色绑定的账号集合', sort, type, 1, 1, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM auth_node
WHERE url = '/management/auth.auth_manager_role/getList'
  AND NOT EXISTS (SELECT 1 FROM auth_node WHERE url = '/management/auth.auth_manager_role/setRoleManagers')
LIMIT 1;
