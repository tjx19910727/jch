-- 优惠券、满减活动关联微程线上商品。
-- goods_source：1普通商品，2微程线上商品；source_no 为微程父商品 out_no。

ALTER TABLE `activity_goods`
    ADD COLUMN `goods_source` tinyint NOT NULL DEFAULT 1 COMMENT '商品来源：1普通商品2微程线上商品' AFTER `g_id`,
    ADD COLUMN `source_no` varchar(64) NOT NULL DEFAULT '' COMMENT '来源商品编码，微程商品保存out_no' AFTER `goods_source`,
    ADD INDEX `idx_activity_online_goods` (`a_id`, `a_type`, `goods_source`, `source_no`);

ALTER TABLE `activity_fd_content`
    ADD COLUMN `goods_source` tinyint NOT NULL DEFAULT 1 COMMENT '商品来源：1普通商品2微程线上商品' AFTER `g_id`,
    ADD COLUMN `source_no` varchar(64) NOT NULL DEFAULT '' COMMENT '来源商品编码，微程商品保存out_no' AFTER `goods_source`,
    ADD INDEX `idx_fd_online_goods` (`fd_id`, `goods_source`, `source_no`);
