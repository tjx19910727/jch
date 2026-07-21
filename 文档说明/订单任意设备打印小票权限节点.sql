-- 订单任意设备打印小票权限节点。
-- 依赖：销售订单列表节点存在，auth_node.permission_action 字段存在。

INSERT INTO auth_node
    (pid, name, url, `desc`, sort, type, is_auth, is_button, data_auth, permission_action, status, create_time, update_time)
SELECT
    pid,
    '指定设备打印小票',
    '/management/sale.sale_orders/printReceipt',
    '选择订单并向指定在线设备下发小票打印命令',
    99,
    type,
    1,
    1,
    data_auth,
    'manage',
    1,
    UNIX_TIMESTAMP(),
    UNIX_TIMESTAMP()
FROM auth_node
WHERE url = '/management/sale.sale_orders/getList'
  AND NOT EXISTS (
      SELECT 1 FROM auth_node
      WHERE url = '/management/sale.sale_orders/printReceipt'
  )
LIMIT 1;

SELECT node_id,pid,name,url,is_button,permission_action,status
FROM auth_node
WHERE url = '/management/sale.sale_orders/printReceipt';
