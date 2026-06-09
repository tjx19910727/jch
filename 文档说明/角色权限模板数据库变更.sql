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

-- 注册角色权限模板接口节点，与“权限角色”接口放在同一菜单下。
INSERT INTO auth_node (pid, name, url, `desc`, sort, type, is_auth, is_button, data_auth, status, create_time, update_time)
SELECT pid, '角色权限模板列表', '/management/auth.auth_role_template/getList', '查询角色权限模板列表', sort, type, 1, 1, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM auth_node
WHERE url = '/management/auth.auth_role/getList'
  AND NOT EXISTS (SELECT 1 FROM auth_node WHERE url = '/management/auth.auth_role_template/getList')
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
