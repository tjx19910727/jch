INSERT INTO auth_node
(pid, name, icon, url, `desc`, sort, type, is_auth, is_button, data_auth, status, create_time, update_time)
SELECT
  source_node.pid,
  '导出未使用提货码',
  source_node.icon,
  '/management/activity.activity_pick_code/exportCode',
  '导出活动中未使用的提货码',
  source_node.sort,
  source_node.type,
  source_node.is_auth,
  1,
  source_node.data_auth,
  source_node.status,
  UNIX_TIMESTAMP(),
  UNIX_TIMESTAMP()
FROM auth_node source_node
WHERE source_node.url = '/management/activity.activity_pick_code/getList'
  AND NOT EXISTS (
    SELECT 1
    FROM auth_node target_node
    WHERE target_node.url = '/management/activity.activity_pick_code/exportCode'
  )
LIMIT 1;

INSERT INTO auth_node
(pid, name, icon, url, `desc`, sort, type, is_auth, is_button, data_auth, status, create_time, update_time)
SELECT
  source_node.pid,
  '导出提货码使用报表',
  source_node.icon,
  '/management/activity.activity_pick_code/exportUsedList',
  '导出活动提货码使用情况报表',
  source_node.sort,
  source_node.type,
  source_node.is_auth,
  1,
  source_node.data_auth,
  source_node.status,
  UNIX_TIMESTAMP(),
  UNIX_TIMESTAMP()
FROM auth_node source_node
WHERE source_node.url = '/management/activity.activity_pick_code/getList'
  AND NOT EXISTS (
    SELECT 1
    FROM auth_node target_node
    WHERE target_node.url = '/management/activity.activity_pick_code/exportUsedList'
  )
LIMIT 1;

INSERT INTO auth_role_node
(role_id, node_id, d_type, is_del, creator, create_time, update_time)
SELECT
  source_role.role_id,
  export_node.node_id,
  source_role.d_type,
  2,
  source_role.creator,
  UNIX_TIMESTAMP(),
  UNIX_TIMESTAMP()
FROM auth_role_node source_role
JOIN auth_node source_node
  ON source_node.node_id = source_role.node_id
JOIN auth_node export_node
  ON export_node.url IN (
    '/management/activity.activity_pick_code/exportCode',
    '/management/activity.activity_pick_code/exportUsedList'
  )
WHERE source_node.url = '/management/activity.activity_pick_code/getList'
  AND source_role.is_del = 2
  AND NOT EXISTS (
    SELECT 1
    FROM auth_role_node target_role
    WHERE target_role.role_id = source_role.role_id
      AND target_role.node_id = export_node.node_id
      AND target_role.is_del = 2
  );
