<?php

namespace app\AppFactory\RabbitMq\AsyncTask;

use think\facade\Db;

/**
 * 基于 MySQL 租约表的微程商品全量同步锁。
 */
class WcGoodsSyncLock
{
    const LOCK_NAME = 'wc_goods_sync_all_lock';
    const TTL = 7200;

    public static function acquire($taskId)
    {
        $taskId = trim(strval($taskId));
        if ($taskId === '') return false;
        $now = time();
        $expireTime = $now + self::TTL;
        $sql = 'INSERT INTO `wc_sync_lock` (`lock_name`,`owner_task_id`,`expire_time`,`update_time`) '
            . 'VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE '
            . '`owner_task_id`=IF(`expire_time`<VALUES(`update_time`),VALUES(`owner_task_id`),`owner_task_id`),'
            . '`expire_time`=IF(`owner_task_id`=VALUES(`owner_task_id`),VALUES(`expire_time`),`expire_time`),'
            . '`update_time`=IF(`owner_task_id`=VALUES(`owner_task_id`),VALUES(`update_time`),`update_time`)';
        try {
            Db::execute($sql, [self::LOCK_NAME, $taskId, $expireTime, $now]);
            return (bool)Db::name('wc_sync_lock')
                ->where('lock_name', '=', self::LOCK_NAME)
                ->where('owner_task_id', '=', $taskId)
                ->where('expire_time', '>=', $now)
                ->find();
        } catch (\Throwable $e) {
            actionException($e, 1);
            return false;
        }
    }

    public static function refresh($taskId)
    {
        $taskId = trim(strval($taskId));
        if ($taskId === '') return false;
        $now = time();
        try {
            return (bool)Db::name('wc_sync_lock')
                ->where('lock_name', '=', self::LOCK_NAME)
                ->where('owner_task_id', '=', $taskId)
                ->update(['expire_time' => $now + self::TTL, 'update_time' => $now]);
        } catch (\Throwable $e) {
            actionException($e, 1);
            return false;
        }
    }

    public static function release($taskId)
    {
        $taskId = trim(strval($taskId));
        if ($taskId === '') return false;
        try {
            return (bool)Db::name('wc_sync_lock')
                ->where('lock_name', '=', self::LOCK_NAME)
                ->where('owner_task_id', '=', $taskId)
                ->delete();
        } catch (\Throwable $e) {
            actionException($e, 1);
            return false;
        }
    }
}
