<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:28
 */

namespace app\AppFactory\Kernel\Model\Machine;


use app\AppFactory\AppFactory;
use app\AppFactory\Kernel\Model\BaseModel;
use think\Model;

class MachineGoodsModel extends BaseModel
{
    protected $pk = "mg_id";
    protected $name = "machine_goods";

    /**
     * 新增后下发通知设备更新
     * @param Model $model
     */
    public static function afterInsert($model)
    {
        $config = [
            "machine_id" => $model['machine_id'],
            "key" => env("api.md5Key"),
        ];
        $app = AppFactory::machine($config);
        @$app->sendMq->triggerUpdateMg($model['mg_id']);
    }

    /**
     * 修改设备商品库，放入Redis，由系统后台守护进程触发同步到设备货架，下发触发设备更新
     * @param int $mg_id
     */
    public static function AfterUpdate($mg_id)
    {
        $redis = new \Redis();
        $config = config("redis");
        $redis->connect($config['host'], $config['port'],$config['timeout'],$config['reserved'],$config['retry_interval']);
        $redis->auth($config['password']);
        $redis->lPush("updateMg",$mg_id);
        $redis->expire("updateMg",300);
        $redisData = $redis->lRange("updateMg", 0, -1);
        actionLog($mg_id,'修改的设备商品ID');
        actionLog($redisData,'放入Redis数据');
        $redis->close();
    }

    /**
     * 删除后下发通知设备更新
     * @param Model $model
     */
    protected static function onAfterDelete(Model $model)
    {
        $where = $model->getWhere();
        if (!$where) $where['mg_id'] = $model['mg_id'];
        if ($where) {
            $mg = self::getList($where, 0, 'mg_id,machine_id');
            if ($mg) {
                $config = [
                    "machine_id" => $mg[0]['machine_id'],
                    "key" => env("api.md5Key"),
                ];
                $app = AppFactory::machine($config);
                foreach ($mg as $k => $v) {
                    $app->sendMq->triggerUpdateMg($v['mg_id']);
                }
            }
        }
    }
}