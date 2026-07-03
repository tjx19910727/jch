-- 维护/巡检项目启用状态字段（用于软删除，保留历史 records 关联信息）
-- 若字段或索引已存在，请勿重复执行对应语句。

ALTER TABLE `maintenance_items`
  ADD COLUMN `is_active` tinyint(1) DEFAULT '1' COMMENT '是否启用' AFTER `sort_order`;

ALTER TABLE `maintenance_items`
  ADD INDEX `idx_is_active` (`is_active`);

ALTER TABLE `check_list_items`
  ADD COLUMN `is_active` tinyint(1) DEFAULT '1' COMMENT '是否启用' AFTER `sort_order`;

ALTER TABLE `check_list_items`
  ADD INDEX `idx_is_active` (`is_active`);
