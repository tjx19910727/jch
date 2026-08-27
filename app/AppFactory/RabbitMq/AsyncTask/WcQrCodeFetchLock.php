<?php

namespace app\AppFactory\RabbitMq\AsyncTask;

use think\facade\Db;

/**
 * 微程货道二维码获取短租约，防止设备轮询并发重复请求受限接口。
 */
class WcQrCodeFetchLock
{
    const LOCK_PREFIX = 'wc_qrcode_fetch_';
    const TTL = 60;
    const RATE_LOCK_NAME = 'wc_qrcode_fetch_rate_limit';
    const RATE_INTERVAL = 1;
    const RATE_LOCK_TTL = 60;

    /**
     * @param int $mcId
     * @param string $owner
     * @return string 获取成功返回持有者标识，失败返回空字符串
     */
    public static function acquire($mcId, $owner = '', $scope = 'wc')
    {
        $mcId = intval($mcId);
        if ($mcId <= 0) return '';
        $scope = $scope === 'mc' ? 'mc' : 'wc';
        return self::acquireLock(self::LOCK_PREFIX . $scope . '_' . $mcId, self::TTL, $owner);
    }

    /**
     * 全局请求闸门。成功后不主动释放，由短租约保证不同货道也不会并发调用外部接口。
     */
    public static function acquireRateLimit($owner = '')
    {
        return self::acquireLock(self::RATE_LOCK_NAME, self::RATE_LOCK_TTL, $owner);
    }

    /**
     * 外部请求结束后保留最小调用间隔；进程异常时由 RATE_LOCK_TTL 自动解锁。
     */
    public static function cooldownRateLimit($owner)
    {
        $owner = trim(strval($owner));
        if ($owner === '') return false;
        $now = time();
        try {
            return (bool)Db::name('wc_sync_lock')
                ->where('lock_name', '=', self::RATE_LOCK_NAME)
                ->where('owner_task_id', '=', $owner)
                ->update([
                    'expire_time' => $now + self::RATE_INTERVAL,
                    'update_time' => $now,
                ]);
        } catch (\Throwable $e) {
            actionException($e, 1);
            return false;
        }
    }

    protected static function acquireLock($lockName, $ttl, $owner = '')
    {
        if ($owner === '') $owner = uniqid('qrcode_', true);

        $now = time();
        $expireTime = $now + max(1, intval($ttl));
        $sql = 'INSERT INTO `wc_sync_lock` (`lock_name`,`owner_task_id`,`expire_time`,`update_time`) '
            . 'VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE '
            . '`owner_task_id`=IF(`expire_time`<=VALUES(`update_time`),VALUES(`owner_task_id`),`owner_task_id`),'
            . '`expire_time`=IF(`owner_task_id`=VALUES(`owner_task_id`),VALUES(`expire_time`),`expire_time`),'
            . '`update_time`=IF(`owner_task_id`=VALUES(`owner_task_id`),VALUES(`update_time`),`update_time`)';
        try {
            Db::execute($sql, [$lockName, $owner, $expireTime, $now]);
            $locked = Db::name('wc_sync_lock')
                ->where('lock_name', '=', $lockName)
                ->where('owner_task_id', '=', $owner)
                ->where('expire_time', '>=', $now)
                ->find();
            return $locked ? $owner : '';
        } catch (\Throwable $e) {
            actionException($e, 1);
            return '';
        }
    }

    public static function release($mcId, $owner, $scope = 'wc')
    {
        $mcId = intval($mcId);
        $owner = trim(strval($owner));
        if ($mcId <= 0 || $owner === '') return false;
        $scope = $scope === 'mc' ? 'mc' : 'wc';
        try {
            return (bool)Db::name('wc_sync_lock')
                ->where('lock_name', '=', self::LOCK_PREFIX . $scope . '_' . $mcId)
                ->where('owner_task_id', '=', $owner)
                ->delete();
        } catch (\Throwable $e) {
            actionException($e, 1);
            return false;
        }
    }
}
