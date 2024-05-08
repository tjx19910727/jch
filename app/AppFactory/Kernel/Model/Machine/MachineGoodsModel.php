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
    protected static function onAfterInsert(Model $model)
    {
        $config = [
            "machine_id" => $model['machine_id'],
            "key" => env("api.md5Key"),
        ];
        $app = AppFactory::machine($config);
        @$app->sendMq->triggerUpdateMg($model['mg_id']);
    }

    /**
     * 修改后下发通知设备更新
     * @param Model $model
     */
    protected static function onAfterUpdate($model)
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