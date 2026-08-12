-- 设备商品多收款策略数据库变更
-- 商品与收款策略关系全部保存于关联表，不修改 machine_goods 表。

CREATE TABLE IF NOT EXISTS `machine_goods_payee_strategy` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `mg_id` int NOT NULL COMMENT '设备商品ID',
  `sp_id` int NOT NULL COMMENT '收款策略ID',
  `sort` int NOT NULL DEFAULT 1 COMMENT '策略优先级，越小越优先',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '状态：1启用，2停用',
  `create_time` datetime DEFAULT NULL,
  `update_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_mg_sp` (`mg_id`,`sp_id`),
  KEY `idx_mg_status_sort` (`mg_id`,`status`,`sort`),
  KEY `idx_sp_id` (`sp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='设备商品收款策略关联表';

-- 执行后核验：同一商品允许多条策略，但同一 mg_id + sp_id 不允许重复。
SELECT mg_id, COUNT(*) AS strategy_count,
       GROUP_CONCAT(sp_id ORDER BY sort,id) AS sp_ids
FROM machine_goods_payee_strategy
WHERE status = 1
GROUP BY mg_id
ORDER BY strategy_count DESC, mg_id;
