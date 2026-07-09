-- 客户提交退货日志、后台远程退货操作日志页面权限
-- 依赖：auth_node 已存在 permission_action 字段。

INSERT INTO auth_node
  (pid, name, icon, url, `desc`, sort, type, is_auth, is_button, data_auth, permission_action, status, create_time, update_time)
SELECT node_id, '客户退货日志', '', '/management/eventlog.machine_refund_goods_log/getList',
       '设备端客户提交退货日志页面', 5, 1, 1, 2, 2, 'menu', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM auth_node
WHERE url = '/management/eventlog'
  AND NOT EXISTS (
    SELECT 1 FROM auth_node WHERE url = '/management/eventlog.machine_refund_goods_log/getList'
  )
LIMIT 1;

INSERT INTO auth_node
  (pid, name, icon, url, `desc`, sort, type, is_auth, is_button, data_auth, permission_action, status, create_time, update_time)
SELECT node_id, '远程退货操作日志', '', '/management/eventlog.remote_action_log/getRemoteRefundGoodsList',
       '后台远程退货设备操作日志页面', 6, 1, 1, 2, 2, 'menu', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()
FROM auth_node
WHERE url = '/management/eventlog'
  AND NOT EXISTS (
    SELECT 1 FROM auth_node WHERE url = '/management/eventlog.remote_action_log/getRemoteRefundGoodsList'
  )
LIMIT 1;
