<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:27
 */

namespace app\AppFactory\Kernel\Model\Machine;


use app\AppFactory\AppFactory;
use app\AppFactory\Kernel\Model\BaseModel;
use think\Model;

class MachineConfigModel extends BaseModel
{
    protected $pk = "mc_id";
    protected $name = "machine_config";

    /**
     * 新增后通知下发设备终端更新
     * @param Model $model
     */
    protected static function onAfterInsert(Model $model)
    {
        $config = [
            "machine_id" => $model['machine_id'],
            "key" =>
                cache($model['machine_id'] . ".signKey") ??
                (MachineModel::getFieldValue(['machine_id' => $model['machine_id']],'signKey') ?? env("api.md5Key")),
        ];
        $app = AppFactory::machine($config);
        @$app->sendMq->sendMq("updateMachineConfig");
    }

    /**
     * 删除后通知下发设备终端更新
     * @param Model $model
     */
    protected static function onAfterDelete(Model $model)
    {
        $where = $model->getWhere();
        if (!$where) $where['mc_id'] = $model['mc_id'];
        if ($where) {
            $machine_id = self::getFieldValue($where, 'machine_id');
            if ($machine_id) {
                $config = [
                    "machine_id" => $machine_id,
                    "key" =>
                        cache($machine_id . ".signKey") ??
                        (MachineModel::getFieldValue(['machine_id' => $machine_id],'signKey') ?? env("api.md5Key")),
                ];
                $app = AppFactory::machine($config);
                @$app->sendMq->sendMq("updateMachineConfig");
            }
        }
    }

    /**
     * 修改后通知下发设备终端更新
     * @param Model $model
     */
    protected static function onAfterUpdate(Model $model)
    {
        $where = $model->getWhere();
        if (!$where) $where['mc_id'] = $model['mc_id'];
        if ($where) {
            $machine_id = self::getFieldValue($where, 'machine_id');
            if ($machine_id) {
                $config = [
                    "machine_id" => $machine_id,
                    "key" =>
                        cache($machine_id . ".signKey") ??
                        (MachineModel::getFieldValue(['machine_id' => $machine_id],'signKey') ?? env("api.md5Key")),
                ];
                $app = AppFactory::machine($config);
                @$app->sendMq->sendMq("updateMachineConfig");
            }
        }
    }
}