<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:25
 */

namespace app\AppFactory\Kernel\Model\Machine;


use app\AppFactory\AppFactory;
use app\AppFactory\Kernel\Model\BaseModel;
use think\Model;

class MachineModel extends BaseModel
{
    protected $pk = "m_id";
    protected $name = "machine";

    /**
     * 修改后下发通知设备更新
     * @param Model $model
     */
    protected static function onAfterUpdate($model)
    {
        $where = $model->getWhere();
        if (!$where) $where['m_id'] = $model['m_id'];
        if ($where) {
            $machine_id = self::getValue($where, 'machine_id');
            if ($machine_id) {
                $config = [
                    "machine_id" => $machine_id,
                    "key" => env("api.md5Key"),
                ];
                $app = AppFactory::machine($config);
                @$app->sendMq->triggerUpdateMachine();
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
        if (!$where) $where['m_id'] = $model['m_id'];
        if ($where) {
            $machine_id = self::getValue($where, 'machine_id');
            if ($machine_id) {
                $config = [
                    "machine_id" => $machine_id,
                    "key" => env("api.md5Key"),
                ];
                $app = AppFactory::machine($config);
                @$app->sendMq->triggerUpdateMachine();
            }
        }
    }
}