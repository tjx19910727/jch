-- 设备商品批量配置收款策略权限节点。
-- 新接口：/management/machine.machine_goods/updatePayeeStrategiesBatch
-- 挂在现有设备商品列表节点下，不自动给任何角色或权限模板授权。

SET @parent_node_id := (
    SELECT node_id FROM auth_node
    WHERE url = '/management/machine.machine_goods/getList'
    ORDER BY node_id ASC LIMIT 1
);
SET @node_url := '/management/machine.machine_goods/updatePayeeStrategiesBatch';
SET @node_name := '批量配置设备商品收款策略';
SET @now_time := UNIX_TIMESTAMP();

UPDATE auth_node
SET pid = @parent_node_id,
    name = @node_name,
    `desc` = '将多个设备商品的独立收款策略整体替换为同一有序策略集合',
    type = 2,
    is_auth = 1,
    is_button = 1,
    data_auth = 1,
    permission_action = 'manage',
    status = 1,
    update_time = @now_time
WHERE url = @node_url;

INSERT INTO auth_node (
    pid,name,icon,url,`desc`,sort,type,is_auth,is_button,
    data_auth,permission_action,status,create_time,update_time
)
SELECT @parent_node_id,@node_name,'',@node_url,
       '将多个设备商品的独立收款策略整体替换为同一有序策略集合',
       102,2,1,1,1,'manage',1,@now_time,@now_time
FROM DUAL
WHERE @parent_node_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM auth_node WHERE url = @node_url);

SELECT node_id,pid,name,url,is_button,data_auth,permission_action,status
FROM auth_node
WHERE url = @node_url;
