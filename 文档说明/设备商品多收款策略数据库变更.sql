-- 设备商品多收款策略数据库变更
-- 执行前请确认 machine_goods.sp_id 已存在；该字段暂时保留用于旧接口兼容和迁移回退。

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

-- 将上一版单策略配置迁移为第一条关联配置，可重复执行。
INSERT INTO `machine_goods_payee_strategy`
  (`mg_id`,`sp_id`,`sort`,`status`,`create_time`,`update_time`)
SELECT `mg_id`,`sp_id`,1,1,NOW(),NOW()
FROM `machine_goods`
WHERE `sp_id` > 0
ON DUPLICATE KEY UPDATE
  `status` = VALUES(`status`),
  `sort` = VALUES(`sort`),
  `update_time` = VALUES(`update_time`);

-- 执行后核验：同一商品允许多条策略，但同一 mg_id + sp_id 不允许重复。
SELECT mg_id, COUNT(*) AS strategy_count,
       GROUP_CONCAT(sp_id ORDER BY sort,id) AS sp_ids
FROM machine_goods_payee_strategy
WHERE status = 1
GROUP BY mg_id
ORDER BY strategy_count DESC, mg_id;
